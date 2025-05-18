<?php

/**
 *
 * @copyright   2025 Rui Correia<rjr.correia@campus.fct.unl.pt>
 * @copyright   based on work by 2024 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/restore_nextblocks_stepslib.php');


class restore_nextblocks_activity_task extends restore_activity_task
{

    protected function define_my_settings(){
    }

    protected function define_my_steps(){
        $this->add_step(new restore_nextblocks_activity_structure_step('nextblocks_structure', 'nextblocks.xml'));
    }

    public function get_fileareas(){
        return ['intro'];
    }

    public function get_configdata_encoded_attributes(){
        return [];
    }

    static public function define_decode_contents(){
        return [];
    }

    static public function define_decode_rules(){
        return [];
    }
}