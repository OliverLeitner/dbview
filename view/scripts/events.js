/**
 * a simple event listener specific to certain element classes
 * TODO: split and generalize
 * @param {*} className the name of the class to get elements by
 * @param {*} eventType the event we are listening for
 */
function stateHandler(className = null, activeClass = null, eventType = null) {
    document.body.addEventListener(eventType, event => {
        if (event.target.parentElement.classList.contains(className)) {
            let elements = document.getElementsByClassName(className);
            // remove active class from all li's
            if (elements) {
                const arrayLength = elements.length;
                for (i = 0; i < arrayLength; i++) {
                    elements[i].classList.remove(activeClass);
                }
                // free some mem
                delete arrayLength;
                elements = null;
            }
            // add active class to current li
            event.target.parentElement.classList.add([activeClass]);
            let table = event.target.parentElement.id.split("_")[1];
            // get the data for the body
            loadTableToGrid(editableGrid,"data.php",table,"datatable");
            table = null;
        }
    });
    return true;
}

/**
 * base js routing (incoming urls...)
 * TODO: optimize even further... (simplification...)
 * @param {*} className the name of the ul class of the menu
 * @param {*} activeClass the class that highlights the current entry
 */
function routing(className = null, activeClass = null, table_name = "") {
    // unselect currently selected menu entry, if avail
    let elements = document.getElementsByClassName(className);
    if (elements) {
        const arrayLength = elements.length;
        for (let i = 0; i < arrayLength; i++) {
            elements[i].classList.remove(activeClass);
        }
        // free em'
        delete arrayLength;
        elements = null;
    }
    // activate the correct menu entry
    let table = table_name;
    if (window.location.hash) {
        // if we got a param, add it to the request
        let urlparam = window.location.hash;
        param = urlparam.split("/")[1];
        table = param;
        loadTableToGrid(editableGrid,"data.php",table,"datatable");
    } else {
        // fallback, if no route supplied
        loadTableToGrid(editableGrid,"data.php",table,"datatable");
        // https://stackoverflow.com/questions/3870057/how-can-i-update-window-location-hash-without-jumping-the-document
        // adding parameter to url via javascript
        if(history.pushState) {
            history.pushState(null, null, '#/' + table);
        } else {
            location.hash = '#/' + table;
        }
    }
    // activate current entry
    document.getElementById("cur_" + table).classList.add(activeClass);
    // got ram here too
    table = null;
    return true;
}
