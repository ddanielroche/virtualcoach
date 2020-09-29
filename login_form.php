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

        $transports = $this->transports();
        if ($transports) {
            $mform->addElement('select', 'transport', get_string('transport', 'mod_virtualcoach'), $transports);
        }

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
     * @param uds $udsinstance
     * @return array|bool
     * @throws coding_exception
     */
    public function transports()
    {
        $poolName = required_param('poolName', PARAM_TEXT);
        /** @var uds $udsinstance */
        $udsinstance = my_uds_login();
        $pool = uds_servicespools_byname($udsinstance, $poolName);
        if (!$pool->show_transports) {
            return false;
        }
        $transports = $udsinstance->rest_request($udsinstance::GET, "servicespools/$pool->id/transports/overview", '');
        $results = array();
        foreach ($transports as $transport) {
            $results[$transport->priority] = ['id' => $transport->id, 'name' => $transport->name];
        }
        ksort($results);
        foreach ($results as $id => $result) {
            unset($results[$id]);
            $results[$result['id']] = $result['name'];
        }
        return $results;
    }
}
