<?php
/**
 * Output stuff to json
 */
$rootpath = dirname(dirname(__FILE__));
$config = json_decode(
    file_get_contents($rootpath."/config/config.json"),
    JSON_UNESCAPED_UNICODE
);
require_once $rootpath."/libs/class.database.php";
require_once $rootpath."/libs/class.views.php";
// binding the required classes
$database = new dbview\database\databaseManager($config["connection_config"]);
// checking if we got a table selected, else use the default
$table = (string) "";
if ($_GET && $_GET["table"]) {
    $table = urlencode(strip_tags($_GET["table"]));
} else {
    $table = array_key_first($config["tables"]["table_names"]);
}
// making the table field properties more accessible
$tablefields = $config["tables"]["table_names"][$table]["table_fields"]; // max line
// putting together the output data
$viewdata = new dbview\views\ViewManager($config, $database, $table);
// putting the main data together
$maindata = $database->getAllFromTable($table, $tablefields);
// adding all the dropdowns
$subdata = $viewdata->buildDropDown($tablefields);
// prepping the output
$mainkeeper = $viewdata->dataToJson($tablefields, $maindata, $subdata);
// output
header("Content-type:application/json; charset=utf-8");
echo json_encode($mainkeeper);
