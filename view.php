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
 * Prints an instance of mod_virtualcoach.
 *
 * @package     mod_virtualcoach
 * @copyright   2019 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_virtualcoach\enrolment_observers;

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/calendar/lib.php');
require_once(__DIR__.'/calendarlib.php');

// Code copy from Virtual PC View
require_once(dirname(__FILE__) .'/../virtualpc/lib.php');
require_once(dirname(__FILE__).'/../virtualpc/locallib.php');
require_once(__DIR__.'/uds_class.php');

// Course_module ID, or
$id = optional_param('id', 0, PARAM_INT);

// ... module instance id.
$v  = optional_param('v', 0, PARAM_INT);

if ($id) {
    $cm             = get_coursemodule_from_id('virtualcoach', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('virtualcoach', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($v) {
    $moduleinstance = $DB->get_record('virtualcoach', array('id' => $n), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('virtualcoach', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', 'mod_virtualcoach'));
}

$categoryid = optional_param('category', null, PARAM_INT);
// TODO Review courseid param
$courseid = optional_param('course', $course->id, PARAM_INT);
$view = optional_param('view', 'week', PARAM_ALPHA);
$time = optional_param('time', 0, PARAM_INT);

$url = new moodle_url('/mod/virtualcoach/view.php');
$url->param('id', $id);

if (empty($time)) {
    $time = time();
}

if ($courseid != SITEID) {
    $url->param('course', $courseid);
}

if ($categoryid) {
    $url->param('categoryid', $categoryid);
}

if ($view !== 'upcoming') {
    $time = usergetmidnight($time);
    $url->param('view', $view);
}

$url->param('time', $time);

$PAGE->set_url($url);
$PAGE->add_body_class('path-calendar');

$course = get_course($courseid);

if ($courseid != SITEID && !empty($courseid)) {
    navigation_node::override_active_url(new moodle_url('/course/view.php', array('id' => $course->id)));
} else if (!empty($categoryid)) {
    $PAGE->set_category_by_id($categoryid);
    navigation_node::override_active_url(new moodle_url('/course/index.php', array('categoryid' => $categoryid)));
} else {
    $PAGE->set_context(context_system::instance());
}

require_login($course, false);

$calendar = calendar_information::create($time, $courseid, $categoryid);

$pagetitle = '';

$strcalendar = get_string('calendar', 'calendar');

switch($view) {
    case 'day':
        $PAGE->navbar->add(userdate($time, get_string('strftimedate')));
        $pagetitle = get_string('dayviewtitle', 'calendar', userdate($time, get_string('strftimedaydate')));
        break;
    case 'month':
        $PAGE->navbar->add(userdate($time, get_string('strftimemonthyear')));
        $pagetitle = get_string('detailedmonthviewtitle', 'calendar', userdate($time, get_string('strftimemonthyear')));
        break;
    case 'upcoming':
        $pagetitle = get_string('upcomingevents', 'calendar');
        break;
}

// Print title and header
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add($moduleinstance->name, $url);
$PAGE->set_title("$course->shortname: $moduleinstance->name");
$PAGE->set_heading($COURSE->fullname);

$renderer = $PAGE->get_renderer('core_calendar');
//$calendar->add_sidecalendar_blocks($renderer, true, $view);

echo $OUTPUT->header();
echo '<link rel="stylesheet" type="text/css"href="mystyle.css">';
echo $renderer->start_layout();
echo html_writer::start_tag('div', array('class'=>'heightcontainer'));
//echo $OUTPUT->heading(get_string('calendar', 'calendar'));

// TODO review course param array($USER->id, 2)

$broker = my_uds_login();
$pool = uds_servicespools_byname($broker, enrolment_observers::get_pool_name($USER, $courseid, $moduleinstance));

if ($pool) {
    $printaccessbutton = true;
    $virtualpc = new stdClass();
    $virtualpc->name = $virtualpc->poolname = $pool->name;
    $virtualpc->thumb = $pool->thumb;
}

uds_logout($broker);

$bc = new block_contents();
$rendererVPC = $PAGE->get_renderer('mod_virtualcoach');
$bc->content = $rendererVPC->display_virtualpc_detail($virtualpc, $id, $printaccessbutton);
$bc->title = get_string('modulename', 'virtualpc');

//echo $OUTPUT->block($bc, BLOCK_POS_LEFT);
$CA = enrolment_observers::get_coach_assign($USER->id, $courseid);
list($data, $template) = calendar_get_week_view($calendar, $view, $courseid, $CA->coach, $id);
echo $renderer->render_from_template($template, $data);

echo html_writer::end_tag('div');

//list($data, $template) = calendar_get_footer_options($calendar);
//echo $renderer->render_from_template($template, $data);

echo $renderer->complete_layout();
echo $OUTPUT->footer();

?>

<script type="text/javascript">

    /**
     * A module to handle CRUD operations within the UI.
     *
     * @module     core_calendar/crud
     * @package    core_calendar
     * @copyright  2017 Andrew Nicols <andrew@nicols.co.uk>
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    require(['jquery', 'core/ajax', 'core/fragment', 'core_calendar/repository', 'core_calendar/modal_event_form'], function($, Ajax, Fragment, CalendarRepository, ModalEventForm) {
        /**
         * Submit the form data for the event form.
         *
         * @method submitCreateUpdateForm
         * @param {string} formdata The URL encoded values from the form
         * @return {promise} Resolved with the new or edited event
         */
        CalendarRepository.submitCreateUpdateForm = function(formdata) {
            var request = {
                methodname: 'mod_virtualcoach_submit_create_update_form',
                args: {
                    formdata: formdata
                }
            };

            return Ajax.call([request])[0];
        };

        /**
         * Get a calendar event by id.
         *
         * @method getEventById
         * @param {int} eventId The event id.
         * @return {promise} Resolved with requested calendar event
         */
        CalendarRepository.getEventById = function(eventId) {

            var request = {
                methodname: 'mod_virtualcoach_get_calendar_event_by_id',
                args: {
                    eventid: eventId
                }
            };

            return Ajax.call([request])[0];
        };

        /**
         * Delete a calendar event.
         *
         * @method deleteEvent
         * @param {int} eventId The event id.
         * @param {bool} deleteSeries Whether to delete all events in the series
         * @return {promise} Resolved with requested calendar event
         */
         CalendarRepository.deleteEvent = function(eventId, deleteSeries) {
            if (typeof deleteSeries === 'undefined') {
                deleteSeries = false;
            }
            var request = {
                methodname: 'mod_virtualcoach_delete_calendar_events',
                args: {
                    events: [{
                        eventid: eventId,
                        repeat: deleteSeries,
                    }]
                }
            };

            return Ajax.call([request])[0];
        };

        /**
         * Send a request to the server to get the event_form in a fragment
         * and render the result in the body of the modal.
         *
         * If serialised form data is provided then it will be sent in the
         * request to the server to have the form rendered with the data. This
         * is used when the form had a server side error and we need the server
         * to re-render it for us to display the error to the user.
         *
         * @method reloadBodyContent
         * @param {string} formData The serialised form data
         * @return {object} A promise resolved with the fragment html and js from
         */
        ModalEventForm.prototype.reloadBodyContent = function(formData) {
            if (this.reloadingBody) {
                return this.bodyPromise;
            }

            this.reloadingBody = true;
            this.disableButtons();

            var args = {};

            if (this.hasEventId()) {
                args.eventid = this.getEventId();
            }

            if (this.hasStartTime()) {
                args.starttime = this.getStartTime();
            }

            if (this.hasCourseId()) {
                args.courseid = this.getCourseId();
            }

            if (this.hasCategoryId()) {
                args.categoryid = this.getCategoryId();
            }

            if (typeof formData !== 'undefined') {
                args.formdata = formData;
            }

            args.location = $('.calendarwrapper').data('coach-id');
            args.moduleid = $('.calendarwrapper').data('module-id');
            args.courseid = $('.calendarwrapper').data('courseid');

            this.bodyPromise = Fragment.loadFragment('mod_virtualcoach', 'event_form', this.getContextId(), args);

            this.setBody(this.bodyPromise);

            this.bodyPromise.then(function() {
                this.enableButtons();
                return;
            }.bind(this))
                .fail(Notification.exception)
                .always(function() {
                    this.reloadingBody = false;
                    return;
                }.bind(this))
                .fail(Notification.exception);

            return this.bodyPromise;
        };
    });

    var vc_vars = {};

    require (['jquery'], function ($) {
        function getSelectos() {
            vc_vars.navbar = $('.fixed-top.navbar')[0];
            vc_vars.table = $('.calendarmonth.calendartable')[0];
            vc_vars.thead = document.querySelector("table thead");
            vc_vars.mq = window.matchMedia("(min-width: 780px)");
        }

        function getVars() {
            // Ancho de la tabla
            vc_vars.tableWidth = vc_vars.table.offsetWidth;
            // Posición superior de la tabla en relación con la ventana del navegador.
            vc_vars.tableOffsetTop = vc_vars.table.getBoundingClientRect().top;
            // Altura del encabezado
            vc_vars.theadHeight = vc_vars.thead.offsetHeight;
            vc_vars.navvarBotton = vc_vars.navbar.getBoundingClientRect().bottom;
        }

        $(document).ready(function() {
            getSelectos();
            getVars();
            scrollHandler();

            function scrollHandler() {
                    vc_vars.tableOffsetTop = vc_vars.table.getBoundingClientRect().top;
                    // 1 Obtener la cantidad de píxeles que un usuario se ha desplazado desde la parte superior de la ventana
                    let scrollY = window.pageYOffset + vc_vars.navvarBotton,
                    // 2 Obtener la posición superior de la última sección en relación con la ventana.
                        lastSectionOffsetTop = vc_vars.table.getBoundingClientRect().bottom - vc_vars.navvarBotton;
                    // 3 Comprobar si un usuario se ha desplazado más o igual a la posición superior inicial de la tabla.
                    //console.log(['3',  scrollY,vc_vars.tableOffsetTop]);
                    if (vc_vars.navvarBotton >= vc_vars.tableOffsetTop) {
                        // 4 Si eso ocurre, ajustamos el ancho de thead igual al ancho inicial de la tabla.
                        vc_vars.thead.style.width = `${vc_vars.tableWidth}px`;
                        // 5 A continuación, comprobamos si el valor resultante del paso 2 es mayor que la altura de vc_vars.thead.
                        //console.log(['5', lastSectionOffsetTop,vc_vars.theadHeight]);
                        if (lastSectionOffsetTop > vc_vars.theadHeight) {
                            // 6
                            vc_vars.thead.style.top = `${vc_vars.navvarBotton}px`;
                            vc_vars.thead.style.position = 'fixed';
                        } else {
                            // 7
                            vc_vars.thead.style.top = `calc(100% - ${vc_vars.theadHeight}px)`;
                            vc_vars.thead.style.position = 'absolute';
                        }
                    } else {
                        // 8
                        vc_vars.thead.style.width = "100%";
                        vc_vars.thead.style.top = "auto";
                        vc_vars.thead.style.position = null;
                    }
            }

            function resizeHandler() {
                getVars();
                scrollHandler();
            }

            window.addEventListener("scroll", scrollHandler);
            window.addEventListener("resize", resizeHandler);
            $(document).on(M.core.event.FILTER_CONTENT_UPDATED, getSelectos)
        });
    });
</script>
