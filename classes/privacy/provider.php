<?php
namespace mod_nextblocks\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'nextblocks_userdata',
            [
                'userid' => 'privacy:metadata:userid',
                'saved_workspace' => 'privacy:metadata:saved_workspace',
                'submitted_workspace' => 'privacy:metadata:submitted_workspace',
                'submissionnumber' => 'privacy:metadata:submissionnumber',
                'reacted' => 'privacy:metadata:reacted',
                'grade' => 'privacy:metadata:grade'
            ],
            'privacy:metadata:nextblocks_userdata'
        );

        return $collection;
    }

    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid
                  JOIN {nextblocks_userdata} ud ON ud.nextblocksid = cm.instance
                 WHERE ctx.contextlevel = :contextlevel
                   AND ud.userid = :userid";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid
        ]);

        return $contextlist;
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) continue;

            $cm = get_coursemodule_from_id('nextblocks', $context->instanceid);
            if (!$cm) continue;

            if ($userdata = $DB->get_records('nextblocks_userdata', [
                'nextblocksid' => $cm->instance,
                'userid' => $userid
            ])) {
                $export = [];
                foreach ($userdata as $record) {
                    $export[] = [
                        'saved_workspace' => $record->saved_workspace,
                        'submitted_workspace' => $record->submitted_workspace,
                        'submission_count' => $record->submissionnumber,
                        'has_reacted' => (bool)$record->reacted,
                        'current_grade' => $record->grade
                    ];
                }
                writer::with_context($context)->export_data(
                    [get_string('workspacedata', 'mod_nextblocks')],
                    (object)['submissions' => $export]
                );
            }
        }
    }


    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) return;

        $DB->delete_records('nextblocks_userdata', [
            'nextblocksid' => $context->instanceid
        ]);
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) continue;

            $DB->delete_records('nextblocks_userdata', [
                'nextblocksid' => $context->instanceid,
                'userid' => $userid
            ]);
        }
    }
}