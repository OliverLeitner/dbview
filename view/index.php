<?php
/**
 * controller for
 * output
 */
// loading the required libs
$start = microtime(true);
$rootpath = dirname(dirname(__FILE__));
$config = json_decode(file_get_contents($rootpath."/config/config.json"), JSON_UNESCAPED_UNICODE);
require_once $rootpath."/libs/class.database.php";
require_once $rootpath."/libs/class.templating.php";
require_once $rootpath."/libs/class.views.php";
// binding the required classes
$database = new dbview\database\databaseManager($config["connection_config"]);
$templates = new dbview\templating\templateManager;
// twig logic
require_once $rootpath."/libs/vendor/autoload.php";
$loader = new Twig_Loader_Filesystem($rootpath."/templates");
$twig = new Twig_Environment($loader, [
    "cache" => $rootpath."/templates/cache",
    "debug" => true,
    "auto_reload" => true,
    "optimizations" => 1,
    "charset" => "utf-8",
]);
// table names for menu
$tables = [];
array_map(function ($tkey, $tval) use (&$tables) {
    array_push($tables, ["name" => $tkey, "values" => $tval]);
}, array_keys($config["tables"]["table_names"]), array_values($config["tables"]["table_names"]));
// defaults
$table = array_key_first($config["tables"]["table_names"]);
// choose a config based on the fact if theres one...
$tableconfig = false;
if ($table) {
    // subdata logic
    $views = new dbview\views\ViewManager($config, $database, $table);
    // set tableconfig
    $tableconfig = $config["tables"]["table_names"][$table]["table_fields"][0];
}
// output
$time_elapsed_secs = microtime(true) - $start;
$output = $twig->render(
    "main.twig",
    [
        "tabledata" => $database->getAllFromTable($table),
        "tables" => $tables,
        "tableconfig" => $tableconfig,
        "stat" => $time_elapsed_secs,
    ]
);

// TODO: move tidy off
// Specify configuration
$config = array(
    'indent'         => true,
    'output-xhtml'   => true,
    'wrap'           => 200);
$tidy = new tidy;
$tidy->parseString($output, $config, "utf8");
$tidy->cleanRepair();
echo $tidy;
