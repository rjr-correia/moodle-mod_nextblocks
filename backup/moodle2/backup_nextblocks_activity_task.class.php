<?php

/**
 *
 * @copyright   2025 Rui Correia<rjr.correia@campus.fct.unl.pt>
 * @copyright   based on work by 2024 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/backup_nextblocks_stepslib.php');

class backup_nextblocks_activity_task extends backup_activity_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        $this->add_step(new backup_nextblocks_activity_structure_step('nextblocks_structure', 'nextblocks.xml'));
    }

    static public function encode_content_links($content) {
        global $CFG;
        $base = preg_quote($CFG->wwwroot, '/');

        $content = preg_replace(
            "/(".$base."\/mod\/nextblocks\/view.php\?id=)([0-9]+)/",
            '$@NEXTBLOCKSVIEWBYID*$2@$',
            $content
        );

        return $content;
    }
}