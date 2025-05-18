<?php

/**
 *
 * @copyright   2025 Rui Correia<rjr.correia@campus.fct.unl.pt>
 * @copyright   based on work by 2024 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class restore_nextblocks_activity_structure_step extends restore_activity_structure_step
{

    protected function define_structure() {
        $paths = [];

        $paths[] = new restore_path_element(
            'nextblocks',
            '/activity/nextblocks'
        );

        $paths[] = new restore_path_element(
            'customblock',
            '/activity/nextblocks/customblocks/customblock'
        );
        $paths[] = new restore_path_element(
            'blocklimit',
            '/activity/nextblocks/blocklimits/blocklimit'
        );
        $paths[] = new restore_path_element(
            'message',
            '/activity/nextblocks/messages/message'
        );
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element(
                'userdata',
                '/activity/nextblocks/userdatas/userdata'
            );
        }

        return $this->prepare_activity_structure($paths);
    }

    protected function process_nextblocks($data)
    {
        global $DB;

        $data = (object)$data;

        $data->course = $this->get_courseid();
        $data->timecreated = time();
        $data->timemodified = time();

        $newitemid = $DB->insert_record('nextblocks', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_customblock($data)
    {
        global $DB;

        $data = (object)$data;
        $data->nextblocksid = $this->get_new_parentid('nextblocks');
        $DB->insert_record('nextblocks_customblocks', $data);
    }

    protected function process_blocklimit($data)
    {
        global $DB;

        $data = (object)$data;
        $data->nextblocksid = $this->get_new_parentid('nextblocks');
        $DB->insert_record('nextblocks_blocklimit', $data);
    }

    protected function process_message($data)
    {
        global $DB;

        $data = (object)$data;
        $data->nextblocksid = $this->get_new_parentid('nextblocks');
        $DB->insert_record('nextblocks_messages', $data);
    }

    protected function process_userdata($data)
    {
        global $DB;

        $data = (object)$data;
        $data->nextblocksid = $this->get_new_parentid('nextblocks');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $DB->insert_record('nextblocks_userdata', $data);
    }

    protected function after_execute()
    {
        global $DB;
        $oldinstanceid = $this->task->get_old_activityid();
        $newinstanceid = $this->get_new_parentid('nextblocks');

        $oldname = 'tests' . $oldinstanceid . '.json';
        $newname = 'tests' . $newinstanceid . '.json';

        $records = $DB->get_records_sql("
            SELECT contextid, filepath, filename
            FROM {files}
            WHERE component = :component
              AND filearea  = :filearea
              AND itemid    = :itemid
              AND filename != '.'",
            [
                'component' => 'mod_nextblocks',
                'filearea'  => 'attachment',
                'itemid'    => $oldinstanceid,
                'filename'  => $oldname,
            ]
        );

        $rec = reset($records);

        $fs = get_file_storage();
        $file = $fs->get_file(
            $rec->contextid,
            'mod_nextblocks',
            'attachment',
            $oldinstanceid,
            $rec->filepath,
            $rec->filename
        );

        $fileinfo = [
            'contextid' => $rec->contextid,
            'component' => 'mod_nextblocks',
            'filearea'  => 'attachment',
            'itemid'    => $newinstanceid,
            'filepath'  => $rec->filepath,
            'filename'  => $newname,
        ];

        $fs->create_file_from_storedfile($fileinfo, $file);

        $this->add_related_files('mod_nextblocks', 'intro', null);

    }
}