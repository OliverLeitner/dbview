// from: https://github.com/jserz/js_piece/blob/master/DOM/ChildNode/before()/before().md
(function (arr) {
    arr.forEach(function (item) {
        if (item.hasOwnProperty('prepend')) {
            return;
        }
        Object.defineProperty(item, 'prepend', {
            configurable: true,
            enumerable: true,
            writable: true,
            value: function before() {
                var argArr = Array.prototype.slice.call(arguments),
                    docFrag = document.createDocumentFragment();

                argArr.forEach(function (argItem) {
                    var isNode = argItem instanceof Node;
                    docFrag.appendChild(isNode ? argItem : document.createTextNode(String(argItem)));
                });

                this.parentNode.insertBefore(docFrag, this);
            }
        });
    });
})([Element.prototype, CharacterData.prototype, DocumentType.prototype]);

/**
 * loading table data to the grid
 * @param {*} gridhook 
 * @param {*} datapath 
 * @param {*} tablename 
 * @param {*} gridname 
 */
function loadTableToGrid(gridhook, datapath, tablename, gridname) {
    gridhook.tableLoaded = function () {
        this.renderGrid(gridname, "datagrid");
        // prepend form if insert param given
        if (window.location.hash) {
            var params = window.location.hash.split("#/")[1];
            if (params[1]) {
                var insert = params.split("/")[1];
                if (insert === "insert") {
                    loadForm(tablename);
                }
            }
        }
    };
    gridhook.loadJSON(datapath + "?table=" + tablename);
}

/**
 * form to body
 */
function loadForm(table_name) {
    if (document) {
        var request = new XMLHttpRequest();
        // getting response as dom copy (avoid caching)
        request.open('GET', 'form.php?table=' + table_name, true);
        request.responseType = 'document';
        request.onload = function () {
            if (request.status >= 200 && request.status < 400) {
                var responseDom = request.response.body.children[0];
                if (
                    !document.getElementById("insertform") &&
                    document.getElementById("datatable") !== null &&
                    responseDom !== null) {
                    document.getElementById("datatable").prepend(responseDom);
                    return true;
                }
            } else {
                console.log("something went wrong");
                console.log(request.statusText);
                return false;
            }
        };
        request.onerror = function () {
            console.log("an error occured");
            console.log(request.statusText);
        };
        request.send();
    }
}