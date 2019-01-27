<?php
/**
 * controller for
 * output
 */
// loading the required libs
$rootpath = dirname(dirname(__FILE__));
$dbview_config = json_decode(file_get_contents($rootpath."/config/config.json"));
require_once "../libs/class.database.php";
require_once "../libs/class.templating.php";
require_once "../libs/class.views.php";
// binding the required classes
$views = new dbview\views\viewManager;
$database = new dbview\database\databaseManager($dbview_config->connection_config);
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
foreach ($dbview_config->tables->table_names as $key => $value) {
    $tables[] = $key;
}
// output
echo $twig->render(
    'main.twig',
    ["tabledata" => $database->getAllFromTable("Customers"), "tables" => $tables],
);