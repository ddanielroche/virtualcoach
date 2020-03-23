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

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Module instance settings form.
 *
 * @package    mod_virtualcoach
 * @copyright  2019 Dany Daniel Roche <ddanielroche@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class login_form extends moodleform {
    //Add elements to form

    /**
     * @throws coding_exception
     */
    public function definition() {
        global $USER;

        $mform = $this->_form; // Don't forget the underscore!

        $mform->addElement('text', 'username', get_string('username'), 'readonly'); // Add elements to your form
        $mform->setType('username', PARAM_NOTAGS);                   //Set type of element
        $mform->setDefault('username', $USER->username);        //Default value

        $mform->addElement('password', 'password', get_string('password')); // Add elements to your form
        $mform->setType('password', PARAM_NOTAGS);
        $mform->addRule('password', get_string('error'), 'required', 'extraruledata', 'client', true, false);
        //$mform->registerRule('checkpassword', 'check', 'required', 'extraruledata', 'client', true, false);

        $this->add_action_buttons(false, get_string('login'));
    }
    //Custom validation should be added here
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $errorcode = 0;
        if (!authenticate_user_login($data['username'], $data['password'], false, $errorcode)) {
            switch ($errorcode) {
                case AUTH_LOGIN_FAILED:
                    $errors['password'] = 'Contraseña Incorrecta';
                    break;
                default:
                    $errors['password'] = 'Error de Inicio de sesión';
            }
        }

        return $errors;
    }

    /**
     * Create ticket from user
     *
     * @param uds $udsinstance
     * @param string $username
     * @param string $idpool
     * @param string $fullname
     * @return string
     * @throws coding_exception
     */
    public function uds_user_tickets_create($udsinstance, $username, $password, $idpool, $fullname) {

        global $CFG, $COURSE;

        $urlpath = '/tickets/create';

        $postfields = array(
            "username" => "$username",
            "password" => "$password",
            "authSmallName" => $udsinstance->get_authsmallnameforactivity(),
            "groups" => $udsinstance->get_groupname(),
            "servicePool" => "$idpool",
            "realname" => "$fullname",
            "force" => "1");

        $jsonresponse = $udsinstance->rest_request($udsinstance::PUT,
            $urlpath, $postfields);
        //print_r($jsonresponse);exit;
        if (empty($jsonresponse->result)) {
            $feedback = new stdClass();
            $pool = uds_servicespools($udsinstance, $idpool);
            $feedback->poolname = $pool->name;
            $feedback->username = "$username";
            notice(get_string('virtualpcerrorcreatingticketid', 'virtualpc', $feedback),
                $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id);
            return null;
        } else {
            return $jsonresponse->result;
        }
    }
}
