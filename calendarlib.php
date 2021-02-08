<?php

use core_calendar\external\calendar_event_exporter;
use core_calendar\external\events_related_objects_cache;
use core_calendar\external\month_exporter;
use core_calendar\local\api;
use core_calendar\local\event\entities\event_interface;
use core_calendar\type_factory;
use mod_virtualcoach\enrolment_observers;



/**
 * Get the calendar view output.
 *
 * @param calendar_information $calendar The calendar being represented
 * @param string $view The type of calendar to have displayed
 * @param string $location
 * @param int $courseId
 * @param int $moduleId
 * @param bool $include_navigation Whether to include navigation
 * @param bool $skip_events Whether to load the events or not
 * @return  array[array, string]
 * @throws Exception
 */
function calendar_get_week_view(calendar_information $calendar, string $view, int $courseId, string $location, int $moduleId, bool $include_navigation = true, bool $skip_events = false) {
    if ($view !== 'week') {
        return calendar_get_view($calendar, $view, $include_navigation, $skip_events);
    }
    //$view = 'month';
    global $PAGE, $USER, $DB;

    $renderer = $PAGE->get_renderer('core_calendar');
    $type = type_factory::get_calendar_instance();

    // Calculate the bounds of the month.
    $calendar_date = $type->timestamp_to_date_array($calendar->time);

    $date = new DateTime('now', core_date::get_user_timezone_object(99));
    // TODO Revisar implementación por valores exedidos
    $event_limit = 199;


    $ts_tart = $type->convert_to_timestamp($calendar_date['year'], $calendar_date['mon'], 1);
    $month_days = $type->get_num_days_in_month($calendar_date['year'], $calendar_date['mon']);
    $date->setTimestamp($ts_tart);
    $date->modify('+' . $month_days . ' days');

    $template = 'mod_virtualcoach/calendar_week';

    // We need to extract 1 second to ensure that we don't get into the next day.
    $date->modify('-1 second');
    $tend = $date->getTimestamp();

    list($user_param, $group_param, $course_param, $category_param) = array_map(function($param) {
        // If parameter is true, return null.
        if ($param === true) {
            return null;
        }

        // If parameter is false, return an empty array.
        if ($param === false) {
            return [];
        }

        // If the parameter is a scalar value, enclose it in an array.
        if (!is_array($param)) {
            return [$param];
        }

        // No normalisation required.
        return $param;
    }, [$calendar->users, $calendar->groups, $calendar->courses, $calendar->categories]);

    if ($skip_events) {
        $events = [];
    } else {
        $events = api::get_events(
            $ts_tart,
            $tend,
            null,
            null,
            null,
            null,
            $event_limit,
            null,
            $user_param,
            $group_param,
            $course_param,
            $category_param,
            true,
            true,
            function ($event) {
                if ($proxy = $event->get_course_module()) {
                    $cm_info = $proxy->get_proxied_instance();
                    return $cm_info->uservisible;
                }

                if ($proxy = $event->get_category()) {
                    $category = $proxy->get_proxied_instance();

                    return $category->is_uservisible();
                }

                return true;
            }
        );
    }

    $related = [
        'events' => $events,
        'cache' => new events_related_objects_cache($events),
        'type' => $type,
    ];

    $month = new month_exporter($calendar, $type, $related);
    $month->set_includenavigation($include_navigation);
    $month->set_initialeventsloaded(!$skip_events);
    $month->set_showcoursefilter($view == "month");
    $data = $month->export($renderer);
    $data->moduleId = $moduleId;
    $data->coachId = $location;
    $data->coachAccessUrl = builder_coach_access_url($courseId, $moduleId, $location)->out(false);
    $data->view = $view;
    $data->hours = array_fill(0, 24, [
        'prepadding' => [],
        'postpadding' => [],
        'days' => [],
        /*'days' => array_fill(0, 7, [
            'seconds' => 0,
            'minutes' => 0,
            'hours' => 0,
            'mday' => 1,
            'wday' => 4,
            'year' => 2020,
            'yday' => 274,
            'istoday' => false,
            'isweekend' => false,
            'timestamp' => 1601503200,
            'neweventtimestamp' => 1601533985,
            'viewdaylink' => "https:\/\/moodle.cursosaula21.com\/calendar\/view.php?view=day&time=1601503200&course=102",
            'events' => [],
            'hasevents' => false,
            'calendareventtypes' => [],
            'previousperiod' => 1601416800,
            'nextperiod' => 1601589600,
            'navigation' => "<div class=\"calendar-controls\"><a class=\"arrow_link previous\" href=\"view.php?view=day&amp;course=102&amp;time=1601416800\" title=\"Mi\u00e9rcoles\" data-time=\"1601416800\" data-drop-zone=\"nav-link\"><span class=\"arrow \">&#x25C4;<\/span>&nbsp;<span class=\"arrow_text\">Mi\u00e9rcoles<\/span><\/a><span class=\"hide\"> | <\/span><span class=\"current\">jueves, 1 de octubre de 2020<\/span><span class=\"hide\"> | <\/span><a class=\"arrow_link next\" href=\"view.php?view=day&amp;course=102&amp;time=1601589600\" title=\"Viernes\" data-time=\"1601589600\" data-drop-zone=\"nav-link\"><span class=\"arrow_text\">Viernes<\/span>&nbsp;<span class=\"arrow \">&#x25BA;<\/span><\/a><span class=\"clearer\"><!-- --><\/span><\/div>\n",
            'haslastdayofevent' => false,
            'popovertitle' => ''
        ])*/
    ]);

    $dayno = $data->daynames[0]->dayno;
    $dif = $dayno - $calendar_date['wday'];
    $timestamp = $timestamp2 = $calendar_date[0] + $dif * 24 * 60 * 60;

    $content = coach_filter_selector(build_url($moduleId, $location, $timestamp), $courseId, $location);
    $data->filter_selector = $content;

    foreach ($data->daynames as $dayname) {
        $result = $type->timestamp_to_date_array($timestamp2);
        $dayname->mday = $result['mday'];
        $timestamp2 += 24 * 60 * 60;
    }
    $events = get_events($timestamp, $timestamp2, $location);
    $time = time();
    $teacher = has_teacher($courseId);
    foreach ($data->hours as $indexH => &$hour) {
        $hour['hour'] = str_pad($indexH, 2, "0", STR_PAD_LEFT) . ':00';
        for ($i = 0; $i < 7; $i++) {
            $newTS = $timestamp + ($i * 24 * 60 * 60);
            /*if ($newTS < $time) {
                $hour['prepadding'][] = $i;
            } else {*/
                $day = $type->timestamp_to_date_array($newTS);
                $day['timestamp'] = $day['neweventtimestamp'] = $day[0];
                $events2 = get_hour_events($newTS, $newTS + 60 * 60 - 60, $events);
                $day['events'] = export_event($events2, $related, $renderer);
                foreach ($day['events'] as $event) {
                    if ($teacher) {
                        $event->canedit = $event->candelete = true;
                        $user = $DB->get_record('user', array('id' => $event->userid));
                        $event->name = fullname($user);
                    } else {
                        if ($event->userid !== $USER->id) {
                            $event->canedit = $event->candelete = false;
                            $event->name = get_string('reserved_other', 'mod_virtualcoach');
                        } else {
                            $event->canedit = $event->candelete = true;
                            $event->name = get_string('reserved_my', 'mod_virtualcoach');
                        }
                    }
                }
                $day['hasevents'] = count($day['events']) > 0;
                $day['istoday'] = false;
                $day['isweekend'] = false;
                $day['calendareventtypes'] = [];
                $day['previousperiod'] = 0;
                $day['nextperiod'] = 1;
                $day['navigation'] = 'http://localhost';
                $day['haslastdayofevent'] = false;
                $day['popovertitle'] = '';
                $day['isbefore'] = $newTS < $time;
                $hour['days'][] = $day;
            //}
        }
        $timestamp += 60 * 60;
    }

    $data->periodname = userdate($timestamp2 - 7 * 24 * 60 * 60, '%e %b') . ' - ' . userdate($timestamp2, '%e %b %Y');
    //$data->periodname = date('j M', $timestamp2 - 7 * 24 * 60 * 60) . ' - ' . date('j M o', $timestamp2 - 1);
    $data->previousperiod = $type->timestamp_to_date_array($timestamp2 - 14 * 24 * 60 * 60);
    $data->previousperiod['timestamp'] = $data->previousperiod[0];
    $data->previousperiod['week'] = date('W', $data->previousperiod[0]);
    $data->previousperiodname = get_string('week_no', 'mod_virtualcoach', ['week' => date('W o', $data->previousperiod[0])]);
    $data->previousperiodlink = build_url($moduleId, $location, $data->previousperiod[0])->out(false);
    $data->nextperiod = $type->timestamp_to_date_array($timestamp2);
    $data->nextperiod['timestamp'] = $data->nextperiod[0];
    $data->nextperiod['week'] = date('W', $data->nextperiod[0]);
    $data->nextperiodname = get_string('week_no', 'mod_virtualcoach', ['week' => date('W o', $data->nextperiod[0])]);
    //$data->nextperiodname = 'Week'. ' ' . date('W o', $data->nextperiod[0]);
    $data->nextperiodlink = build_url($moduleId, $location, $data->nextperiod[0])->out(false);

    return [$data, $template];
}

