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
 * Plugin strings are defined here.
 *
 * @package     mod_virtualcoach
 * @category    string
 * @copyright   2019 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Virtual Coach';
$string['modulename'] = 'Virtual Coach';
$string['modulenameplural'] = 'Virtual Coaches';
$string['modulename_help'] = 'The Virtual Coach activity module enables participants to have time reserve in virtual trainer and access via web or rdp.';
$string['virtualcoachname'] = 'Virtual Coach activity name';
$string['virtualcoachname_help'] = 'Enter name of the Virtual Coach activity that will appear on the course page.';
$string['virtualcoachsettings'] = 'Virtual Coach activity settings';
$string['virtualcoachfieldset'] = 'Virtual Coach config';
$string['allowcoachaccess'] = 'Allow Coach Access';
$string['denycoachaccess'] = 'Deny Coach Access';
$string['sendendofsessionmessage'] = 'Send end of session message';
$string['pluginadministration'] = 'Virtual Coach';
$string['coachnotfound'] = 'The user does not have a coach assigned for this course.';
$string['coachnotavailable'] = 'There is no coach available to assign the user.';
$string['autoassign'] = 'Auto assign';
$string['autoassign_help'] = 'Users will be assigned automatically according to the availability of coaches.';
$string['max_daily_hours'] = 'Max daily hours';
$string['max_weekly_hours'] = 'Max weekly hours';
$string['max_course_hours'] = 'Max course hours';
$string['max_daily_hours_help'] = 'Maximum number of hours to consume daily per user. If the value is zero, this setting will not be evaluated.';
$string['max_weekly_hours_help'] = 'Maximum number of hours to consume weekly per user. If the value is zero, this setting will not be evaluated.';
$string['max_course_hours_help'] = 'Maximum number of hours to consume per user in course. If the value is zero, this setting will not be evaluated.';
$string['max_daily_hours_error'] = 'You have exceeded ({$a->user_hours}) the maximum hours ({$a->max_hours}) allowed in one day.';
$string['max_weekly_hours_error'] = 'You have exceeded ({$a->user_hours}) the maximum hours ({$a->max_hours}) allowed in one week.';
$string['max_course_hours_error'] = 'You have exceeded ({$a->user_hours}) the maximum hours ({$a->max_hours}) allowed for this course.';
$string['max_days'] = 'Max Days';
$string['max_days_help'] = 'Maximum number of days to consume per user.';
$string['default_coach_id'] = 'Default Coach';
$string['default_coach_id_help'] = 'Coach assigned vy default';
$string['transport'] = 'Connection';
$string['hour'] = 'Hour';
$string['weekly_coach_bookings'] = 'Weekly coach bookings:';
$string['week_no'] = 'Week {$a->week}';
$string['coach_access'] = 'Coach access';
$string['reserved_other'] = 'Reserved by other';
$string['reserved_my'] = 'Reserved by my';
$string['coach_assignment'] = 'Coaches Assignment';
$string['duration_hours'] = 'Duration in hours';
$string['coach_booking'] = 'Coach Booking';
$string['busy_coach'] = 'The time you are trying to reserve is already occupied by another user, please choose another time that is available.';