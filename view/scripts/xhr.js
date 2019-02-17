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
    gridhook.tableLoaded = function () { this.renderGrid(gridname, "datagrid"); };
    gridhook.loadJSON(datapath + "?table=" + tablename);
}

/**
 * form to body
 */
function loadForm(table_name) {
    if (document) {
        var request = new XMLHttpRequest();
        // getting response as dom copy
        request.open('GET', 'form.php?table=' + table_name, true);
        request.responseType = 'document';
        request.onload = function () {
            if (request.status >= 200 && request.status < 400) {
                var resp = request.response;
                // prepend form to document
                var responseDom = resp.body.children[0];
                if (
                    !document.getElementById("insertform") &&
                    document.getElementById("datatable") !== null &&
                    responseDom !== null) {
                    document.getElementById("datatable").prepend(responseDom);
                    return true;
                }
            } else {
                // output error info
                console.log("something went wrong");
                console.log(request);
                return false;
            }
        };
        request.onerror = function () {
            // There was a connection error of some sort
        };
        request.send();
    }
}