function has_teacher($courseId)
{
    $context = context_course::instance($courseId, MUST_EXIST);
    return has_capability('moodle/course:update', $context);
}

/**
 * @param moodle_url $returnurl
 * @param int $courseId
 * @param null $coachId
 * @return mixed
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function coach_filter_selector(moodle_url $returnurl, $courseId, $coachId = null) {
    global $USER;

    if (!isloggedin() or isguestuser()) {
        return '';
    }
    $courseurl = new moodle_url($returnurl);
    $courseurl->remove_params('course');

    $coaches = enrolment_observers::get_coaches_assign($USER->id, $courseId);

    $selected = $coachId;

    $label = get_string('weekly_coach_bookings', 'mod_virtualcoach');
    $select = html_writer::label($label, 'coach', false, ['class' => 'm-r-1']);
    $select .= html_writer::select($coaches, 'coach', $selected, false, ['class' => 'cal_courses_flt']);

    return $select;
}

function build_url($moduleId, $coachId, $time)
{
    //"https:\/\/moodle.cursosaula21.com\/calendar\/view.php?view=month&time=1598911200&course=102",
    $url = new moodle_url('/mod/virtualcoach/view.php', ['id' => $moduleId, 'coachId' => $coachId, 'view' => 'week', 'time' => $time]);
    return $url;
}

function builder_coach_access_url($courseId, $moduleId, $coachId)
{
    global $DB;

    /*$broker = my_uds_login();
    $pool = uds_servicespools_byname($broker, enrolment_observers::get_pool_name($USER, $courseId, $moduleinstance));

    if ($pool) {
        $printaccessbutton = true;
        $virtualpc = new stdClass();
        $virtualpc->name = $virtualpc->poolname = $pool->name;
        $virtualpc->thumb = $pool->thumb;
    }

    uds_logout($broker);*/

    $coach = $DB->get_record('coach', ['id' => $coachId], '*', MUST_EXIST);

    $param = array('id' => $moduleId, 'sesskey' => sesskey(), 'poolName' => $coach->pool);
    $target = new moodle_url('/mod/virtualcoach/join.php', $param);
    return $target;
}

