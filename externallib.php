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
 * External calendar API
 *
 * @package     mod_virtualcoach
 * @category    external
 * @copyright   2020 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_calendar\external\events_related_objects_cache;
use \core_calendar\local\event\container as event_container;
use core_calendar\external\month_exporter;
use core_calendar\external\week_exporter;
use core_calendar\type_factory;
use \core_calendar\local\event\mappers\create_update_form_mapper;
use \core_calendar\external\event_exporter;

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__.'/../../calendar/externallib.php');

/**
 * Virtual Coach Calendar external functions
 *
 * @package     mod_virtualcoach
 * @category    external
 * @copyright   2020 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_virtual_coach_external extends core_calendar_external {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters.
     */
    public static function submit_create_update_form_parameters() {
        return new external_function_parameters(
            [
                'formdata' => new external_value(PARAM_RAW, 'The data from the event form'),
            ]
        );
    }

    /**
     * Handles the event form submission.
     *
     * @param string $formdata The event form data in a URI encoded param string
     * @return array The created or modified event
     * @throws moodle_exception
     */
    public static function submit_create_update_form($formdata) {
        global $CFG, $USER, $PAGE;
        require_once($CFG->dirroot."/calendar/lib.php");
        require_once($CFG->libdir."/filelib.php");

        // Parameter validation.
        $params = self::validate_parameters(self::submit_create_update_form_parameters(), ['formdata' => $formdata]);
        $context = \context_user::instance($USER->id);
        $data = [];

        self::validate_context($context);
        parse_str($params['formdata'], $data);

        if (WS_SERVER) {
            // Request via WS, ignore sesskey checks in form library.
            $USER->ignoresesskey = true;
        }

        $eventtype = isset($data['eventtype']) ? $data['eventtype'] : null;
        $coursekey = ($eventtype == 'group') ? 'groupcourseid' : 'courseid';
        $courseid = (!empty($data[$coursekey])) ? $data[$coursekey] : null;
        $editoroptions = mod_virtualcoach\event\forms\create::build_editor_options($context);
        $formoptions = ['editoroptions' => $editoroptions, 'courseid' => $courseid];
        if ($courseid) {
            require_once($CFG->libdir . '/grouplib.php');
            $groupcoursedata = groups_get_course_data($courseid);
            if (!empty($groupcoursedata->groups)) {
                $formoptions['groups'] = [];
                foreach ($groupcoursedata->groups as $groupid => $groupdata) {
                    $formoptions['groups'][$groupid] = $groupdata->name;
                }
            }
        }

        if (!empty($data['id'])) {
            $eventid = clean_param($data['id'], PARAM_INT);
            $legacyevent = calendar_event::load($eventid);
            $legacyevent->count_repeats();
            $formoptions['event'] = $legacyevent;

            // TODO UPDATE
            $mform = new mod_virtualcoach\event\forms\create(null, $formoptions, 'post', '', null, true, $data);
        } else {
            $legacyevent = null;
            $mform = new mod_virtualcoach\event\forms\create(null, $formoptions, 'post', '', null, true, $data);
        }

        if ($validateddata = $mform->get_data()) {
            $formmapper = new create_update_form_mapper();
            $properties = $formmapper->from_data_to_event_properties($validateddata);

            if (is_null($legacyevent)) {
                $legacyevent = new \calendar_event($properties);
                // Need to do this in order to initialise the description
                // property which then triggers the update function below
                // to set the appropriate default properties on the event.
                $properties = $legacyevent->properties(true);
            }

            /*if (!calendar_edit_event_allowed($legacyevent, true)) {
                print_error('nopermissiontoupdatecalendar');
            }*/

            $legacyevent->update($properties);
            $eventcontext = $legacyevent->context;

            file_remove_editor_orphaned_files($validateddata->description);

            // Take any files added to the description draft file area and
            // convert them into the proper event description file area. Also
            // parse the description text and replace the URLs to the draft files
            // with the @@PLUGIN_FILE@@ placeholder to be persisted in the DB.
            $description = file_save_draft_area_files(
                $validateddata->description['itemid'],
                $eventcontext->id,
                'calendar',
                'event_description',
                $legacyevent->id,
                mod_virtualcoach\event\forms\create::build_editor_options($eventcontext),
                $validateddata->description['text']
            );

            // If draft files were found then we need to save the new
            // description value.
            if ($description != $validateddata->description['text']) {
                $properties->id = $legacyevent->id;
                $properties->description = $description;
                $legacyevent->update($properties);
            }

            $eventmapper = event_container::get_event_mapper();
            $event = $eventmapper->from_legacy_event_to_event($legacyevent);
            $cache = new events_related_objects_cache([$event]);
            $relatedobjects = [
                'context' => $cache->get_context($event),
                'course' => $cache->get_course($event),
            ];
            $exporter = new event_exporter($event, $relatedobjects);
            $renderer = $PAGE->get_renderer('core_calendar');

            return [ 'event' => $exporter->export($renderer) ];
        } else {
            return [ 'validationerror' => true ];
        }
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description.
     */
    public static function  submit_create_update_form_returns() {
        $eventstructure = event_exporter::get_read_structure();
        $eventstructure->required = VALUE_OPTIONAL;

        return new external_single_structure(
            array(
                'event' => $eventstructure,
                'validationerror' => new external_value(PARAM_BOOL, 'Invalid form data', VALUE_DEFAULT, false),
            )
        );
    }

    /**
     * Get data for the weekly calendar view.
     *
     * @param int $year The year to be shown
     * @param int $month The month to be shown
     * @param int $day The day to be shown
     * @param int $course_id The course to be included
     * @param int $category_id The category to be included
     * @param bool $include_navigation Whether to include navigation
     * @param bool $mini Whether to return the mini month view or not
     * @param int $coachId The id of the coach user assign
     * @param int $moduleId The Id of the module in course
     * @return  array
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     * @throws Exception
     */
    public static function get_calendar_weekly_view($year, $month, $day, $course_id, $category_id, $include_navigation, $mini, $coachId, $moduleId) {
        global $USER, $PAGE;
        require_once(__DIR__.'/calendarlib.php');

        // Parameter validation.
        $params = self::validate_parameters(self::get_calendar_weekly_view_parameters(), [
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'courseId' => $course_id,
            'categoryId' => $category_id,
            'includeNavigation' => $include_navigation,
            'mini' => $mini,
            'coachId' => $coachId,
            'moduleId' => $moduleId,
        ]);

        $context = context_user::instance($USER->id);
        self::validate_context($context);
        $PAGE->set_url('/calendar/');

        $type = type_factory::get_calendar_instance();

        $time = $type->convert_to_timestamp($params['year'], $params['month'], $params['day']);
        $calendar = calendar_information::create($time, $params['courseId'], $params['categoryId']);
        self::validate_context($calendar->context);

        $view = 'week';
        list($data) = calendar_get_week_view($calendar, $view, $params['courseId'], $params['coachId'], $params['moduleId'], $params['includeNavigation']);

        return $data;
    }

    /**
     * Returns description of week parameters.
     *
     * @return external_function_parameters
     */
    public static function get_calendar_weekly_view_parameters() {
        return new external_function_parameters(
            [
                'year' => new external_value(PARAM_INT, 'Year to be viewed', VALUE_REQUIRED),
                'month' => new external_value(PARAM_INT, 'Month to be viewed', VALUE_REQUIRED),
                'day' => new external_value(PARAM_INT, 'Day to be viewed', VALUE_REQUIRED),
                'courseId' => new external_value(PARAM_INT, 'Course being viewed', VALUE_DEFAULT, SITEID, NULL_ALLOWED),
                'categoryId' => new external_value(PARAM_INT, 'Category being viewed', VALUE_DEFAULT, null, NULL_ALLOWED),
                'includeNavigation' => new external_value(PARAM_BOOL, 'Whether to show course navigation', VALUE_DEFAULT, true,NULL_ALLOWED),
                'mini' => new external_value(PARAM_BOOL, 'Whether to return the mini month view or not', VALUE_DEFAULT, false, NULL_ALLOWED),
                'coachId' => new external_value(PARAM_INT, 'Whether to return the coachId', VALUE_DEFAULT, 0, NULL_ALLOWED),
                'moduleId' => new external_value(PARAM_INT, 'Whether to return the moduleId', VALUE_DEFAULT, 0, NULL_ALLOWED),
            ]
        );
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     */
    public static function get_calendar_weekly_view_returns() {
        $result =  month_exporter::get_read_structure();

        $result->keys['hours'] = new external_multiple_structure(week_exporter::get_read_structure(), 'hours');
        $result->keys['moduleId'] = new external_value(PARAM_INT, 'Module ID');
        $result->keys['coachId'] = new external_value(PARAM_INT, 'Coach ID');
        $result->keys['coachAccessUrl'] = new external_value(PARAM_TEXT, 'Coach Access URL');
        $result->keys['daynames']->content->keys['mday'] = new external_value(PARAM_INT, 'Mon Day');
        $result->keys['hours']->content->keys['hour'] = new external_value(PARAM_TEXT, 'Hour');
        $result->keys['hours']->content->keys['days']->content->keys['isbefore'] = new external_value(PARAM_BOOL, 'Is Before');
        //$result->keys['hours']->content->keys['days']->content->keys['events']->content->keys['isbefore'] = new external_value(PARAM_BOOL, 'Is Before');
        return $result;
    }

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function get_calendar_event_by_id_parameters() {
        return new external_function_parameters(
            array(
                'eventid' => new external_value(PARAM_INT, 'The event id to be retrieved'),
            )
        );
    }

    /**
     * Get calendar event by id.
     *
     * @param int $eventid The calendar event id to be retrieved.
     * @return array Array of event details
     */
    public static function get_calendar_event_by_id($eventid) {
        global $DB, $CFG, $PAGE, $USER;
        require_once($CFG->dirroot."/calendar/lib.php");

        $params = self::validate_parameters(self::get_calendar_event_by_id_parameters(), ['eventid' => $eventid]);
        $context = \context_user::instance($USER->id);

        self::validate_context($context);
        $warnings = array();

        $legacyevent = calendar_event::load($eventid);
        // Must check we can see this event.
        if (!calendar_view_event_allowed($legacyevent)) {
            // We can't return a warning in this case because the event is not optional.
            // We don't know the context for the event and it's not worth loading it.
            $syscontext = context_system::instance();
            throw new \required_capability_exception($syscontext, 'moodle/course:view', 'nopermission', '');
        }

        $legacyevent->count_repeats();

        $eventmapper = event_container::get_event_mapper();
        $event = $eventmapper->from_legacy_event_to_event($legacyevent);

        $cache = new events_related_objects_cache([$event]);
        $relatedobjects = [
            'context' => $cache->get_context($event),
            'course' => $cache->get_course($event),
        ];

        $exporter = new event_exporter($event, $relatedobjects);
        $renderer = $PAGE->get_renderer('core_calendar');

        // Customise event export data
        $eventExport = $exporter->export($renderer);
        $eventExport->name = get_string('coach_booking', 'virtualcoach');
        $eventExport->canedit = $eventExport->candelete = true;
        $eventExport->isactionevent = false;
        $coach = $DB->get_record('coach', ['id' => $eventExport->location], '*', IGNORE_MISSING);
        if (isset($coach->pool)) {
            $eventExport->location = $coach->pool;
        }

        return array('event' => $eventExport, 'warnings' => $warnings);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_calendar_event_by_id_returns() {
        $eventstructure = event_exporter::get_read_structure();

        return new external_single_structure(array(
                'event' => $eventstructure,
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.5
     */
    public static function delete_calendar_events_parameters() {
        return new external_function_parameters(
            array('events' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'eventid' => new external_value(PARAM_INT, 'Event ID', VALUE_REQUIRED, '', NULL_NOT_ALLOWED),
                        'repeat'  => new external_value(PARAM_BOOL, 'Delete comeplete series if repeated event')
                    ), 'List of events to delete'
                )
            )
            )
        );
    }

    /**
     * Delete Calendar events
     *
     * @param array $eventids A list of event ids with repeat flag to delete
     * @return null
     * @since Moodle 2.5
     */
    public static function delete_calendar_events($events) {
        global $CFG, $DB;
        require_once($CFG->dirroot."/calendar/lib.php");

        // Parameter validation.
        $params = self::validate_parameters(self:: delete_calendar_events_parameters(), array('events' => $events));

        $transaction = $DB->start_delegated_transaction();

        foreach ($params['events'] as $event) {
            $eventobj = calendar_event::load($event['eventid']);

            // Let's check if the user is allowed to delete an event.

            // TODO Verificar los permisos de eliminación permitidos para cada usuario
            /*if (!calendar_delete_event_allowed($eventobj)) {
                throw new moodle_exception('nopermissions', 'error', '', get_string('deleteevent', 'calendar'));
            }*/
            // Time to do the magic.
            $eventobj->delete($event['repeat']);
        }

        // Everything done smoothly, let's commit.
        $transaction->allow_commit();

        return null;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.5
     */
    public static function  delete_calendar_events_returns() {
        return null;
    }
}
