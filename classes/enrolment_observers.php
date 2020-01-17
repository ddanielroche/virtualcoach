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
use Exception;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Enrolment observers class.
 *
 * @package     mod_virtualcoach
 * @copyright   2019 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrolment_observers {

    /**
     * @param base $event
     * @return bool|int
     * @throws Exception
     */
    public static function user_enrolment_created($event) {
        return static::create_coach_assign($event->relateduserid, $event->courseid);
    }

    /**
     * @param $user
     * @param $course
     * @return bool|int
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function create_coach_assign($user, $course) {
        global $DB;

        if (!$DB->record_exists('coach_assign', ['course' => $course, 'userid' => $user])) {
            $coachAssign = new stdClass();
            $coachAssign->coach = static::get_available_coach();
            $coachAssign->course = $course;
            $coachAssign->userid = $user;

            return $DB->insert_record('coach_assign', $coachAssign);
        }
        return true;
    }

    /**
     * @return mixed
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function get_available_coach() {
        global $DB;

        $coach = $DB->get_record_sql('SELECT coach.id, count(ca.coach) as cantidad
FROM {coach_assign} as ca
RIGHT JOIN {coach} as coach on ca.coach = coach.id
WHERE coach.active = 1
GROUP BY coach.id
ORDER BY count(ca.coach), coach.id',null, IGNORE_MULTIPLE);
        if (!$coach)
            throw new moodle_exception('coachnotavailable', 'virtualcoach');

        return $coach->id;
    }

    /**
     * @param $user
     * @param $course
     * @return mixed
     * @throws dml_exception
     */
    public static function get_coach_assign($user, $course) {
        global $DB;

        return $DB->get_record('coach_assign', ['userid' => $user, 'course' => $course], '*', IGNORE_MULTIPLE);
    }

    /**
     * @param $user
     * @param $curse
     * @return string
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function get_coach_link($user, $curse) {
        global $DB;

        /** @var object $coach_assign */
        if (!$coach_assign = static::get_coach_assign($user->id, $curse)) {
            static::create_coach_assign($user->id, $curse);
            $coach_assign = static::get_coach_assign($user->id, $curse);
        }

        $coach = $DB->get_record('coach', ['id' => $coach_assign->coach], '*', MUST_EXIST);
        $name = get_string('modulename', 'mod_virtualcoach');
        return "<a href='rdp.php?username={$user->username}&machine={$coach->pool}'>$name</a>";
    }

    /**
     * @param base $event
     * @throws Exception
     */
    public static function user_enrolment_updated($event) {
        throw new \core\session\exception($event->get_context());
    }

    /**
     * @param base $event
     * @return bool
     * @throws Exception
     */
    public static function user_enrolment_deleted($event) {
        global $DB;

        return $DB->delete_records('coach_assign', [
            'course' => $event->courseid,
            'userid' => $event->relateduserid,
        ]);
    }
}
