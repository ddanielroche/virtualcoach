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
 * Enrolment observers.
 *
 * @package     mod_virtualcoach
 * @copyright   2019 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_virtualcoach;

use core\event\base;
use dml_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Enrolment observers class.
 *
 * @package     mod_virtualcoach
 * @copyright   2019 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class calendar_event_observers {

    /**
     * @param base $event
     * @throws dml_exception
     */
    public static function calendar_event_created($event)
    {
        global $DB;
        if (static::is_others($event)) {
            $DB->delete_records('event', ['id' => $event->objectid]);
            echo '[{"error":false,"data":{"validationerror":true}}]';exit;
        }
    }

    /**
     * @param base $event
     * @throws dml_exception
     */
    public static function calendar_event_updated($event)
    {
        global $DB;
        if (static::is_others($event)) {
            $DB->delete_records('event', ['id' => $event->objectid]);
            echo '[{"error":false,"data":{"validationerror":true}}]';exit;
        }
    }

    /**
     * @param base $event
     * @return bool
     * @throws dml_exception
     */
    public static function is_others($event)
    {
        global $DB;

        $exist = $DB->get_record_sql("SELECT e.id,e.courseid, e.name, ca.coach, ca_other.coach, other.id as other_id, other.name, e.timestart, other.timestart as other_timestart, other.timestart + other.timeduration as other_timeend
FROM {event} e
INNER JOIN {event} other
ON (e.id = $event->objectid AND other.id != $event->objectid) AND ((e.timestart BETWEEN other.timestart AND other.timestart + other.timeduration)
OR (e.timestart + e.timeduration BETWEEN other.timestart AND other.timestart + other.timeduration))

INNER JOIN {coach_assign} ca ON (ca.userid = e.userid and ca.course = e.courseid)
INNER JOIN {coach_assign} ca_other ON (ca_other.userid = other.userid and ca_other.course = other.courseid)

where ca.coach = ca_other.coach");
        
        return $exist;
    }
}
