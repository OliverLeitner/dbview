"use strict";

/**
 * a simple event listener specific to certain element classes
 * TODO: split and generalize
 * @param {*} className the name of the class to get elements by
 * @param {*} eventType the event we are listening for
 */
var stateHandler = function (className, subClassName, activeClass, subActiveClass, eventType) {
    document.addEventListener(eventType, function(event) {
        if (event.target) {
            if (event.target.parentElement.classList.contains(className)) {
                var elements = document.getElementsByClassName(className);
                var elementsLength = elements.length;
                // remove active class from all li's
                if (elementsLength > 0) {
                    for (var i = 0; i < elementsLength; i++) {
                        if (elements[i].classList.contains(activeClass)) {
                            elements[i].classList.remove(activeClass);
                            if (document.getElementById("insertform")) {
                                var insertForm = document.getElementById("insertform");
                                insertForm.parentNode.removeChild(insertForm);
                            }
                            break;
                        }
                    }
                    // free some mem
                    elements = null;
                }
                // add active class to current li
                if (event.target.parentElement) {
                    event.target.parentElement.classList.add([activeClass]);
                    var table_name = event.target.parentElement.id.split("_")[1];
                    // get the data for the body
                    if (editableGrid && table_name) {
                        loadTableToGrid(editableGrid, "data.php", table_name, "datatable");
                    }
                    var subElements = document.getElementsByClassName(subClassName);
                    var subElementsLength = subElements.length;
                    if (subElementsLength > 0) {
                        for (var i = 0; i < subElementsLength; i++) {
                            if (subElements[i].classList.contains(subActiveClass)) {
                                subElements[i].classList.remove(subActiveClass);
                                break;
                            }
                        }
                    }
                }
            }
            // submenu form activation
            if (event.target.parentElement.classList.contains(subClassName)) {
                if (event.target.parentElement) {
                    event.target.parentElement.classList.add([subActiveClass]);
                    if (parent.document.getElementById("insertform")) {
                        document.getElementById("insertform").parentNode
                        .removeChild(
                            parent.document.getElementById("insertform")
                        );
                    }
                    var table_name = event.target.parentElement.parentElement.id.split("_")[1];
                    loadForm(table_name);
                }
            }
        }
    });
    return true;
};

/**
 * base js routing (incoming urls...)
 * TODO: optimize even further... (simplification...)
 * @param {*} className the name of the ul class of the menu
 * @param {*} activeClass the class that highlights the current entry
 */
var routing = function (className, subClassName, activeClass, subActiveClass, table_name) {
    // unselect currently selected menu entry, if avail
    var subparam = null;
    var elements = document.getElementsByClassName(className);
    if (elements) {
        var arrayLength = elements.length;
        for (var i = 0; i < arrayLength; i++) {
            if (elements[i]) {
                elements[i].classList.remove(activeClass);
            }
        }
        // free em'
        elements = null;
    }
    // activate the correct menu entry
    var table = table_name;
    if (window.location.hash) {
        // if we got a param, add it to the request
        var urlparam = window.location.hash;
        var param = urlparam.split("/")[1];
        if (param && table && editableGrid) {
            table = param;
            loadTableToGrid(editableGrid,"data.php",table,"datatable");
            subparam = urlparam.split("/")[2];
        }
    } else {
        // fallback, if no route supplied
        if (table && editableGrid) {
            loadTableToGrid(editableGrid,"data.php",table,"datatable");
            // https://stackoverflow.com/questions/3870057/how-can-i-update-window-location-hash-without-jumping-the-document
            // adding parameter to url via javascript
            if(history && history.pushState) {
                history.pushState(null, null, '#/' + table);
            } else {
                if (location) {
                    location.hash = '#/' + table;
                }
            }
        }
    }
    // activate current entry
    if (document) {
        document.getElementById("cur_" + table).classList.add(activeClass);
        if (subparam !== null) {
            if (document.getElementsByClassName(subClassName)) {
                var submenu = document.getElementById("submenu_" + table);
                var elem = submenu.getElementsByClassName(subClassName)[0];
                // only activate submenu entry if hash param checks
                if (subparam === "insert") {
                    elem.classList.add(subActiveClass);
                }
            }
        }
    }
};