function get_events($time_start, $time_end, $location)
{
    $events = api::get_events(
        $time_start,
        $time_end,
        null,
        null,
        null,
        null,
        // TODO Revisar implementación por valores exedidos
        199,
        null,
        null,
        null,
        null,
        null,
        true,
        true,
        function ($event) use ($location) {
            /** @var event_interface $event */
            return $event->get_location() == $location && $event->get_type() == 'virtualcoach';
        }
    );

    return $events;
}

/**
 * @param $time_tart
 * @param $time_end
 * @param $events event_interface[]
 */
function get_hour_events($time_start, $time_end, $events)
{
    $result = [];
    foreach ($events as $event) {
        $times = $event->get_times();
        $starttime = $times->get_start_time()->getTimestamp();
        $endtime = $times->get_end_time()->getTimestamp();
        if ($starttime > $time_end) {
            // Starts after time.
            continue;
        }
        if ($endtime < $time_start) {
            // Starts after time.
            continue;
        }

        $result[] = $event;
    }
    return $result;
}

function export_event($events, $related, $output)
{
    $cache = $related['cache'];
    $eventexporters = array_map(function($event) use ($related, $cache, $output) {
        $context = $cache->get_context($event);
        $course = $cache->get_course($event);
        $moduleinstance = $cache->get_module_instance($event);
        $exporter = new calendar_event_exporter($event, [
            'context' => $context,
            'course' => $course,
            'moduleinstance' => $moduleinstance,
            'daylink' => new moodle_url('/calendar/view.php', [
                'view' => 'day',
                'time' => $event->get_times()->get_start_time()->getTimestamp(),
            ]),
            'type' => $related['type'],
            'today' => $event->get_times()->get_start_time()->getTimestamp(),
        ]);

        return $exporter;
    }, $events);

    $events2 = array_map(function($exporter) use ($output) {
        return $exporter->export($output);
    }, $eventexporters);
    return $events2;
}