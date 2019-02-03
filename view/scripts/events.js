/**
 * a simple event listener specific to certain element classes
 * TODO: split and generalize
 * @param {*} className the name of the class to get elements by
 * @param {*} eventType the event we are listening for
 */
function stateHandler(className = null, eventType = "click") {
    document.body.addEventListener(eventType, event => {
        if (event.target.parentElement.classList.contains(className)) {
            let elements = document.querySelectorAll("." + className);
            // remove active class from all li's
            for (i = 0; i < elements.length; i++) {
                elements[i].classList.remove("active");
            }
            // add active class to current li
            event.target.parentElement.classList.add(["active"]);
            let table = event.target.parentElement.id.split("_")[1];
            // get the data for the body
            loadTableToGrid(editableGrid,"data.php",table,"datatable");
        }
    });
}