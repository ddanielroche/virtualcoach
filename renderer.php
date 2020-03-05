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
 * This file contains a custom renderer class used by the Virtual PC module.
 *
 * @package    mod_virtualpc
 * @copyright  2014 Universidad de Málaga - Enseñanza Virtual y Laboratorios Tecnólogicos
 * @author     Antonio Godino (asesoramiento [at] evlt [dot] uma [dot] es)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * A custom renderer class that extends the plugin_renderer_base and is used by the virtualpc module.
 *
 * @package    mod_virtualpc
 * @copyright  2014 Universidad de Málaga - Enseñanza Virtual y Laboratorios Tecnólogicos
 * @author     Antonio Godino (asesoramiento [at] evlt [dot] uma [dot] es)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_virtualcoach_renderer extends plugin_renderer_base {
    /**
     * Returns HTML to display the virtualpc details
     *
     * @param instance $virtualpc
     * @param integer $cm
     * @param boolean $printaccessbutton
     * @return array
     */
    public function display_virtualpc_detail (&$virtualpc, $cm, $printaccessbutton) {

        global $CFG, $OUTPUT, $COURSE;

        if ($printaccessbutton) {

            $html = html_writer::start_tag('table', array('width' => '100%', 'align' => 'center'));

            $html .= html_writer::start_tag('tr');
            $html .= html_writer::start_tag ( 'td', array ( 'width' => '10%', 'valign' => 'middle', 'align' => 'left' ) );

            $param = array ( 'src' => $CFG->wwwroot.'/mod/virtualpc/pix/virtualpc.jpg', 'alt' => 'Virtual PC',
                             'height' => '55', 'width' => '55' );
            $html .= html_writer::empty_tag ( 'img', $param );
            $html .= html_writer::end_tag ( 'td' );

            $html .= html_writer::start_tag ( 'td', array ( 'width' => '95%', 'valign' => 'middle', 'align' => 'left' ) );
            $html .= html_writer::start_tag ( 'h2');
            $html .= format_string($virtualpc->name);
            $html .= html_writer::end_tag ( 'h2' );
            $html .= html_writer::end_tag ( 'td' );

            $html .= html_writer::end_tag ( 'tr' );
            $html .= html_writer::end_tag ( 'table' );

            $html .= html_writer::start_tag ( 'table', array('width' => '80%', 'align' => 'center'));

            /*if ($virtualpc->intro) {
                $html .= html_writer::start_tag('tr');
                $html .= html_writer::start_tag('td', array('width' => '100%', 'align' => 'left'));

                $html .= $OUTPUT->box(format_module_intro('virtualpc', $virtualpc, $cm),
                                      'generalbox mod_introbox', 'virtualpcintro');

                $html .= html_writer::end_tag('td');
                $html .= html_writer::end_tag('tr');

            }*/

            $html .= html_writer::empty_tag('br');

            $html .= html_writer::start_tag('tr');
            $html .= html_writer::start_tag('td', array ( 'width' => '100%', 'align' => 'center'));

            $html .= html_writer::start_tag('form');
            $param = array('type' => 'hidden', 'name' => 'id', 'value' => $cm);
            $html .= html_writer::empty_tag('input', $param);

            $param = array('id' => $cm, 'sesskey' => sesskey(), 'poolName' => $virtualpc->poolname);
            $target = new moodle_url('/mod/virtualcoach/join.php', $param);

            $html .= html_writer::start_tag('span', $param);
            $param = array( 'type' => "button",
                            'class' => "boton_preferente",
                            'style' => "background-image:url(\"data:image/jpeg;base64, $virtualpc->thumb\");
                                       height:80px;
                                       width=140px;
                                       background-repeat: no-repeat;
                                       border-radius: 15px;
                                       padding: 58px 20px 20px 20px;
                                       background-position: 50px 4px;
                                       color: black;
                                       background-color: #FFDC66;",
                            'value' => get_string('joinvirtualpc', 'virtualpc'),
                            'name' => 'btnname',
                            'onclick' => 'window.open(\''.$target->out(false) . '\', \'doloadcontent\',
                                    \'menubar=0, location=0, scrollbars=0, resizable=0, width=900, height=900\', 0);', );
            $html .= html_writer::empty_tag('input', $param);

            $html .= html_writer::end_tag('form');

            $html .= html_writer::end_tag('td');
            $html .= html_writer::end_tag('tr');

            $html .= html_writer::empty_tag('br');
            $html .= html_writer::end_tag('table');

            $html .= html_writer::start_tag('table', array( 'width' => '100%' ,
                                                            'align' => 'center' ));
            $html .= html_writer::start_tag('tr');

            $html .= html_writer::start_tag('td', array('colspan' => '3',
                                                        'align' => 'center',
                                                        'valign' => 'middle' ,
                                                        'width' => '3%'));
            $param = array('align' => 'center', 'src' => $CFG->wwwroot.'/mod/virtualpc/pix/info.png', 'alt' => 'Información' );
            $html .= html_writer::empty_tag('img', $param);
            $html .= html_writer::end_tag('td');

            $html .= html_writer::start_tag('td', array('width' => '50%',
                                                        'valign' => 'middle'));

            $a = "<a href=" . get_config('virtualpc', 'serverurl') . ":" .
                              get_config('virtualpc', 'serverport') .
                              "/down target=\"_blank\">UDS Plugin download page</a>";
            $html .= get_string('virtualpchelp', 'virtualpc', $a);

            $html .= html_writer::end_tag('td');

            $html .= html_writer::end_tag('tr');

            $html .= html_writer::end_tag('table');

        } else {
            $virtualpc->serverurl = "<a href=\"".get_config('virtualpc', 'serverurl').":".
                get_config('virtualpc', 'serverport')."\"  target=\"_blank\">Servidor UDS</a>";

            notice(get_string('idpoolnotfound', 'virtualpc', $virtualpc),
               $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id);
        }

        return $html;
    }

    /**
     * Returns HTML to display the virtualpc help
     *
     * @param instance $virtualpc
     * @param integer $cm
     * @return array
     */
    public function display_virtualpc_help($virtualpc, $cm) {

        $a = "<a href=".get_config('virtualpc', 'serverurl').":".
                get_config('virtualpc', 'serverport').
                "/down target=\"_blank\">UDS Plugin download page</a>";

        $html = get_string('virtualpchelp', 'virtualpc', $a);

        return $html;

    }
}
