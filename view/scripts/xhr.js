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
