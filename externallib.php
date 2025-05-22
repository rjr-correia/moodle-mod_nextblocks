<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package     mod_nextblocks
 * @copyright   2025 Rui Correia<rjr.correia@campus.fct.unl.pt>
 * @copyright   based on work by 2024 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once("$CFG->libdir/externallib.php");
require_once(__DIR__ . '/lib.php');

/**
 * Handles communications with external sources
 */
class mod_nextblocks_external extends external_api {

    /**
     * Saves the workspace of a user.
     *
     * @param int    $nextblocksid Id of the nextblocks activity
     * @param string $savedworkspace The workspace to be saved, in base64
     * @param int    $userid          The id of the user that is saving the workspace.
     *                                By default is not needed, and the current user is used.
     *                                Only used when teacher is adding comments to user's workspace.
     *
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function save_workspace($nextblocksid, $savedworkspace, $userid=null) {
        global $DB, $USER;
        if (!$userid) {
            $userid = $USER->id;
        }
        $params = self::validate_parameters(self::save_workspace_parameters(),
            ['nextblocksid' => $nextblocksid, 'saved_workspace' => $savedworkspace]);
        $cm = get_coursemodule_from_id('nextblocks', $nextblocksid, 0, false, MUST_EXIST);
        // Check if record exists.
        $record = $DB->get_record('nextblocks_userdata', ['userid' => $userid, 'nextblocksid' => $cm->instance]);
        // If record exists with same userid and nextblocksid, update it, else insert new record.
        if ($record) {
            $DB->update_record('nextblocks_userdata', ['id' => $record->id, 'userid' => $userid,
                'nextblocksid' => $cm->instance, 'saved_workspace' => $savedworkspace]);
        } else {
            $DB->insert_record('nextblocks_userdata', ['userid' => $userid,
                'nextblocksid' => $cm->instance, 'saved_workspace' => $savedworkspace]);
        }
    }

    /**
     * @return external_function_parameters
     */
    public static function save_workspace_parameters(){
        return new external_function_parameters(
            [
                'nextblocksid' => new external_value(PARAM_INT, 'module id', VALUE_REQUIRED),
                'saved_workspace' => new external_value(PARAM_RAW, 'workspace', VALUE_REQUIRED),
                'userid' => new external_value(PARAM_INT, 'user id', VALUE_OPTIONAL, 0),
            ]
        );
    }

    /**
     * @return null
     */
    public static function save_workspace_returns() {
        return null;
    }

    /**
     * Handles program submission when user clicks "Submit" button
     * 
     * @param $nextblocksid int activity instance id
     * @param $submittedworkspace object program to submit 
     * @param $codestring string javascript code generated from program
     */
    public static function submit_workspace($nextblocksid, $submittedworkspace, $codestring) {
        global $DB, $USER, $PAGE;

        $params = self::validate_parameters(self::submit_workspace_parameters(),
            ['nextblocksid' => $nextblocksid, 'submitted_workspace' => $submittedworkspace, 'codeString' => $codestring]);
        $cm = get_coursemodule_from_id('nextblocks', $nextblocksid, 0, false, MUST_EXIST);
        // Check if record exists.
        $record = $DB->get_record('nextblocks_userdata', ['userid' => $USER->id, 'nextblocksid' => $cm->instance]);
        // If record exists with same userid and nextblocksid, update it, else insert new record.
        if ($record) {
            $DB->update_record('nextblocks_userdata', ['id' => $record->id, 'userid' => $USER->id, 'nextblocksid' => $cm->instance, 
                'saved_workspace' => $submittedworkspace, 'submitted_workspace' => $submittedworkspace, 'submissionnumber' => $record->submissionnumber + 1]);
        } else {
            $DB->insert_record('nextblocks_userdata', ['userid' => $USER->id, 'nextblocksid' => $cm->instance, 
                'saved_workspace' => $submittedworkspace, 'submitted_workspace' => $submittedworkspace, 'submissionnumber' => 1]);
        }

        $nextblocks = $DB->get_record('nextblocks', ['id' => $cm->instance]);

        $context = context_module::instance($cm->id);
        $PAGE->set_context($context);

        $fs = get_file_storage();
        $filenamehash = get_filenamehash($cm->instance);

        // If has point grade and tests, run auto grading.
        if ($nextblocks->grade > 0 && $filenamehash != false) {
            $testsfile = $fs->get_file_by_hash($filenamehash);
            self::auto_grade($cm, $codestring, $nextblocks, $testsfile);
        }
    }

    /**
     * Automatically grade students' submissions using test system
     *
     * @param $cm object context module
     * @param $codestring string javascript code generated from program
     * @param $nextblocks object plugin instance
     * @param $testsfile object tests file
     */
    public static function auto_grade($cm, $codestring, $nextblocks, $testsfile) {
        global $USER, $DB;

        $testsfile_contents = $testsfile->get_content();

        $tests = json_decode($testsfile_contents, true);

        $testscount = count($tests);
        $testscorrectcount = self::run_tests_jobe($tests, $codestring);
        $newgrade = $testscorrectcount / $testscount * $nextblocks->grade; //... $nextblocks->grade is the max grade.


        $grades = new stdClass();
        $grades->userid = $USER->id;
        $grades->rawgrade = $newgrade;

        nextblocks_grade_item_update($nextblocks, $grades);

        // Update userdata with new grade.
        $userdata = $DB->get_record('nextblocks_userdata', ['userid' => $USER->id, 'nextblocksid' => $cm->instance]);
        $DB->update_record('nextblocks_userdata', ['id' => $userdata->id, 'userid' => $USER->id,
            'nextblocksid' => $cm->instance, 'grade' => $newgrade]);
    }

