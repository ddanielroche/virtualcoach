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
 * Plugin strings are defined here.
 *
 * @package     mod_virtualcoach
 * @category    string
 * @copyright   2019 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Entrenador Virtual';
$string['modulename'] = 'Entrenador Virtual';
$string['modulenameplural'] = 'Entrenadores Virtuales';
$string['modulename_help'] = 'El módulo de actividad Virtual Coach permite a los participantes reservar horas en entrenador y acceder via web o RDP';
$string['virtualcoachname'] = 'Nombre de actividad del entrenador virtual';
$string['virtualcoachname_help'] = 'Ingrese el nombre de la actividad de Entrenador Virtual que aparecerá en la página del curso.';
$string['virtualcoachsettings'] = 'Configuración de actividad de entrenador virtual';
$string['virtualcoachfieldset'] = 'Configuración Entrenador Virtual';
$string['allowcoachaccess'] = 'Permitir acceso al entrenador';
$string['denycoachaccess'] = 'Denegar acceso al entrenador';
$string['sendendofsessionmessage'] = 'Enviar mensaje de fin de sesión';
$string['pluginadministration'] = 'Entrenador Virtual';
$string['coachnotfound'] = 'El usuario no tiene entrenador asignado para este curso.';
$string['coachnotavailable'] = 'No hay entrenador disponible para asignar al usuario.';
$string['autoassign'] = 'Asignación automática';
$string['autoassign_help'] = 'Los usuarios serán asignados automáticamente de acuerdo a la disponibilidad de entrenadores.';
$string['max_daily_hours'] = 'Máximo de horas diarias';
$string['max_weekly_hours'] = 'Máximo de horas semanales';
$string['max_course_hours'] = 'Máximo de horas en curso';
$string['max_daily_hours_help'] = 'Número máximo de horas a consumir diariamente por usuario. Si el valor es cero, esta configuración no se evaluará.';
$string['max_weekly_hours_help'] = 'Número máximo de horas a consumir semanalmente por usuario. Si el valor es cero, esta configuración no se evaluará.';
$string['max_course_hours_help'] = 'Número máximo de horas a consumir por usuario en el curso. Si el valor es cero, esta configuración no se evaluará.';
$string['max_daily_hours_error'] = 'Ha superado ({$a->user_hours}) el máximo de horas ({$a->max_hours}) permitidas en un día.';
$string['max_weekly_hours_error'] = 'Ha superado ({$a->user_hours}) el máximo de horas ({$a->max_hours}) permitidas en una semana.';
$string['max_course_hours_error'] = 'Ha superado ({$a->user_hours}) el máximo de horas ({$a->max_hours}) permitidas para este curso.';
$string['max_days'] = 'Días Máximo';
$string['max_days_help'] = 'Máximo número de días a consumir por usuario.';
$string['default_coach_id'] = 'Entrenador por defecto';
$string['default_coach_id_help'] = 'Entrenador asignado por defecto';
$string['transport'] = 'Conexión';
$string['hour'] = 'Hora';
$string['weekly_coach_bookings'] = 'Reservas semanales en entrenador:';
$string['week_no'] = 'Semana {$a->week}';
$string['coach_access'] = 'Acceso al entrenador';
$string['reserved_other'] = 'Reservado por otro';
$string['reserved_my'] = 'Reservado por mi';
$string['coach_assignment'] = 'Asignasión de entrenadores';
$string['duration_hours'] = 'Duración en horas';
$string['coach_booking'] = 'Reserva de entrenador';
$string['busy_coach'] = 'El horario que intenta reservar ya está ocupado por otro usuario, por favor elija otro horario que esté disponible.';