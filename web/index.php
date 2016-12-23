<?php

session_start();

use Slim\App;
use Slim\Flash\Messages;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\PhpRenderer;

defined('DS') or define('DS', DIRECTORY_SEPARATOR);
defined('WEB_PATH') or define('WEB_PATH', dirname(__FILE__));
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__) . DS . '..' . DS);
defined('SRC_PATH') or define('SRC_PATH', ROOT_PATH . 'src');

require_once ROOT_PATH . DS . 'vendor'. DS . 'autoload.php';

$app = new App([
    'settings' => [
        'displayErrorDetails' => true,
        'addContentLengthHeader' => true
    ]
]);

/** @var Slim\Container $container */
$container = $app->getContainer();

$container['view'] = new PhpRenderer(ROOT_PATH . DS . 'views');
$container['flash'] = new Messages;

$app->get('/', function (Request $request, Response $response) {
    return $this->view->render($response, "index.phtml", []);
});

$app->get('/hello/{name}', function (Request $request, Response $response) {
    $name = $request->getAttribute('name');

    return $this->view->render($response, "hello.phtml", [
        'name' => $name
    ]);
});

$app->run();