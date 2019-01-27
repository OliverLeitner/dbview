<?php
/**
 * output stuff to json
 */
$table = (string) "";
if ($_GET && $_GET["table"]) {
    $table = $_GET["table"];
} else {
    $table = "Customers";
}
$rootpath = dirname(dirname(__FILE__));
$dbview_config = json_decode(file_get_contents($rootpath."/config/config.json"));
require_once "../libs/class.database.php";
// binding the required classes
$database = new dbview\database\databaseManager($dbview_config->connection_config);
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
// output
header("Content-type:application/json; charset=utf-8");
echo $twig->render(
    'data.twig',
    ["tabledata" => $database->getAllFromTable($table)],
);