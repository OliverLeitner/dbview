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
// output
$time_elapsed_secs = microtime(true) - $start;
$output = $twig->render(
    "form.twig",
    [
        "table" => htmlentities(strip_tags($_GET["table"]))
    ]
);

// TODO: move tidy off
// Specify configuration
$config = [
    'clean'       => TRUE,
    'doctype'     => 'omit',
    'indent'      => 2, // auto
    'output-html' => TRUE,
    'tidy-mark'   => FALSE,
    'wrap'        => 0,
    // HTML5 tags
    'new-blocklevel-tags' => 'article aside audio bdi canvas details dialog figcaption figure footer header hgroup main menu menuitem nav section source summary template track video',
    'new-empty-tags' => 'command embed keygen source track wbr',
    'new-inline-tags' => 'audio command datalist embed keygen mark menuitem meter output progress source time video wbr',
];
$tidy = new tidy;
$tidy->parseString($output, $config, "utf8");
$tidy->cleanRepair();
echo '<!DOCTYPE html>' . PHP_EOL . $tidy;
