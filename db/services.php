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
 * Virtual Coach external functions and service definitions.
 *
 * The functions and services defined on this file are
 * processed and registered into the Moodle DB after any
 * install or upgrade operation. All plugins support this.
 *
 * For more information, take a look to the documentation available:
 *     - Webservices API: {@link http://docs.moodle.org/dev/Web_services_API}
 *     - External API: {@link http://docs.moodle.org/dev/External_functions_API}
 *     - Upgrade API: {@link http://docs.moodle.org/dev/Upgrade_API}
 *
 * @package     mod_virtualcoach
 * @category    webservice
 * @copyright   2020 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(
    'mod_virtualcoach_get_calendar_weekly_view' => array(
        'classname' => 'mod_virtual_coach_external',
        'methodname' => 'get_calendar_weekly_view',
        'description' => 'Fetch the monthly view data for a calendar',
        'classpath' => 'mod/virtualcoach/externallib.php',
        'type' => 'read',
        'capabilities' => '',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'mod_virtualcoach_get_calendar_event_by_id' => array(
        'classname' => 'mod_virtual_coach_external',
        'methodname' => 'get_calendar_event_by_id',
        'description' => 'Get calendar event by id',
        'classpath' => 'mod/virtualcoach/externallib.php',
        'type' => 'read',
        'capabilities' => 'moodle/calendar:manageentries, moodle/calendar:manageownentries, moodle/calendar:managegroupentries',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'mod_virtualcoach_submit_create_update_form' => array(
        'classname' => 'mod_virtual_coach_external',
        'methodname' => 'submit_create_update_form',
        'description' => 'Submit form data for event form',
        'classpath' => 'mod/virtualcoach/externallib.php',
        'type' => 'write',
        'capabilities' => 'moodle/calendar:manageentries, moodle/calendar:manageownentries, moodle/calendar:managegroupentries',
        'ajax' => true,
    ),
    'mod_virtualcoach_delete_calendar_events' => array(
        'classname' => 'mod_virtual_coach_external',
        'methodname' => 'delete_calendar_events',
        'description' => 'Delete calendar events',
        'classpath' => 'mod/virtualcoach/externallib.php',
        'type' => 'write',
        'capabilities' => 'moodle/calendar:manageentries, moodle/calendar:manageownentries, moodle/calendar:managegroupentries',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
);

