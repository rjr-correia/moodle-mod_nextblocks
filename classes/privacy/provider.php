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
 * Prints an instance of mod_nextblocks.
 *
 * @package     mod_nextblocks
 * @copyright   2025 Rui Correia<rjr.correia@campus.fct.unl.pt>
 * @copyright   based on work by 2024 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_nextblocks\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\helper;
use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\plugin_provider;
use core_privacy\local\request\contextlist\user_contextlist;

/**
 * Privacy provider for mod_nextblocks.
 *
 * This plugin stores the following user data:
 *  - nextblocks_userdata (saved workspaces, last submissions, etc.)
 *  - nextblocks_customblocks (if user‐created custom blocks are saved per user)
 *
 * @package    mod_nextblocks
 * @copyright  2025 Your Name <youremail@example.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    metadata_provider,
    plugin_provider {

    /**
     * Returns meta‐data about the data stored by this plugin.
     *
     * @param collection $collection Privacy metadata collection to add to.
     * @return collection
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'nextblocks_userdata',
            [
                'userid'         => 'privacy:metadata:nextblocks_userdata:userid',
                'nextblocksid'   => 'privacy:metadata:nextblocks_userdata:nextblocksid',
                'saved_workspace'=> 'privacy:metadata:nextblocks_userdata:saved_workspace',
                'testsfile'      => 'privacy:metadata:nextblocks_userdata:testsfile',
                'lastrun'        => 'privacy:metadata:nextblocks_userdata:lastrun'
            ],
            'privacy:metadata:nextblocks_userdata'
        );

        $collection->add_database_table(
            'nextblocks_customblocks',
            [
                'nextblocksid'   => 'privacy:metadata:nextblocks_customblocks:nextblocksid',
                'userid'         => 'privacy:metadata:nextblocks_customblocks:userid',
                'blockxml'       => 'privacy:metadata:nextblocks_customblocks:blockxml',
                'timecreated'    => 'privacy:metadata:nextblocks_customblocks:timecreated'
            ],
            'privacy:metadata:nextblocks_customblocks'
        );

        return $collection;
    }

    /**
     * Get contexts where user data is stored for the specified user.
     *
     * @param int $userid User ID.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        global $DB;
        $contextlist = new user_contextlist();

        $sql = "SELECT DISTINCT cm.id
                  FROM {nextblocks_userdata} ud
                  JOIN {nextblocks} nb            ON nb.id = ud.nextblocksid
                  JOIN {course_modules} cm        ON cm.instance = nb.id AND cm.module = :modid
                  JOIN {context} ctx              ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                 WHERE ud.userid = :userid";
        $params = [
            'modid'         => get_config('core', 'mod_nextblocks_id'), // Usually module id for nextblocks
            'contextlevel'  => CONTEXT_MODULE,
            'userid'        => $userid
        ];
        $mods = $DB->get_records_sql($sql, $params);
        foreach ($mods as $cmid => $unused) {
            $contextlist->add_context(\context_module::instance($cmid));
        }

        $sql2 = "SELECT DISTINCT cm.id
                   FROM {nextblocks_customblocks} cb
                   JOIN {nextblocks} nb            ON nb.id = cb.nextblocksid
                   JOIN {course_modules} cm        ON cm.instance = nb.id AND cm.module = :modid
                   JOIN {context} ctx              ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                  WHERE cb.userid = :userid";
        $params2 = [
            'modid'         => get_config('core', 'mod_nextblocks_id'),
            'contextlevel'  => CONTEXT_MODULE,
            'userid'        => $userid
        ];
        $mods2 = $DB->get_records_sql($sql2, $params2);
        foreach ($mods2 as $cmid => $unused) {
            $contextlist->add_context(\context_module::instance($cmid));
        }

        return $contextlist;
    }

    /**
     * Get all users in the given context who have data in this plugin.
     *
     * @param \context $context
     * @return userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        global $DB;

        if (!$context = $userlist->get_context() or $context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $cmid = $context->instanceid;

        $sql = "SELECT ud.userid
                  FROM {nextblocks_userdata} ud
                  JOIN {nextblocks} nb      ON nb.id = ud.nextblocksid
                  JOIN {course_modules} cm  ON cm.instance = nb.id AND cm.id = :cmid
                 WHERE ud.userid IS NOT NULL";
        $params = ['cmid' => $cmid];
        $users = $DB->get_records_sql($sql, $params);
        foreach ($users as $record) {
            $userlist->add_user($record->userid);
        }

        $sql2 = "SELECT cb.userid
                  FROM {nextblocks_customblocks} cb
                  JOIN {nextblocks} nb      ON nb.id = cb.nextblocksid
                  JOIN {course_modules} cm  ON cm.instance = nb.id AND cm.id = :cmid";
        $users2 = $DB->get_records_sql($sql2, $params);
        foreach ($users2 as $record) {
            $userlist->add_user($record->userid);
        }
    }

    /**
     * Export all user data in the specified contexts for the given user.
     *
     * @param \context $context
     * @param array    $users An array of user IDs to export.
     */
    public static function export_user_data(\context $context, array $users) {
        global $DB;

        $cm = get_coursemodule_from_id('nextblocks', $context->instanceid, 0, false, MUST_EXIST);
        $nb = $DB->get_record('nextblocks', ['id' => $cm->instance], '*', MUST_EXIST);

        $fs = writer::with_context($context);
        $subcontext = ['nextblockinstance', $nb->id];

        foreach ($users as $userid) {
            $userdata = $DB->get_records(
                'nextblocks_userdata',
                ['userid' => $userid, 'nextblocksid' => $nb->id]
            );
            foreach ($userdata as $data) {
                writer::export_data(
                    array_merge($subcontext, ['userdata']),
                    (object)[
                        'saved_workspace' => $data->saved_workspace,
                        'testsfile'       => $data->testsfile,
                        'lastrun'         => $data->lastrun
                    ]
                );
            }

            $customblocks = $DB->get_records(
                'nextblocks_customblocks',
                ['userid' => $userid, 'nextblocksid' => $nb->id]
            );
            foreach ($customblocks as $cb) {
                writer::export_data(
                    array_merge($subcontext, ['customblocks']),
                    (object)[
                        'blockxml'    => $cb->blockxml,
                        'timecreated' => $cb->timecreated
                    ]
                );
            }
        }
    }

    /**
     * Delete all user data for all users in the given context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('nextblocks', $context->instanceid, 0, false, MUST_EXIST);
        $nbid = $DB->get_field('nextblocks', 'id', ['id' => $cm->instance], MUST_EXIST);

        $DB->delete_records('nextblocks_userdata', ['nextblocksid' => $nbid]);

        $DB->delete_records('nextblocks_customblocks', ['nextblocksid' => $nbid]);
    }

    /**
     * Delete all user data for the specified user in all given contexts.
     *
     * @param \context $context
     * @param int      $userid
     */
    public static function delete_data_for_user(\context $context, int $userid) {
        global $DB;

        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('nextblocks', $context->instanceid, 0, false, MUST_EXIST);
        $nbid = $DB->get_field('nextblocks', 'id', ['id' => $cm->instance], MUST_EXIST);

        $DB->delete_records('nextblocks_userdata', [
            'userid'       => $userid,
            'nextblocksid' => $nbid
        ]);

        $DB->delete_records('nextblocks_customblocks', [
            'userid'       => $userid,
            'nextblocksid' => $nbid
        ]);
    }

    /**
     * Delete all user data for the specified user across all contexts.
     *
     * @param int $userid
     */
    public static function delete_data_for_userid(int $userid) {
        global $DB;

        $DB->delete_records('nextblocks_userdata', ['userid' => $userid]);

        $DB->delete_records('nextblocks_customblocks', ['userid' => $userid]);
    }
}
