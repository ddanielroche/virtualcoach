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
 * Enrolment observers class.
 *
 * @package     mod_virtualcoach
 * @copyright   2019 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_virtualcoach;

global $CFG;

use auth_plugin_ldap;
use core_text;
use dml_exception;

require_once($CFG->libdir.'/ldaplib.php');

/**
 * Enrolment observers class.
 *
 * @package     mod_virtualcoach
 * @copyright   2019 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class calendar {
    const ALLOW_ACCESS = 1;
    const SEND_MSG = 2;
    const DENY_ACCESS = 3;

    /**
     * @param int $allow
     * @return bool
     * @throws dml_exception
     */
    public static function allow_coach_access($allow) {
        if (!is_enabled_auth('ldap')) {
            return false;
        }
        /** @var auth_plugin_ldap $auth */
        $auth = get_auth_plugin('ldap');
        if (empty($auth->config->memberattribute)) {
            return false;
        }

        // TODO implement as a service
        $logout =  __DIR__ . '/../cli/logoff.ps1';
        $events = static::readEvents($allow);
        $ldapConnection = $auth->ldap_connect();
        foreach ($events as $event) {
            $extusername = core_text::convert($event->username, 'utf-8', $auth->config->ldapencoding);
            if(!($userid = $auth->ldap_find_userdn($ldapConnection, $extusername))) {
                continue;
            }
            echo "\nldap_isgroupmember - ";
            $isgroupmember = ldap_isgroupmember($ldapConnection, $userid, [$event->group], $auth->config->memberattribute);
            if ($allow == self::ALLOW_ACCESS && !$isgroupmember) {
                echo "ldap_mod_add $event->group $event->username\n\n";
                ldap_mod_add($ldapConnection, $event->group, [$auth->config->memberattribute => $userid]);
            } elseif ($allow == self::SEND_MSG && $isgroupmember) {
                echo "ldap_mod_del $event->group $event->username\n\n";
                ldap_mod_del( $ldapConnection, $event->group, [$auth->config->memberattribute => $userid]);
                // TODO implement as a service
                $cmd = "powershell msg $extusername /server:$event->computer !Su sesión está a punto de finalizar, dispone de 5 minutos para guardar su trabajo!";
                echo $cmd . "\n";
                exec($cmd);
            } elseif ($allow == self::DENY_ACCESS) {
                // TODO implement as a service
                $cmd = "powershell $logout -server $event->computer -username $extusername";
                echo $cmd . "\n";
                exec($cmd);
            }
        }
        $auth->ldap_close();
        return true;
    }

    /**
     * List event of users that auth type is 'ldap', coach assigned in a course and:
     *
     * time between 0 and 10 => deny access
     * time between start and end => allow access
     *
     * @param bool $allow
     * @return array
     * @throws dml_exception
     */
    public static function readEvents($allow)
    {
        global $DB;

        $location = $DB->sql_cast_char2int('e.location');
        $locationIsNotEmpty = $DB->sql_isnotempty('event', 'e.location', true, true);

        $time = time();
        $sql = "SELECT  e.id, c.group, u.username, c.computer
FROM {event} e
INNER JOIN {user} u ON u.id = e.userid
INNER JOIN {coach} c ON c.id = $location
WHERE u.auth = 'ldap' AND e.eventtype = 'virtualcoach' AND $locationIsNotEmpty AND";
        if ($allow == self::ALLOW_ACCESS) {
            $sql .= " $time BETWEEN e.timestart AND e.timestart + e.timeduration\n";
        } elseif ($allow == self::SEND_MSG) {
            $sql .= " (e.timestart + e.timeduration - $time) BETWEEN 0 and 300\n";
        } elseif ($allow == self::DENY_ACCESS) {
            $sql .= " (e.timestart + e.timeduration - $time) BETWEEN -120 and 0\n";
        }
        $sql .= "ORDER BY e.id\n";
        //echo $sql;

        return $DB->get_records_sql($sql);
    }
}