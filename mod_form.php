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
 * The main mod_virtualcoach configuration form.
 *
 * @package     mod_virtualcoach
 * @copyright   2019 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_virtualcoach\enrolment_observers;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package    mod_virtualcoach
 * @copyright  2019 Dany Daniel Roche <ddanielroche@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_virtualcoach_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     * @throws coding_exception
     * @throws dml_exception
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('virtualcoachname', 'mod_virtualcoach'), array('size' => '64'));

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'virtualcoachname', 'mod_virtualcoach');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        // Adding the rest of mod_virtualcoach settings, spreading all them into this fieldset
        // ... or adding more fieldsets ('header' elements) if needed for better logic.
        $mform->addElement('static', 'label1', 'virtualcoachsettings', get_string('virtualcoachsettings', 'mod_virtualcoach'));
        $mform->addElement('header', 'virtualcoachfieldset', get_string('virtualcoachfieldset', 'mod_virtualcoach'));

        /*$mform->addElement('checkbox', 'autoassign', '&nbsp;', ' ' . get_string('autoassign', 'mod_virtualcoach'));
        $mform->setDefault('autoassign', true);
        $mform->addHelpButton('autoassign', 'autoassign', 'mod_virtualcoach');*/

        $coaches = enrolment_observers::get_active_coaches();
        $mform->addElement('select', 'default_coach_id', get_string('default_coach_id', 'mod_virtualcoach'), $coaches);
        $mform->setDefault('default_coach_id', true);
        $mform->addHelpButton('default_coach_id', 'default_coach_id', 'mod_virtualcoach');

        $mform->addElement('text', 'max_daily_hours', get_string('max_daily_hours', 'mod_virtualcoach'));
        $mform->setDefault('max_daily_hours', 3);
        $mform->addHelpButton('max_daily_hours', 'max_daily_hours', 'mod_virtualcoach');

        $mform->addElement('text', 'max_weekly_hours', get_string('max_weekly_hours', 'mod_virtualcoach'));
        $mform->setDefault('max_weekly_hours', 21);
        $mform->addHelpButton('max_weekly_hours', 'max_weekly_hours', 'mod_virtualcoach');

        $mform->addElement('text', 'max_course_hours', get_string('max_course_hours', 'mod_virtualcoach'));
        $mform->setDefault('max_course_hours', 30);
        $mform->addHelpButton('max_course_hours', 'max_course_hours', 'mod_virtualcoach');

        /*$mform->addElement('text', 'max_days', get_string('max_days', 'mod_virtualcoach'));
        $mform->setDefault('max_days', 30);
        $mform->addHelpButton('max_days', 'max_days', 'mod_virtualcoach');*/

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
            $mform->setType('default_coach_id', PARAM_TEXT);
            $mform->setType('max_daily_hours', PARAM_TEXT);
            $mform->setType('max_weekly_hours', PARAM_TEXT);
            $mform->setType('max_course_hours', PARAM_TEXT);
            $mform->setType('max_days', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
            $mform->setType('default_coach_id', PARAM_CLEANHTML);
            $mform->setType('max_daily_hours', PARAM_CLEANHTML);
            $mform->setType('max_weekly_hours', PARAM_CLEANHTML);
            $mform->setType('max_course_hours', PARAM_CLEANHTML);
            //$mform->setType('max_days', PARAM_CLEANHTML);
        }

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }
}
