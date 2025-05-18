<?php

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