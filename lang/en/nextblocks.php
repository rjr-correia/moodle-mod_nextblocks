<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     mod_nextblocks
 * @category    string
 * @copyright   2025 Rui Correia<rjr.correia@campus.fct.unl.pt>
 * @copyright   based on work by 2024 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'NextBlocks';
$string['pluginname'] = 'NextBlocks plugin';
$string['modulenameplural'] = 'NextBlocks';
$string['nextblocksname'] = "Exercise name";
$string['nextblockssettings'] = 'NextBlocks settings';
$string['settings'] = 'NextBlocks settings';
$string['nextblockscreategrading'] = "Grades";
$string['nextblockscreatetiming'] = "Timing";
$string['nextblocksname_help'] = "Name of the NextBlocks activity";
$string['nextblockscreatecustomblocks'] = "Custom Blocks";
$string['nextblockscreatetests'] = "Tests";
$string['nextblockscreateprimitiverestrictions'] = "Primitive Restrictions";
$string['testsinput'] = "Input";
$string['testsinput_help'] = "Test Input";
$string['testsoutput'] = "Output";
$string['customblocksinput'] = "Custom Block Code";
$string['testsoutput_help'] = "Test Output";
$string['customblocksinput_help'] = "Code for the custom block. <br> For information on how to write a custom block visit www.google.com";
$string['nextblocks_cancel'] = "Cancel";
$string['nextblocks_submit'] = "Submit";
$string['nextblocks_run'] = "Run";
$string['nextblocks_runtests'] = "Tests";
$string['nextblocks_save'] = "Save";
$string['testsradio_help'] = "Method for submitting test cases. <br><br> File: Upload a file with the test cases. <br> Text Boxes: Write the test cases the text boxes.";
$string['testsradiolabel'] = "Tests input method:";
$string['testsradiofile'] = "File";
$string['iseval'] = "Exercise is graded?";
$string['testsfilesubmit'] = "Tests file:";
$string['testsradiotextbox'] = "Text Boxes";
$string['iseval_help'] = "Check if the exercise is for graded.";
$string['nextblocks_tab_edit'] = "Test";
$string['nextblocks_tab_view'] = "Test";
$string['blockdefinition'] = 'Custom block definition';
$string['invalidfilecount'] = 'Only upload 1 test file';
$string['invalidfilestructure'] = 'Invalid test file structure';
$string['pluginadministration'] = 'NextBlocks Administration';
$string['blockdefinition_help'] = 'Custom block definition. <br> Copy code from the <b> Block Definition </b> section of the Block Factory. Needs to be in JavaScript format.';
$string['blockgenerator'] = 'Custom block generator (Javascript)';
$string['blockgenerator_help'] = 'Custom block generator function. <br> Copy code from the <b> Generator stub </b> section of the Block Factory. Needs to be in JavaScript. You need to manually add the block code, in var code = ...';
$string['blockpythongenerator'] = 'Custom block generator (Python)';
$string['blockpythongenerator_help'] = 'Custom block generator function. <br> Copy code from the <b> Generator stub </b> section of the Block Factory. Needs to be in Python. You need to manually add the block code, in var code = ...';
$string['addanothercustomblock'] = 'Add another custom block';
$string['customblockstext'] = '<p> Create a custom Blockly block. Intended for advanced users. <br> Please note that the custom block code is not validated in any way, so if the definition or the generator are not correct, the custom block will not work. <br> For help creating a custom block, visit <a target=”_blank” href="https://blockly-demo.appspot.com/static/demos/blockfactory/index.html">https://blockly-demo.appspot.com/static/demos/blockfactory/index.html</a> </p>';
$string['deletestr'] = 'Delete';
$string['gradingselect'] = 'Grading method';
$string['gradingselect0'] = 'None';
$string['gradingselect1'] = 'Point';
$string['gradingselect2'] = 'Scale';
$string['gradingselect3'] = 'Feedback only';
$string['maxgrade'] = 'Maximum grade';
$string['howmanysubmissions'] = 'How many submissions';
$string['multiplesubmissions'] = 'Allow multiple submissions';
$string['nextblockscreatesubmissions'] = 'Submissions';
$string['testsfile'] = 'Tests file';
$string['blocklimits'] = 'Block limits';
$string['newgrade'] = "New Grade: ";
$string['testsfile_help'] = "File containing the test cases. <br> The file must be in the following format: <br> <br> <b>Input1</b> <br> <b>Output1</b> <br> <b>Input2</b> <br> <b>Output2</b> <br> <b>...</b> <br> <b>InputN</b> <br> <b>OutputN</b> <br> <br> Where <b>Input</b> is the input for the test case and <b>Output</b> is the expected output for the test case.";
$string['limitblock'] = 'Max uses for block: {$a}';
$string['judge0url']      = 'https://judge0-ce.p.sulu.sh';
$string['judge0urldesc']  = 'The base URL of the Judge0 API (e.g. https://judge0.p.rapidapi.com).';
$string['judge0token']    = 'sk_live_spZDwoQjgwyN4GTGuNoSrdy8qCdU3vUD';
$string['judge0tokendesc']= 'Your Judge0 (or RapidAPI) key, if required.';
$string['judge0submissionfailed'] = 'Could not submit code to Judge0.';
$string['judge0timeout']          = 'Timed out waiting for Judge0 execution.';