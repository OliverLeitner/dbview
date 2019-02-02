<?php
/**
 * output stuff to json
 */
$rootpath = dirname(dirname(__FILE__));
$dbview_config = json_decode(file_get_contents($rootpath."/config/config.json"),JSON_UNESCAPED_UNICODE);

require_once "../libs/class.database.php";
// binding the required classes
$database = new dbview\database\databaseManager($dbview_config["connection_config"]);

$table = (string) "";
if ($_GET && $_GET["table"]) {
    $table = strip_tags(urlencode($_GET["table"]));
} else {
    $table = array_keys($dbview_config["tables"]["table_names"])[0];
}
$dbview_tablefields = $dbview_config["tables"]["table_names"][$table]["table_fields"];

// hook logic
// TODO: separate logic into its own lib
// tODO: add logic for function hooks
$subdata = null;
if ($dbview_tablefields && $dbview_tablefields[0]) {
    foreach($dbview_tablefields[0] AS $key => $value) {
        $subdata[$key] = null;
        if (is_array($value) && $value["values"]) {
            foreach($value["values"] AS $skey => $sval) {
                if (!is_array($sval)) {
                    if ($sval === "enum" || $sval === "table" || $skey === "table_name") {
                        // cross table data origin hook
                        if ($skey === "table_name") {
                            $type = "table";
                            $table_name = $sval;
                            $dbdata = $database->getAllFromTable(
                                    $table_name,
                                    $dbview_config["tables"]["table_names"][$table_name]["table_fields"]
                            );
                            // the actual data being inserted on dropdown select
                            $sel_fieldname = $dbview_config["tables"]["table_names"][$table_name]["selector_field"];
                            $cross_values = [];
                            $cross_key = null;
                            foreach ($dbdata AS $dkey => $dval) {
                                $selval = null;
                                foreach ($dval AS $ckey => $cval) {
                                    if ($ckey === $sel_fieldname) {
                                        $selval = $cval;
                                    } else {
                                        // names to show in the dropdown
                                        $cross_values[$selval] = trim($cval);
                                    }
                                    $cross_key = $ckey;
                                }
                                $subdata[$key][$cross_key] = $cross_values;
                            }
                        }
                    }
                }
            }
        }
    }
}

// choose a config based on the fact if theres one...
$tableconfig = false;
if ($dbview_tablefields) {
    $tableconfig = $dbview_tablefields[0];
}

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

// putting the data together
$maindata = $database->getAllFromTable($table, $dbview_tablefields);

// adding  hook data to config
if (is_array($tableconfig)) {
    foreach ($tableconfig AS $key => $value) {
        if ($value["values"] && is_array($subdata[$key])) {
            foreach ($subdata[$key] AS $cross_key => $cross_values) {
                if (is_array($cross_values)) {
                    $tableconfig[$key]["values"][$cross_key] = $cross_values;
                }
            }
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