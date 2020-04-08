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
 * Plugin upgrade steps are defined here.
 *
 * @package     mod_virtualcoach
 * @category    upgrade
 * @copyright   2019 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/upgradelib.php');

/**
 * Execute mod_virtualcoach upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 * @throws ddl_exception
 */
function xmldb_virtualcoach_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    $virtualcoach = $dbman->get_install_xml_schema()->getTable('virtualcoach');

    if ($oldversion < 2019120408) {
        $dbman->add_field($virtualcoach, new xmldb_field(
            'max_hours',
            XMLDB_TYPE_INTEGER,
            '8',
            null,
            XMLDB_NOTNULL,
            null,
            30
        ));

        $dbman->add_field($virtualcoach, new xmldb_field(
            'max_days',
            XMLDB_TYPE_INTEGER,
            '8',
            null,
            XMLDB_NOTNULL,
            null,
            90
        ));
    }

    if ($oldversion < 2019120413) {
        $dbman->add_field($virtualcoach, new xmldb_field(
            'default_coach_id',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            null,
            null,
            null
        ));

        $dbman->add_key($virtualcoach, new xmldb_key(
            'fk_default_coach_id',
            XMLDB_KEY_FOREIGN,
            array('default_coach_id'),
            'coach',
            array('id')
        ));
    }

    // For further information please read the Upgrade API documentation:
    // https://docs.moodle.org/dev/Upgrade_API
    //
    // You will also have to create the db/install.xml file by using the XMLDB Editor.
    // Documentation for the XMLDB Editor can be found at:
    // https://docs.moodle.org/dev/XMLDB_editor

    return true;
}
