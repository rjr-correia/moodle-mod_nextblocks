<?php
defined('MOODLE_INTERNAL') || die();

class backup_nextblocks_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $nextblocks = new backup_nested_element('nextblocks', ['id'], [
            'course','name','timecreated','timemodified','intro','introformat','iseval',
            'testsfilehash','reactionseasy','reactionsmedium','reactionshard',
            'grade','maxsubmissions'
        ]);

        $userdata       = new backup_nested_element('userdatas');
        $userdataentry  = new backup_nested_element('userdata', ['id'], [
            'userid','saved_workspace','submitted_workspace',
            'submissionnumber','reacted','grade'
        ]);
        $nextblocks->add_child($userdata);
        $userdata->add_child($userdataentry);

        $customblocks  = new backup_nested_element('customblocks');
        $customblock   = new backup_nested_element('customblock', ['id'], [
            'blockdefinition','blockgenerator','blockpythongenerator'
        ]);
        $nextblocks->add_child($customblocks);
        $customblocks->add_child($customblock);

        $messages      = new backup_nested_element('messages');
        $message       = new backup_nested_element('message', ['id'], [
            'message','timestamp','username'
        ]);
        $nextblocks->add_child($messages);
        $messages->add_child($message);

        $blocklimits   = new backup_nested_element('blocklimits');
        $blocklimit    = new backup_nested_element('blocklimit', ['id'], [
            'blocktype','blocklimit'
        ]);
        $nextblocks->add_child($blocklimits);
        $blocklimits->add_child($blocklimit);

        $nextblocks->set_source_table('nextblocks', ['id' => backup::VAR_ACTIVITYID]);
        if ($userinfo) {
            $userdataentry->set_source_table('nextblocks_userdata',
                ['nextblocksid' => backup::VAR_ACTIVITYID]);
        }
        $customblock->set_source_table('nextblocks_customblocks',
            ['nextblocksid' => backup::VAR_ACTIVITYID]);
        $message->set_source_table('nextblocks_messages',
            ['nextblocksid' => backup::VAR_ACTIVITYID]);
        $blocklimit->set_source_table('nextblocks_blocklimit',
            ['nextblocksid' => backup::VAR_ACTIVITYID]);

        $userdataentry->annotate_ids('user', 'userid');
        $nextblocks->annotate_files('mod_nextblocks', 'intro', null);
        $nextblocks->annotate_files('mod_nextblocks', 'attachment', null);

        return $this->prepare_activity_structure($nextblocks);
    }
}