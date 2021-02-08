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
 * A javascript module to handler calendar ajax actions.
 *
 * @module     mod_virtualcoach/repository
 * @package    mod_virtualcoach
 * @class      repository
 * @copyright   2019 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax'], function($, Ajax) {

    /**
     * Get calendar data for the day view.
     *
     * @method getCalendarDayData
     * @param {Number} year Year
     * @param {Number} month Month
     * @param {Number} day Day
     * @param {Number} courseId The course id.
     * @param {Number} categoryId The id of the category whose events are shown
     * @param {Number} coachId The id of the coach user assign
     * @param {Number} moduleId The id of the module in the course
     * @return {promise} Resolved with the day view data.
     */
    var getCalendarWeekData = function (year, month, day, courseId, categoryId, coachId, moduleId) {
        var request = {
            methodname: 'mod_virtualcoach_get_calendar_weekly_view',
            args: {
                year: year,
                month: month,
                day: day,
                courseId: courseId,
                categoryId: categoryId,
                coachId: coachId,
                moduleId: moduleId,
            }
        };

        return Ajax.call([request])[0];
    };

    return {
        getCalendarWeekData: getCalendarWeekData,
    };
});
