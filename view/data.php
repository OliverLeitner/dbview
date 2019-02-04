<?php
/**
 * output stuff to json
 */
$rootpath = dirname(dirname(__FILE__));
$dbview_config = json_decode(file_get_contents($rootpath."/config/config.json"), JSON_UNESCAPED_UNICODE);
require_once "../libs/class.database.php";
require_once "../libs/class.views.php";
// binding the required classes
$database = new dbview\database\databaseManager($dbview_config["connection_config"]);

// checking if we got a table selected, else use the default
$table = (string) "";
if ($_GET && $_GET["table"]) {
    $table = urlencode(strip_tags($_GET["table"]));
} else {
    $table = array_key_first($dbview_config["tables"]["table_names"]);
}

// making the table field properties more accessible
$dbview_tablefields = $dbview_config["tables"]["table_names"][$table]["table_fields"];

// putting together the output data
$viewdata = new dbview\views\viewManager($dbview_config, $database, $table);

// twig logic
require_once '../libs/vendor/autoload.php';
$loader = new Twig_Loader_Filesystem('../templates');
$twig = new Twig_Environment($loader, [
    'cache' => '../templates/cache',
    'debug' => true,
    'auto_reload' => true,
    'optimizations' => 1,
    'charset' => 'utf-8',
]);

// putting the main data together
$maindata = $database->getAllFromTable($table, $dbview_tablefields);

// adding all the dropdowns
$subdata = $viewdata->buildDropDown($dbview_tablefields);

// adding all subdata to the config
// choose a config based on the fact if theres one...
$tableconfig = false;
if ($dbview_tablefields) {
    $tableconfig = $dbview_tablefields[0];
    foreach ($tableconfig as $key => $value) {
        if ($value["values"] && is_array($subdata[$key])) {
            array_map(function ($cross_key, $cross_values) use (&$tableconfig, &$key) {
                if (is_array($cross_values)) {
                    $tableconfig[$key]["values"][$cross_key] = $cross_values;
                }
            }, array_keys($subdata[$key]), $subdata[$key]);
        }
    }
}

// output
header("Content-type:application/json; charset=utf-8");
echo $twig->render(
    'data.twig',
    [
        "tabledata" => $maindata,
        "tableconfig" => $tableconfig,
        "subdata" => $subdata
    ],
);