    /**
     * Runs the program against the given tests
     * 
     * @param $tests object[]
     * @param $codestring string javascript code from the program
     * @return int number of passed tests
     */
    public static function run_tests_jobe($tests, $codestring): int {
        $testscorrectcount = 0;
        for ($i = 0; $i < count($tests); $i++) {
            $test = $tests[$i];
            $inputs = $test['inputs'];
            $expectedoutput = $test['output'];
            // Json has arrays where there shouldn't be, as there is only one element, so the foreaches are necessary.
            foreach ($inputs as $key => $val) {
                $inputname = "";
                $input = "";
                foreach ($val as $inputname_ => $val1) {
                    $inputname = $inputname_;
                    $input = "";
                    foreach ($val1 as $key2 => $inputvalue_) {
                        $input = $inputvalue_;
                    }
                }
                // Get the indices of the first and second parentheses of the last occurrence of the input function call.
                $firstparenindex = strrpos($codestring, "input" . $inputname . "(");
                $secondparenindex = strpos($codestring, ")", $firstparenindex);

                // Replace everything between the parentheses with the input.
                $codestring = substr_replace($codestring, $input, $firstparenindex + strlen("input" . $inputname . "("),
                    $secondparenindex - $firstparenindex - strlen("input" . $inputname . "("));
            }

            $testoutput = self::run_test_jobe($codestring);
            if ($testoutput == $expectedoutput) {
                $testscorrectcount++;
            }
        }
        
        return $testscorrectcount;
    }

