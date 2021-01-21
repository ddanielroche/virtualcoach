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
 * A javascript module to handler calendar view changes.
 *
 * @module     mod_virtualcoach/view_manager
 * @package    mod_virtualcoach
 * @copyright   2019 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'core/templates',
    'core/str',
    'core/notification',
    'core_calendar/events',
    'core_calendar/selectors',
    'mod_virtualcoach/repository',
], function(
    $,
    Templates,
    Str,
    Notification,
    CalendarEvents,
    CalendarSelectors,
    VirtualCoachCalendarRepository,
) {

    var SELECTORS = {
        COACH_SELECTOR: 'select[name="coach"]',
    };

    /**
     * Register event listeners for the module.
     *
     * @param {object} root The root element.
     */
    var registerEventListeners = function (root) {
        root = $(root);

        root.on('click', CalendarSelectors.links.navLink, function (e) {
            let wrapper = root.find(CalendarSelectors.wrapper),
                view = wrapper.data('view'),
                courseId = wrapper.data('courseid'),
                categoryId = wrapper.data('categoryId'),
                coachId = wrapper.data('coach-id'),
                moduleId = wrapper.data('module-id'),
                link = $(e.currentTarget);

            if (view === 'week') {
                changeWeek(root, link.attr('href'), link.data('year'), link.data('month'), link.data('day'),
                    courseId, categoryId, coachId, moduleId);
                e.preventDefault();
            }

        });

        // TODO todo esto va en calendar.js
        root.on('change', SELECTORS.COACH_SELECTOR, function() {
            var selectElement = $(this);
            var coachId = selectElement.val();
            reloadCurrentWeek(root, coachId, null)
                .then(function() {
                    // We need to get the selector again because the content has changed.
                    return root.find(SELECTORS.COACH_SELECTOR).val(coachId);
                })
                .fail(Notification.exception);
        });

        let body = $('body');

        body.off(CalendarEvents.created);
        body.on(CalendarEvents.created, function() {
            reloadCurrentWeek(root);
        });

        body.off(CalendarEvents.deleted);
        body.on(CalendarEvents.deleted, function() {
            reloadCurrentWeek(root);
        });

        body.off(CalendarEvents.updated);
        body.on(CalendarEvents.updated, function() {
            reloadCurrentWeek(root);
        });

        body.off(CalendarEvents.eventMoved);
        body.on(CalendarEvents.eventMoved, function() {
            reloadCurrentWeek(root);
        });
        // TODO todo esto va en calendar.js
    },

    /**
     * Refresh the week content.
     *
     * @param {object} root The root element.
     * @param {Number} year Year
     * @param {Number} month Month
     * @param {Number} day Day
     * @param {Number} courseId The id of the course whose events are shown
     * @param {Number} categoryId The id of the category whose events are shown
     * @param {Number} coachId The id of the coach user assign
     * @param {Number} moduleId The id of the coach user assign
     * @param {object} target The element being replaced. If not specified, the calendarWrapper is used.
     * @return {promise}
     */
    refreshWeekContent = function(root, year, month, day, courseId, categoryId, coachId, moduleId, target) {
        startLoading(root);

        target = target || root.find(CalendarSelectors.wrapper);

        M.util.js_pending([root.get('id'), year, month, day, courseId, categoryId, coachId, moduleId].join('-'));
        return VirtualCoachCalendarRepository.getCalendarWeekData(year, month, day, courseId, categoryId, coachId, moduleId)
            .then(function(context) {
                return Templates.render(root.attr('data-template'), context);
            })
            .then(function(html, js) {
                return Templates.replaceNode(target, html, js);
            })
            .then(function() {
                $('body').trigger(CalendarEvents.viewUpdated);
                return true;
            })
            .always(function() {
                M.util.js_complete([root.get('id'), year, month, day, courseId, categoryId, coachId, moduleId].join('-'));
                return stopLoading(root);
            })
            .fail(Notification.exception);
    },

    /**
     * Reload the current week view data.
     *
     * @param {object} root The container element.
     * @param {Number} coachId The coach id.
     * @param {Number} categoryId The id of the category whose events are shown
     * @return {promise}
     */
    reloadCurrentWeek = function(root, coachId, categoryId) {
        let wrapper = root.find(CalendarSelectors.wrapper),
            year = wrapper.data('year'),
            month = wrapper.data('month'),
            day = wrapper.data('day'),
            moduleId = wrapper.data('module-id');

        let courseId = root.find(CalendarSelectors.wrapper).data('courseid');

        if (typeof coachId === 'undefined') {
            coachId = $(SELECTORS.COACH_SELECTOR).val();
        }

        if (typeof categoryId === 'undefined') {
            categoryId = root.find(CalendarSelectors.wrapper).data('categoryId');
        }

        return refreshWeekContent(root, year, month, day, courseId, categoryId, coachId, moduleId, null);
    },

    /**
     * Handle changes to the current calendar view.
     *
     * @param {object} root The root element.
     * @param {String} url The calendar url to be shown
     * @param {Number} year Year
     * @param {Number} month Month
     * @param {Number} day Day
     * @param {Number} courseId The id of the course whose events are shown
     * @param {Number} categoryId The id of the category whose events are shown
     * @param {Number} coachId The id of the coach user assign
     * @param {Number} moduleId The id of the module
     * @return {promise}
     */
    changeWeek = function(root, url, year, month, day, courseId, categoryId, coachId, moduleId) {
        console.log([url, year, month, day, courseId, categoryId, coachId, moduleId]);
        return refreshWeekContent(root, year, month, day, courseId, categoryId, coachId, moduleId, null)
            .then(function() {
                if (url.length && url !== '#') {
                    window.history.pushState({}, '', url);
                }
                return arguments;
            })
            /*.then(function() {
                $('body').trigger(CalendarEvents.weekChanged, [year, month, day, courseId, categoryId, coachId]);
                return arguments;
            })*/;
    },

    /**
     * Set the element state to loading.
     *
     * @param {object} root The container element
     * @method startLoading
     */
    startLoading = function(root) {
        let loadingIconContainer = root.find(CalendarSelectors.containers.loadingIcon);

        loadingIconContainer.removeClass('hidden');
    },

    /**
     * Remove the loading state from the element.
     *
     * @param {object} root The container element
     * @method stopLoading
     */
    stopLoading = function(root) {
        let loadingIconContainer = root.find(CalendarSelectors.containers.loadingIcon);

        loadingIconContainer.addClass('hidden');
    };

    return {
        init: function(root) {
            registerEventListeners(root);
        },
        reloadCurrentWeek: reloadCurrentWeek,
        changeWeek: changeWeek,
        refreshWeekContent: refreshWeekContent,
    };
});