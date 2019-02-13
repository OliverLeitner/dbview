/**
 * a simple event listener specific to certain element classes
 * TODO: split and generalize
 * @param {*} className the name of the class to get elements by
 * @param {*} eventType the event we are listening for
 */
function stateHandler(className = null, activeClass = null, eventType = null) {
    document.body.addEventListener(eventType, event => {
        if (event.target.parentElement.classList.contains(className)) {
            let elements = document.querySelectorAll("." + className);
            // remove active class from all li's
            if (elements) {
                for (i = 0; i < elements.length; i++) {
                    elements[i].classList.remove(activeClass);
                }
            }
            // add active class to current li
            event.target.parentElement.classList.add([activeClass]);
            let table = event.target.parentElement.id.split("_")[1];
            // get the data for the body
            loadTableToGrid(editableGrid,"data.php",table,"datatable");
        }
    });
}

/**
 * base js routing (incoming urls...)
 * TODO: optimize even further...
 * @param {*} className the name of the ul class of the menu
 * @param {*} activeClass the class that highlights the current entry
 */
function routing(className = null, activeClass = null, table_name = "") {
    // unselect currently selected menu entry, if avail
    let elements = document.querySelectorAll("." + className);
    if (elements) {
        for (i = 0; i < elements.length; i++) {
            elements[i].classList.remove(activeClass);
        }
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
    }
    // activate current entry
    document.getElementById("cur_" + table).classList.add(activeClass);
}