    /**
     * Runs tests through jobe
     * 
     * @param $codestring string JavaScript code from the program
     * @return mixed test results
     */
    public static function run_test_jobe($codestring) {
        $url = 'http://localhost:4000/jobe/index.php/restapi/runs/';
        $data = [
            "run_spec" => [
                'language_id' => 'nodejs',
                'sourcefilename' => 'test.js',
                'sourcecode' => $codestring,
            ],
        ];

        // Use key 'http' even if you send the request to https://...
        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data),
            ],
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === false) {
            // Unexpected error.
            return null;
        }

        $result = json_decode($result, true);
        return $result['stdout'];
    }

    /**
     * @return external_function_parameters
     */
    public static function submit_workspace_parameters() {
        return new external_function_parameters(
            [
                'nextblocksid' => new external_value(PARAM_INT, 'module id'),
                'submitted_workspace' => new external_value(PARAM_RAW, 'workspace'),
                'codeString' => new external_value(PARAM_RAW, 'codeString'),
            ]
        );
    }

    /**
     * @return null
     */
    public static function submit_workspace_returns() {
        return null;
    }

    /**
     * Submits a reaction to an exercise made by a user
     * 
     * @param $nextblocksid int the id of the activity
     * @param $reaction string the reaction done by the user (easy, medium, hard)
     * @return array reaction counter
     */
    public static function submit_reaction($nextblocksid, $reaction) {
        global $DB, $USER;
        $params = self::validate_parameters(self::submit_reaction_parameters(),
            ['nextblocksid' => $nextblocksid, 'reaction' => $reaction]);

        $cm = get_coursemodule_from_id('nextblocks', $nextblocksid, 0, false, MUST_EXIST);
        $nextblocks = $DB->get_record('nextblocks', ['id' => $cm->instance]);
        $userdata = $DB->get_record('nextblocks_userdata', ['userid' => $USER->id, 'nextblocksid' => $cm->instance]);
        // If userdata does not exist, insert new record.
        if (!$userdata) {
            $newid = $DB->insert_record('nextblocks_userdata', ['userid' => $USER->id, 'nextblocksid' => $cm->instance]);
            $userdata = $DB->get_record('nextblocks_userdata', ['id' => $newid]);
        }

        // Get reaction database column name.
        $newreactioncolumnname = "reactions".$reaction;
        // Reaction to number, for database (easy-1, medium-2, hard-3).
        $newreactionnumber = array_search($reaction, ['easy', 'medium', 'hard']) + 1;

        $newreactions = [
            'reactionseasy' => $nextblocks->reactionseasy,
            'reactionsmedium' => $nextblocks->reactionsmedium,
            'reactionshard' => $nextblocks->reactionshard,
        ];

        // If new reaction is same as previous reaction, decrement reaction.
        if ($userdata->reacted == $newreactionnumber) {
            $DB->update_record('nextblocks', ['id' => $nextblocks->id, 
                $newreactioncolumnname => $nextblocks->$newreactioncolumnname - 1]);
            // User unreacted, update userdata.
            $DB->update_record('nextblocks_userdata', ['id' => $userdata->id, 'userid' => $USER->id, 
                'nextblocksid' => $cm->instance, 'reacted' => 0]);
            $newreactions[$newreactioncolumnname] = $nextblocks->$newreactioncolumnname - 1;
        } else { // Else, decrement previous reaction (if it exists) and increment new reaction.
            if ($userdata->reacted == 0) {
                $DB->update_record('nextblocks', ['id' => $nextblocks->id, 
                    $newreactioncolumnname => $nextblocks->$newreactioncolumnname + 1]);
                $newreactions[$newreactioncolumnname] = $nextblocks->$newreactioncolumnname + 1;
            } else {
                $oldreactioncolumnname = "reactions" . ['easy', 'medium', 'hard'][$userdata->reacted - 1];

                $DB->update_record(
                    'nextblocks', [
                        'id' => $nextblocks->id,
                        $newreactioncolumnname => $nextblocks->$newreactioncolumnname + 1,
                        $oldreactioncolumnname => $nextblocks->$oldreactioncolumnname - 1,
                    ]
                );
                $newreactions[$newreactioncolumnname] = $nextblocks->$newreactioncolumnname + 1;
                $newreactions[$oldreactioncolumnname] = $nextblocks->$oldreactioncolumnname - 1;
            }
            // Update userdata with new reaction.
            $DB->update_record('nextblocks_userdata', ['id' => $userdata->id, 'userid' => $USER->id, 
                'nextblocksid' => $cm->instance, 'reacted' => $newreactionnumber]);
        }

        return $newreactions;
    }

    /**
     * @return external_function_parameters
     */
    public static function submit_reaction_parameters() {
        return new external_function_parameters(
            [
                'nextblocksid' => new external_value(PARAM_INT, 'module id'),
                'reaction' => new external_value(PARAM_ALPHA, 'workspace'),
            ]
        );
    }

    /**
     * @return external_single_structure
     */
    public static function submit_reaction_returns() {
        return new external_single_structure(
            [
                'reactionseasy' => new external_value(PARAM_INT, 'number of easy reactions'),
                'reactionsmedium' => new external_value(PARAM_INT, 'number of medium reactions'),
                'reactionshard' => new external_value(PARAM_INT, 'number of hard reactions'),
            ]
        );
    }

    /**
     * Saves a message sent by a user in the database
     * 
     * @param $message string 
     * @param $username string 
     * @param $nextblocksid int activity id
     * @param $timestamp int
     */
    public static function save_message($message, $username, $nextblocksid, $timestamp) {
        global $DB;
        $params = self::validate_parameters(self::save_message_parameters(),
            ['message' => $message, 'userName' => $username, 'nextblocksId' => $nextblocksid, 'timestamp' => $timestamp]);
        $DB->insert_record('nextblocks_messages', ['message' => $message, 'username' => $username, 
            'nextblocksid' => $nextblocksid, 'timestamp' => $timestamp]);
    }

    /**
     * @return external_function_parameters
     */
    public static function save_message_parameters() {
        return new external_function_parameters(
            [
                'message' => new external_value(PARAM_TEXT, 'message sent'),
                'userName' => new external_value(PARAM_TEXT, 'name of the user who sent the message'),
                'nextblocksId' => new external_value(PARAM_INT, 'id of the activity where the message was sent'),
                'timestamp' => new external_value(PARAM_INT, 'when the message was sent (UNIX time)'),
            ]
        );
    }

    /**
     * @return null
     */
    public static function save_message_returns() {
        return null;
    }

    /**
     * Gets a specific number of messages in an activity through the database
     * 
     * @param $messagecount int number of messages to get
     * @param $nextblocksid int activity id
     * @return array list of messages
     */
    public static function get_messages($messagecount, $nextblocksid) {
        global $DB;
        $params = self::validate_parameters(self::get_messages_parameters(),
            ['messageCount' => $messagecount, 'nextblocksId' => $nextblocksid]);
        $messages = $DB->get_records('nextblocks_messages', ['nextblocksid' => $nextblocksid], 
            'timestamp ASC', '*', 0, $messagecount);
        $messagesarray = [];
        foreach ($messages as $message) {
            $messagearray = [
                'message' => $message->message,
                'username' => $message->username,
                'timestamp' => $message->timestamp,
            ];
            $messagesarray[] = $messagearray;
        }
        return $messagesarray;
    }

    /**
     * @return external_function_parameters
     */
    public static function get_messages_parameters() {
        return new external_function_parameters(
            [
                'messageCount' => new external_value(PARAM_INT, 'number of messages to get'),
                'nextblocksId' => new external_value(PARAM_INT, 'id of the activity where the messages were sent'),
            ]
        );
    }

    /**
     * @return external_multiple_structure
     */
    public static function get_messages_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'message' => new external_value(PARAM_TEXT, 'message sent'),
                    'username' => new external_value(PARAM_TEXT, 'name of the user who sent the message'),
                    'timestamp' => new external_value(PARAM_INT, 'when the message was sent (UNIX time)'),
                ]
            )
        );
    }
}
