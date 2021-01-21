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
use core_user\search\user;
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
        //return static::create_coach_assign($event->relateduserid, $event->courseid);
    }

    /**
     * @param $user
     * @param $course
     * @return bool|int
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function create_coach_assign($user, $course, $moduleinstance) {
        global $DB;

        if (!$DB->record_exists('coach_assign', ['course' => $course, 'userid' => $user])) {
            $coachAssign = new stdClass();
            $coachAssign->course = $course;
            $coachAssign->userid = $user;
            if ($moduleinstance->default_coach_id == 0 && $coach = static::get_available_coach()) {
                $coachAssign->coach = $coach->id;
            } elseif ($moduleinstance->default_coach_id && static::is_available_coach($moduleinstance->default_coach_id)) {
                $coachAssign->coach = $moduleinstance->default_coach_id;
            } else {
                return false;
            }

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

        return $coach;
    }

    /**
     * @param $default_coach_id
     * @return mixed
     * @throws dml_exception
     */
    public static function is_available_coach($default_coach_id) {
        global $DB;

        return $DB->record_exists('coach',  ['id' => $default_coach_id, 'active' => 1]);
    }

    /**
     * @return array
     * @throws dml_exception
     * @throws \coding_exception
     */
    public static function get_active_coaches()
    {
        global $DB;

        $coaches = $DB->get_records('coach',  null, 'id ASC', 'id, name');

        foreach ($coaches as $coach) {
            $coaches[$coach->id] = $coach->name;
        }

        $coaches[0] = get_string('autoassign', 'mod_virtualcoach');

        return $coaches;
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
     * @param int $user
     * @param int $course
     * @return mixed
     * @throws dml_exception
     */
    public static function get_coaches_assign($user, $course) {
        global $DB;

        $coaches = $DB->get_records_sql("SELECT coach.id, coach.name
FROM {coach} as coach
INNER JOIN {coach_assign} as ca on ca.coach = coach.id
WHERE coach.active = 1 AND ca.userid = $user AND ca.course = $course
ORDER BY coach.id");

        foreach ($coaches as $coach) {
            $coaches[$coach->id] = $coach->name;
        }

        return $coaches;
    }

    /**
     * @param $user
     * @param $curse
     * @param $moduleinstance
     * @return mixed
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function get_pool_name($user, $curse, $moduleinstance) {
        global $DB;

        /** @var object $coach_assign */
        if (!$coach_assign = static::get_coach_assign($user->id, $curse)) {
            if (static::create_coach_assign($user->id, $curse, $moduleinstance)) {
                $coach_assign = static::get_coach_assign($user->id, $curse);
            } else {
                throw new moodle_exception('coachnotavailable', 'virtualcoach');
            }
        }

        $coach = $DB->get_record('coach', ['id' => $coach_assign->coach], '*', MUST_EXIST);
        return $coach->pool;
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
