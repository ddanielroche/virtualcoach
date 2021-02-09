
/**
 * A javascript module to fix calendar day names
 *
 * @module     mod_virtualcoach/fixed_day_names
 * @package    mod_virtualcoach
 * @class      fixed_day_names
 * @copyright   2021 Salfa Meridian S.L. - Aula21
 * @author      Dany Daniel Roche <ddanielroche@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function ($) {
    var vc_vars = {},
        getSelectos = function () {
            vc_vars.navbar = $('.fixed-top.navbar')[0];
            vc_vars.table = $('.calendarmonth.calendartable')[0];
            vc_vars.thead = document.querySelector("table thead");
            vc_vars.mq = window.matchMedia("(min-width: 780px)");
        },

        getVars = function () {
            // Ancho de la tabla
            vc_vars.tableWidth = vc_vars.table.offsetWidth;
            // Posición superior de la tabla en relación con la ventana del navegador.
            vc_vars.tableOffsetTop = vc_vars.table.getBoundingClientRect().top;
            // Altura del encabezado
            vc_vars.theadHeight = vc_vars.thead.offsetHeight;
            vc_vars.navvarBotton = vc_vars.navbar.getBoundingClientRect().bottom;
        },

        scrollHandler = function () {
            vc_vars.tableOffsetTop = vc_vars.table.getBoundingClientRect().top;
                // 2 Obtener la posición superior de la última sección en relación con la ventana.
            var lastSectionOffsetTop = vc_vars.table.getBoundingClientRect().bottom - vc_vars.navvarBotton;
            // 3 Comprobar si un usuario se ha desplazado más o igual a la posición superior inicial de la tabla.
            if (vc_vars.navvarBotton >= vc_vars.tableOffsetTop) {
                // 4 Si eso ocurre, ajustamos el ancho de thead igual al ancho inicial de la tabla.
                vc_vars.thead.style.width = vc_vars.tableWidth + 'px';
                // 5 A continuación, comprobamos si el valor resultante del paso 2 es mayor que la altura de vc_vars.thead.
                //console.log(['5', lastSectionOffsetTop,vc_vars.theadHeight]);
                if (lastSectionOffsetTop > vc_vars.theadHeight) {
                    // 6
                    vc_vars.thead.style.top = vc_vars.navvarBotton + 'px';
                    vc_vars.thead.style.position = 'fixed';
                } else {
                    // 7
                    vc_vars.thead.style.top = 'calc(100% - ' + vc_vars.theadHeight + 'px)';
                    vc_vars.thead.style.position = 'absolute';
                }
            } else {
                // 8
                vc_vars.thead.style.width = "100%";
                vc_vars.thead.style.top = "auto";
                vc_vars.thead.style.position = null;
            }
        },

        resizeHandler = function () {
            getVars();
            scrollHandler();
        },

        registerEventListeners = function () {
            getSelectos();
            getVars();
            scrollHandler();

            window.addEventListener("scroll", scrollHandler);
            window.addEventListener("resize", resizeHandler);
            $(document).on(M.core.event.FILTER_CONTENT_UPDATED, getSelectos);
        };

    /*$(document).ready(function() {

    });*/

    return {
        init: function(root) {
            registerEventListeners(root);
        },
    };
});