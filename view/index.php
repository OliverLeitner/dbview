<?php
/**
 * controller for
 * output
 */
// loading the required libs
$rootpath = dirname(dirname(__FILE__));
$dbview_config = json_decode(file_get_contents($rootpath."/config/config.json"), JSON_UNESCAPED_UNICODE);
require_once "../libs/class.database.php";
require_once "../libs/class.templating.php";
require_once "../libs/class.views.php";
// binding the required classes
$database = new dbview\database\databaseManager($dbview_config["connection_config"]);
$templates = new dbview\templating\templateManager;
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
// table names for menu
$tables = [];
foreach ($dbview_config["tables"]["table_names"] as $key => $value) {
    $tables[] = ["name" => $key, "values" => $value];
}
// defaults
$table = array_keys($dbview_config["tables"]["table_names"])[0];

// choose a config based on the fact if theres one...
$tableconfig = false;
if ($table) {
    // subdata logic
    $views = new dbview\views\viewManager($dbview_config, $database, $table);
    // set tableconfig
    $tableconfig = $dbview_config["tables"]["table_names"][$table]["table_fields"][0];
}
// output
echo $twig->render(
    'main.twig',
    [
        "tabledata" => $database->getAllFromTable($table),
        "tables" => $tables,
        "tableconfig" => $tableconfig
    ],
);
