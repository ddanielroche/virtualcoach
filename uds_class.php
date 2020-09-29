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
 * Connect to UDS Server by rest request and return json
 *
 * @package    mod_virtualpc
 * @copyright  2014 Universidad de Málaga - Enseñanza Virtual y Laboratorios Tecnólogicos
 * @author     Antonio Godino (asesoramiento [at] evlt [dot] uma [dot] es)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/../virtualpc/uds_class.php');

/**
 * Class REST client for OpenUDS.org
 *
 * @package    mod_virtualpc
 * @copyright  2014 Universidad de Málaga - Enseñanza Virtual y Laboratorios Tecnólogicos
 * @author     Antonio Godino (asesoramiento [at] evlt [dot] uma [dot] es)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class my_uds extends uds {
    /**
     * Rest request is DELETE
     */
    const DELETE = 'DELETE';

    /**
     * Rest request
     *
     * @param string $type
     * @param string $urlpath
     * @param string $postfields
     * @return \stdClass
     */
    public function rest_request ( $type, $urlpath, $postfields ) {

        global $USER, $CFG, $COURSE;

        $connection = curl_init();

        if (preg_match('/^https/i', get_config('virtualpc', 'serverurl')) or ($this->_port == 0)) {

            if (preg_match('/^https/i', $this->get_server() )) {
                curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($connection, CURLOPT_CUSTOMREQUEST, self::POST);
            }

            $this->_requesturl = $this->get_server().
                             '/rest/' . $urlpath;
        } else {

            $this->_requesturl = $this->get_server() .':' .
                             $this->get_port() .
                             '/rest/' . $urlpath;
        }

        switch ($type) {
            case self::POST:
                curl_setopt($connection, CURLOPT_POST, true);
                if (!empty($postfields)) {
                    curl_setopt($connection, CURLOPT_POSTFIELDS, json_encode($postfields));
                }
                break;
            case self::PUT:
                if (!empty($postfields)) {
                    curl_setopt($connection, CURLOPT_POSTFIELDS, json_encode($postfields));
                }
                curl_setopt($connection, CURLOPT_CUSTOMREQUEST, self::PUT);
                break;
            case self::GET:
                if (!empty($postfields)) {
                    curl_setopt($connection, CURLOPT_POSTFIELDS, json_encode($postfields));
                }
                curl_setopt($connection, CURLOPT_CUSTOMREQUEST, self::GET);
                break;
            case self::DELETE:
                if (!empty($postfields)) {
                    curl_setopt($connection, CURLOPT_POSTFIELDS, json_encode($postfields));
                }
                curl_setopt($connection, CURLOPT_CUSTOMREQUEST, self::DELETE);
                break;
        }

        curl_setopt($connection, CURLOPT_URL, $this->_requesturl);

        curl_setopt($connection, CURLOPT_HTTPHEADER, $this->get_headers());
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($connection, CURLOPT_VERBOSE, true);

        if (isset($CFG->proxyhost)) {
            curl_setopt($connection, CURLOPT_PROXY, $CFG->proxyhost);
            if (isset($CFG->proxyport)) {
                curl_setopt($connection, CURLOPT_PROXYPORT, $CFG->proxyport);
            }
        }

        $curlresponse = curl_exec($connection);
        $status = curl_getinfo($connection, CURLINFO_HTTP_CODE);

        switch ($status) {
            case self::HTTP_OK:
            case self::HTTP_CREATED:
            case self::HTTP_ACEPTED:
                $jsonresponse = json_decode($curlresponse);
                break;
            default:
                if (is_siteadmin($USER->id)) {
                    $msg = sprintf("Curl error (#%d): %s<br>\n HTTP_CODE error:%d", curl_errno($connection),
                            htmlspecialchars(curl_error($connection)), $status);

                    curl_close($connection);

                    debugging($msg, DEBUG_DEVELOPER);
                    if (isset($this->_server) and empty($this->_server)) {
                        notice (get_string('virtualpcresterror', 'virtualpc', $urlpath),
                            $CFG->wwwroot.'/admin/settings.php?section=modsettingvirtualpc');
                    }
                }
                $feedback = new stdClass();
                $feedback->url = $this->get_server();
                notice (get_string('errorconnection', 'virtualpc', $feedback),
                        $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id);
                break;
        }

        curl_close($connection);

        return $jsonresponse;
    }
}

/**
 * Login UDS and set valid token
 *
 * @return stdClass
 */
function my_uds_login() {

    global $CFG, $COURSE, $USER;

    $udsinstance = new my_uds();

    $postfields = array('authSmallName' => $udsinstance->get_authsmallname(),
        'username' => $udsinstance->get_username(),
        'password' => $udsinstance->get_password());

    $urlpath = 'auth/login' . $udsinstance->get_lang();

    $jsonresponse = $udsinstance->rest_request($udsinstance::POST, $urlpath,
        $postfields );

    if ($jsonresponse->result == $udsinstance::OK) {

        $udsinstance->set_token($jsonresponse->token);

        $udsinstance->add_header('X-Auth-Token: ' . $jsonresponse->token);

        return $udsinstance;

    } else {
        if (is_siteadmin($USER->id)) {
            notice(get_string('virtualpcresterror', 'virtualpc', $urlpath),
                $CFG->wwwroot.'/admin/settings.php?section=modsettingvirtualpc');
        } else {
            $feedback = new stdClass();
            $feedback->url = $udsinstance->get_server();
            notice (get_string('errorconnection', 'virtualpc', $feedback),
                $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id);
        }
    }

}

/**
 * Create ticket from user
 *
 * @param uds $udsinstance
 * @param string $username
 * @param $password
 * @param string $idpool
 * @param $transport
 * @param string $fullname
 * @return string
 * @throws coding_exception
 */
function uds_user_tickets_create($udsinstance, $username, $password, $idpool, $transport, $fullname) {

    global $CFG, $COURSE;

    $urlpath = '/tickets/create';

    $postfields = array(
        "username" => "$username",
        "password" => "$password",
        "authSmallName" => $udsinstance->get_authsmallnameforactivity(),
        "groups" => $udsinstance->get_groupname(),
        "servicePool" => "$idpool",
        "transport" => $transport,
        "realname" => "$fullname",
        "force" => "1",
    );

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

/**
 * Delete users if last_access is greater than 10 minutes on users authenticator
 *
 * @param my_uds $udsinstance
 */
function delete_last_login_users($udsinstance)
{
    $urlpath = '/authenticators/overview';
    // Find all authenticators
    $authenticators = $udsinstance->rest_request($udsinstance::GET, $urlpath, '');

    foreach ($authenticators as $authenticator) {
        // Locate authenticator label where users will be created.
        if ($authenticator->small_name == $udsinstance->get_authsmallnameforactivity()) {
            $urlpath = "/authenticators/$authenticator->id/users/overview";
            // find all users for this authenticator
            $users = $udsinstance->rest_request($udsinstance::GET, $urlpath, '');
            foreach ($users as $user) {
                // Delete users if last_access is greater than 10 minutes
                if ($user->last_access < time() - 600) {
                    $urlpath = "/authenticators/$authenticator->id/users/$user->id";
                    $udsinstance->rest_request($udsinstance::DELETE, $urlpath, '');
                }
            }
            break;
        }
    }
}