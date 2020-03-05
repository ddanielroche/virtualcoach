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
 * Add event handlers for the virtualcoach
 *
 * @package     mod_virtualcoach
 * @copyright   2019 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\core\event\user_enrolment_created',
        'callback' => '\mod_virtualcoach\enrolment_observers::user_enrolment_created',
    ),
    array(
        'eventname' => '\core\event\user_enrolment_updated',
        'callback' => '\mod_virtualcoach\enrolment_observers::user_enrolment_updated',
    ),
    array(
        'eventname' => '\core\event\user_enrolment_deleted',
        'callback' => '\mod_virtualcoach\enrolment_observers::user_enrolment_deleted',
    ),
    array(
        'eventname' => '\core\event\calendar_event_created',
        'callback' => '\mod_virtualcoach\calendar_event_observers::calendar_event_created',
    ),
    array(
        'eventname' => '\core\event\calendar_event_updated',
        'callback' => '\mod_virtualcoach\calendar_event_observers::calendar_event_updated',
    ),
);
