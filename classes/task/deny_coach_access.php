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

namespace mod_virtualcoach\task;

use coding_exception;
use core\task\scheduled_task;
use dml_exception;
use mod_virtualcoach\calendar;

/**
 * An example of a scheduled task.
 */
class deny_coach_access extends scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name() {
        return get_string('denycoachaccess', 'mod_virtualcoach');
    }

    /**
     * Execute the task.
     * @throws dml_exception
     */
    public function execute() {
        calendar::allow_coach_access(false);
    }
}
