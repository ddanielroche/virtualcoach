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

// Code copy from Virtual PC View
require_once(dirname(__FILE__) .'/../virtualpc/lib.php');
require_once(dirname(__FILE__).'/../virtualpc/locallib.php');
require_once(dirname(__FILE__).'/../virtualpc/uds_class.php');

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
$view = optional_param('view', 'month', PARAM_ALPHA);
$time = optional_param('time', 0, PARAM_INT);

$url = new moodle_url('/mod/virtualcoach/view.php');

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
$PAGE->set_title("$course->shortname: $strcalendar: $pagetitle");
$PAGE->set_heading($COURSE->fullname);

$renderer = $PAGE->get_renderer('core_calendar');
$calendar->add_sidecalendar_blocks($renderer, true, $view);

echo $OUTPUT->header();
echo '<link rel="stylesheet" type="text/css"href="mystyle.css">';
echo $renderer->start_layout();
echo html_writer::start_tag('div', array('class'=>'heightcontainer'));
//echo $OUTPUT->heading(get_string('calendar', 'calendar'));

// TODO review course param array($USER->id, 2)

$broker = uds_login();
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

echo $OUTPUT->block($bc, BLOCK_POS_LEFT);
//echo enrolment_observers::get_coach_link($USER, $courseid);

list($data, $template) = calendar_get_view($calendar, $view);
echo $renderer->render_from_template($template, $data);

echo html_writer::end_tag('div');

list($data, $template) = calendar_get_footer_options($calendar);
echo $renderer->render_from_template($template, $data);

echo $renderer->complete_layout();
echo $OUTPUT->footer();

?>

<script type="text/javascript">
    require (['jquery'], function ($) {
        $(document).ready(function() {
            document.body.addEventListener('DOMNodeInserted', function( event ) {
                if (event.target.classList && event.target.classList.contains('moreless-actions')) {
                    $('.fitem.moreless-actions').css('display', 'none');
                }

                /*if (event.target.classList && event.target.classList.contains('editor_atto_content')) {
                    console.log(event.target);
                    var x = document.createElement("P");
                    var t = document.createTextNode("Reserva de horas en entrenador.");
                    x.appendChild(t);
                    $('#id_descriptioneditable')[0].appendChild(x);
                }*/

                if (event.target.tagName == "FORM") {
                    $('#fitem_id_name').css('display', 'none');
                    $('#id_name').val('<?= $USER->firstname . ' ' . $USER->lastname ?>');


                    $('#fitem_id_eventtype').css('display', 'none');
                    $('#fitem_id_courseid').css('display', 'none');
                    //$('#fitem_id_description').css('display', 'none');
                    $('#fitem_id_location').css('display', 'none');
                    $('#fitem_id_repeats').css('display', 'none');
                    $('.fdescription.required').css('display', 'none');
                    $('#fgroup_id_durationgroup')[0].classList.remove('advanced');

                    $('#id_duration_0').prop('disabled', true);
                    $('#id_duration_2').click();
                    $('#id_timedurationminutes').val(60);
                }
            }, false);
        });
    });
</script>
