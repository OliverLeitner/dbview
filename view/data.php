<?php
/**
 * Output stuff to json
 */
$rootpath = dirname(dirname(__FILE__));
$dbview_config = json_decode(
    file_get_contents($rootpath."/config/config.json"),
    JSON_UNESCAPED_UNICODE
);
require_once $rootpath."/libs/class.database.php";
require_once $rootpath."/libs/class.views.php";
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
$dbview_tablefields = $dbview_config["tables"]["table_names"][$table]["table_fields"]; // max line

// putting together the output data
$viewdata = new dbview\views\viewManager($dbview_config, $database, $table);

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
            array_map(
                function ($cross_key, $cross_values) use (&$tableconfig, &$key) {
                    if (is_array($cross_values)) {
                        $tableconfig[$key]["values"][$cross_key] = $cross_values;
                    }
                },
                array_keys($subdata[$key]), $subdata[$key]
            );
        }
    }
}

// metadata part of the json
$mainkeeper = array();
$citer = 0;
foreach ($maindata[0] AS $key => $val) {
    $mainkeeper["metadata"][$citer]["name"] = $key;
    // label condition
    if ($tableconfig[$key]["label"]) {
        $mainkeeper["metadata"][$citer]["label"] = $tableconfig[$key]["label"];
    } else {
        $mainkeeper["metadata"][$citer]["label"] = $key;
    }
    if ($tableconfig && $tableconfig[$key]) {
        $mainkeeper["metadata"][$citer]["datatype"] = $tableconfig[$key]["datatype"];
        $mainkeeper["metadata"][$citer]["editable"] = $tableconfig[$key]["editable"];
    } else {
        $mainkeeper["metadata"][$citer]["datatype"] = "string";
        $mainkeeper["metadata"][$citer]["editable"] = true;
    }
    if ($tableconfig && $tableconfig[$key] && $tableconfig[$key]["values"] !== false) {
        $mainkeeper["metadata"][$citer]["values"] = null;
        foreach ($tableconfig[$key]["values"] AS $skey => $sval) {
            $type = $tableconfig[$key]["values"]["type"];
            if ($skey !== "type" && $skey !== "table_name") {
                $mainkeeper["metadata"][$citer]["values"][$skey] = null;
                if ($type !== "in_menu" && $type !== "selector_field" && $type !== "enum") {
                    $mainkeeper["metadata"][$citer]["values"] = $subdata[$key];
                } else {
                    foreach ($sval AS $sskey => $ssval) {
                        $mainkeeper["metadata"][$citer]["values"][$skey][$sskey] = $ssval;
                    }
                }
            }
        }
    }
    $citer++;
}

// data part of the json
$iter = 0;
foreach ($maindata AS $entry) {
    $mainkeeper["data"][$iter]["id"] = $entry[array_key_first($entry)];
    $mainkeeper["data"][$iter]["values"] = $entry;
    $iter++;
}

// TODO: on the above -> simplify and put into a class...

// output
header("Content-type:application/json; charset=utf-8");
echo json_encode($mainkeeper);
