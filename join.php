<?php
// This file is part of Virtual PC module.
//
// Virtual PC  is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Virtual PC is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 *  Join page to a virtual pc machine
 *
 * @package    mod_virtualpc
 * @copyright  2014 Universidad de Málaga - Enseñanza Virtual y Laboratorios Tecnólogicos
 * @author     Antonio Godino (asesoramiento [at] evlt [dot] uma [dot] es)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(dirname(__FILE__)).'/virtualpc/uds_class.php');
require_once(dirname(dirname(__FILE__)).'/virtualpc/locallib.php');
require_once(__DIR__.'/login_form.php');

$id = required_param('id', PARAM_INT);
$sesskey = required_param('sesskey', PARAM_ALPHANUM);
$poolName = required_param('poolName', PARAM_TEXT);

if (!$cm = get_coursemodule_from_id('virtualcoach', $id)) {
    print_error('incorrectcourseid', 'virtualpc');
}

$cond = array('id' => $cm->course);
if (!$course = $DB->get_record('course', $cond)) {
    print_error('misconfiguredcourse', 'virtualpc');
}

/*$cond = array('id' => $cm->instance);
if (!$virtualpc = $DB->get_record('virtualpc', $cond)) {
    print_error('incorrectcoursemodule', 'virtualpc');
}*/

require_login ($course, true, $cm);

$context = context_module::instance($cm->id);

// Print the page header.
$url = new moodle_url('/course/join.php', array ('id' => $course->id));

$PAGE->set_url($url);

$PAGE->set_title(format_string($poolName));
$PAGE->set_heading($course->fullname);

$loginURL = new moodle_url('/mod/virtualcoach/join.php', array ('id' => $id, 'sesskey' => $sesskey, 'poolName' => $poolName));
$mform = new login_form($loginURL);

if ($arrayData = $mform->get_data()) {

    if (has_capability('mod/virtualpc:join', $context) && confirm_sesskey($sesskey)) {

        $broker = uds_login();

        $pool = uds_servicespools_byname($broker, $poolName);

        $ticketid = $mform->uds_user_tickets_create($broker, $USER->username, $arrayData->password, $pool->id, $arrayData->transport,
            $USER->firstname . " " . $USER->lastname);

        uds_logout($broker);

        if ($pool->id or $ticketid > 0) {

            $params = array(
                'context' => $context,
                'objectid' => $cm->id
            );
            /*$event = \mod_virtualpc\event\virtualpc_joined::create($params);
            $event->add_record_snapshot('virtualpc', $poolName);
            $event->trigger();*/

            if (preg_match('/^https/i', get_config('virtualpc', 'serverurl'))) {
                $target = get_config('virtualpc', 'serverurl') . '/tkauth/' . $ticketid;
            } else {
                $target = get_config('virtualpc', 'serverurl') . ':' .
                    get_config('virtualpc', 'serverport') . '/tkauth/' . $ticketid;
            }

            redirect($target);

        } else {
            $msg = get_string('idpoolnotfound', 'virtualpc', $pool->id);
        }

    } else {
        $msg = get_string('usernotenrolled', 'virtualpc');
    }

echo $OUTPUT->header();

notice ($msg, new moodle_url('/course/view.php', array('id' => $course->id)));

// Finish the page.
echo $OUTPUT->footer();

} else {
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}