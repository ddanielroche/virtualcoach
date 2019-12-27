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
 * Enrolment observers class.
 *
 * @package     mod_virtualcoach
 * @copyright   2019 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_virtualcoach;

global $CFG;
require_once($CFG->libdir.'/ldaplib.php');

/**
 * Enrolment observers class.
 *
 * @package     mod_virtualcoach
 * @copyright   2019 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class calendar {

    /**
     * @throws \dml_exception
     */
    public static function allow_coach_access() {
        global $DB;
        $time = time() - 60 * 60 * 4;

        $sql = "SELECT  e.location, u.username
FROM {event} e
INNER JOIN {user} u ON u.id = e.userid
WHERE timestart <= $time AND timestart+timeduration >= $time";
        $events = $DB->get_records_sql($sql);

        $sql = "SELECT id, \"group\" FROM {coach}";
        $coaches = $DB->get_records_sql($sql);

        foreach ($coaches as $id => $coach) {
            $location = "coach:$id";
            if (array_key_exists($location, $events)) {
                echo $coach->group . ' ' . $events[$location]->username . "\n";
            }
        }

    }
}