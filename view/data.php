<?php
/**
 * output stuff to json
 */
$rootpath = dirname(dirname(__FILE__));
$dbview_config = json_decode(file_get_contents($rootpath."/config/config.json"),JSON_UNESCAPED_UNICODE);

$table = (string) "";
if ($_GET && $_GET["table"]) {
    $table = strip_tags(urlencode($_GET["table"]));
} else {
    $table = array_keys($dbview_config["tables"]["table_names"])[0];
}
$dbview_tablefields = $dbview_config["tables"]["table_names"][$table]["table_fields"];
require_once "../libs/class.database.php";
// binding the required classes
$database = new dbview\database\databaseManager($dbview_config["connection_config"]);
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
    ["tabledata" => $database->getAllFromTable($table, $dbview_tablefields)],
);