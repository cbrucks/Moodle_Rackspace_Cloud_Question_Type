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
 * Cloud question type upgrade code.
 *
 * @package    qtype
 * @subpackage cloud
 * @copyright  2013 Chris Brucks
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Upgrade code for the cloud question type.
 * @param int $oldversion the version we are upgrading from.
 */
function xmldb_qtype_cloud_upgrade($oldversion = 0) {
    global $DB;
    $dbman = $DB->get_manager();

    $result = true;

    $newversion = 2013021400;

    if ($oldversion < $newversion) {
        // Define field api_key to be dropped from question_cloud_account
        $table = new xmldb_table('question_cloud_account');
        $field = new xmldb_field('api_key');

        // Conditionally launch drop field api_key
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field api_auth_token to be dropped from question_cloud_account
        $table = new xmldb_table('question_cloud_account');
        $field = new xmldb_field('api_auth_token');

        // Conditionally launch drop field api_auth_token
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field srv_name to be added to question_cloud_server
        $table = new xmldb_table('question_cloud_server');
        $field = new xmldb_field('srv_name', XMLDB_TYPE_CHAR, '128', null, XMLDB_NOTNULL, null, 'Old_Servers', 'num');

        // Conditionally launch add field srv_name
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // cloud savepoint reached
        upgrade_plugin_savepoint(true, $newversion, 'qtype', 'cloud');
    }

    return $result;
}
