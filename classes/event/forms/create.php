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
 * @copyright   2020 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_virtualcoach\event\forms;

use coding_exception;
use core_calendar\local\event\forms\eventtype;
use core_date;
use DateTime;
use dml_exception;
use MoodleQuickForm;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot . '/mod/virtualcoach/calendarlib.php');

/**
 * Enrolment observers class.
 *
 * @package     mod_virtualcoach
 * @copyright   2020 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create extends \moodleform {

    use eventtype;

    const HOURS_DAILY = 'daily';
    const HOURS_WEEKLY = 'weekly';
    const HOURS_COURSE = 'course';

        /**
     * Build the editor options using the given context.
     *
     * @param \context $context A Moodle context
     * @return array
     */
    public static function build_editor_options(\context $context) {
        global $CFG;

        return [
            'context' => $context,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $CFG->maxbytes,
            'noclean' => true,
            'autosave' => false
        ];
    }

    /**
     * The form definition
     * @throws \moodle_exception
     */
    public function definition() {
        global $PAGE;

        $mform = $this->_form;
        $starttime = isset($this->_customdata['starttime']) ? $this->_customdata['starttime'] : 0;

        $mform->setDisableShortforms();
        $mform->disable_form_change_checker();

        // Empty string so that the element doesn't get rendered.
        $mform->addElement('header', 'general', '');

        $this->add_default_hidden_elements($mform);

        // Event time start field.
        $mform->addElement('date_time_selector', 'timestart', get_string('date'), ['defaulttime' => $starttime]/*, ['disabled' => 'disabled']*/);

        // Add the variety of elements allowed for selecting event duration.
        $this->add_event_duration_elements($mform);

        // Add the javascript required to enhance this mform.
        $PAGE->requires->js_call_amd('core_calendar/event_form', 'init', [$mform->getAttribute('id')]);
    }

    /**
     * A bit of custom validation for this form
     *
     * @param array $data An assoc array of field=>value
     * @param array $files An array of files
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    public function validation($data, $files): array
    {
        global $DB, $USER;

        $errors = parent::validation($data, $files);
        $eventtype = isset($data['eventtype']) ? $data['eventtype'] : null;
        $coursekey = ($eventtype == 'group') ? 'groupcourseid' : 'courseid';
        $courseid = (!empty($data[$coursekey])) ? $data[$coursekey] : null;

        if ($courseid && $courseid > 0) {
            if ($course = $DB->get_record('course', ['id' => $courseid])) {
                if ($data['timestart'] < $course->startdate) {
                    $errors['timestart'] = get_string('errorbeforecoursestart', 'calendar');
                }
            } else {
                $errors[$coursekey] = get_string('invalidcourse', 'error');
            }
        }

        // The time you are trying to reserve is already occupied by another user
        $timestart = $data['timestart'];
        $timeduration = $data['timedurationminutes'] * 60;
        $location = $data['location'];
        if (self::is_exists($timestart, $timeduration, $USER->id, $location)) {
            $errors['timestart'] = get_string('busy_coach', 'virtualcoach');
        }

        if (!has_teacher($courseid)) {
            $errors += $this->validate_max_hours($data, self::HOURS_DAILY);
            $errors += $this->validate_max_hours($data, self::HOURS_WEEKLY);
            $errors += $this->validate_max_hours($data, self::HOURS_COURSE);
        }

        /*if ($data['duration'] == 1 && $data['timestart'] > $data['timedurationuntil']) {
            $errors['durationgroup'] = get_string('invalidtimedurationuntil', 'calendar');
        } else if ($data['duration'] == 2 && (trim($data['timedurationminutes']) == '' || $data['timedurationminutes'] < 1)) {
            $errors['durationgroup'] = get_string('invalidtimedurationminutes', 'calendar');
        }*/

        return $errors;
    }

    /**
     * @param int $timestart
     * @param int $timeduration
     * @param $user
     * @param int $location
     * @return bool
     * @throws dml_exception
     */
    public static function is_exists($timestart, $timeduration, $user, $location)
    {
        global $DB;
        $timeend = $timestart + $timeduration;
        $locationInt = $DB->sql_cast_char2int('e.location');

        $exist = $DB->record_exists_sql("SELECT e.id FROM {event} e
            WHERE $locationInt = $location AND e.eventtype = 'virtualcoach' AND e.userid != $user AND (
                (e.timestart BETWEEN $timestart AND $timeend)
                OR (e.timestart + e.timeduration BETWEEN $timestart AND $timeend)
                OR ($timestart BETWEEN e.timestart AND e.timestart + e.timeduration)
                OR ($timeend BETWEEN e.timestart AND e.timestart + e.timeduration)
            )
        ");

        return $exist;
    }

    protected function validate_max_hours($data, $mode)
    {
        global $USER;
        $moduleinstance = $this->get_module_instance($data['instance']);
        $maxHours = $moduleinstance->{"max_{$mode}_hours"};
        $eventtype = isset($data['eventtype']) ? $data['eventtype'] : null;
        $coursekey = ($eventtype == 'group') ? 'groupcourseid' : 'courseid';
        $errors = [];

        $times = $this->get_time_start_end($data['timestart'], $mode);
        $UserHours = self::max_hours($USER->id, $data[$coursekey], $data['location'], $data['id'], $times['timeStart'], $times['timeEnd']);
        if ($UserHours->user_hours) {
            $UserHours = round($UserHours->user_hours / 60 / 60) + round($data['timedurationminutes'] / 60);
            if ((int)$UserHours > (int)$maxHours) {
                $errors['timestart'] = get_string("max_{$mode}_hours_error", 'virtualcoach', ['user_hours' => $UserHours, 'max_hours' => $maxHours]);
            }
        } else {
            $UserHours = round($data['timedurationminutes'] / 60);
            if ($UserHours > (int)$maxHours) {
                $errors['timestart'] = get_string("max_{$mode}_hours_error", 'virtualcoach', ['user_hours' => $UserHours, 'max_hours' => $maxHours]);
            }
        }

        return $errors;
    }

    protected function get_time_start_end($time, $mode)
    {
        $date = new DateTime('now', core_date::get_user_timezone_object(99));
        $date->setTimestamp($time);
        if ($mode == self::HOURS_DAILY) {
            $date->setTime(0,0);
            $timeStart = $date->getTimestamp();
            $date->setTime(23,59);
            $timeEnd = $date->getTimestamp();
        } elseif ($mode == self::HOURS_WEEKLY) {
            $date->modify('this week')->setTime(0,0);
            $timeStart = $date->getTimestamp();
            $date->modify('+7 days');
            $timeEnd = $date->getTimestamp();
        } else {
            $timeStart = $timeEnd = null;
        }

        return [
            'timeStart' => $timeStart,
            'timeEnd' => $timeEnd,
        ];
    }

    /**
     * @param int $user
     * @param int $course
     * @param $location
     * @param null $id
     * @param null $timeStart
     * @param null $timeEnd
     * @return object
     * @throws dml_exception
     */
    public static function max_hours($user, $course, $location, $id = null, $timeStart = null, $timeEnd = null)
    {
        global $DB;
        $locationInt = $DB->sql_cast_char2int('e.location');

        $sql = "SELECT sum(e.timeduration) AS user_hours FROM {event} e
            WHERE e.eventtype = 'virtualcoach' AND e.userid = $user AND e.courseid = $course AND $locationInt = $location
        ";

        if ($id) {
            $sql .= " AND e.id != $id";
        }

        if ($timeStart && $timeEnd) {
            $sql .= " AND ((e.timestart BETWEEN $timeStart AND $timeEnd) OR
             (e.timestart + e.timeduration BETWEEN $timeStart AND $timeEnd))";
        }

        $result = $DB->get_record_sql($sql);

        return $result;
    }

    protected function get_module_instance($id)
    {
        global $DB;
        return $DB->get_record('virtualcoach', array('id' => $id), '*', MUST_EXIST);
    }

    /**
     * Add the list of hidden elements that should appear in this form each
     * time. These elements will never be visible to the user.
     *
     * @param MoodleQuickForm $mform
     */
    protected function add_default_hidden_elements($mform) {
        global $USER;

        // Event name field.
        $mform->addElement('hidden', 'name', get_string('eventname', 'calendar'), 'size="50"');
        $mform->setType('name', PARAM_TEXT);
        $mform->setDefault('name', get_string('coach_booking', 'virtualcoach'));

        $mform->addElement('hidden', 'location', get_string('location', 'moodle'), 'size="50"');
        $mform->setType('location', PARAM_RAW_TRIMMED);


        // Add some hidden fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', 0);

        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);
        $mform->setDefault('userid', $USER->id);

        $mform->addElement('hidden', 'modulename', 'modulename');
        $mform->setType('modulename', PARAM_TEXT);
        $mform->setDefault('modulename', 'virtualcoach');

        $mform->addElement('hidden', 'eventtype', 'eventtype');
        $mform->setType('eventtype', PARAM_TEXT);
        $mform->setDefault('eventtype', 'virtualcoach');

        $mform->addElement('hidden', 'courseid', get_string('course'));
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'instance', 'instance');
        $mform->setType('instance', PARAM_INT);

        $mform->addElement('hidden', 'visible');
        $mform->setType('visible', PARAM_INT);
        $mform->setDefault('visible', 1);
    }

    /**
     * Add the various elements to express the duration options available
     * for an event.
     *
     * @param MoodleQuickForm $mform
     * @throws coding_exception
     */
    protected function add_event_duration_elements($mform) {
        $mform->addElement('hidden', 'duration', null, get_string('duration_hours', 'virtualcoach'), 2);
        $mform->setType('duration', PARAM_INT);
        $mform->setDefault('duration', 2);
        $mform->addElement('select', 'timedurationminutes', get_string('duration_hours', 'virtualcoach'), $this->getOptions(5));
        $mform->setType('timedurationminutes', PARAM_INT);

        //$mform->disabledIf('timestart[minute]', 'duration');
    }

    protected function getOptions($hours) {
        /*$moduleinstance = $this->get_module_instance($data['instance']);
        $maxHours = $moduleinstance->{"max_daily_hours"};*/

        $options = array();
        for ($i = 1; $i <= $hours; $i++) {
            $options['' . $i * 60 - 1] = $i;
        }
        return $options;
    }
}
