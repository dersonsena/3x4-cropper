<?php

session_start();

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Palette\RGB;
use \Imagine\Image\Point;
use Slim\App;
use Slim\Flash\Messages;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Stream;
use Slim\Http\UploadedFile;
use Slim\Views\PhpRenderer;

defined('DS') or define('DS', DIRECTORY_SEPARATOR);
defined('WEB_PATH') or define('WEB_PATH', dirname(__FILE__));
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__) . DS . '..' . DS);
defined('SRC_PATH') or define('SRC_PATH', ROOT_PATH . 'src');
defined('FILES_PATH') or define('FILES_PATH', WEB_PATH . DS . 'files');
defined('APP_ENV') or define('APP_ENV', (getenv('APP_ENV') ? getenv('APP_ENV') : 'prod'));
defined('APP_ENV_DEV') or define('APP_ENV_DEV', APP_ENV === 'dev');
defined('BASE_URL') or define('BASE_URL', (APP_ENV_DEV ? 'http://dev.3x4cropper.com.br:8080' : 'http://3x4.studiostilo.com.br'));

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
$container['imagine'] = new \Imagine\Gd\Imagine;

$app->any('/', function (Request $request, Response $response) {

    $imgSrc = null;
    $imageName = null;

    if ($request->isPost()) {

        try {

            /** @var UploadedFile $uploadedImage */
            $imageName = date('Ymdhis') . '.jpg';
            $uploadedImage = $request->getUploadedFiles()['photo'];

            if (!in_array($uploadedImage->getClientMediaType(), ['image/jpeg', 'image/jpeg']))
                throw new Exception('Envie um imagem no formato <strong>JPG</strong> ou <strong>PNG</strong>.');

            $palette = new RGB();

            $x = 35;
            $y = 60;
            $collage = $this->imagine->create(new Box(1205, 1807), $palette->color('#666', 100));

            for ($i = 1; $i <= 9; $i++) {
                $image = $this->imagine->open($uploadedImage->file);
                $image->resize(new Box(354, 472));
                $collage->paste($image, new Point($x, $y));

                if ($i % 3 !== 0) {
                    $x += 380;
                } else {
                    $x = 35;
                    $y += 590;
                }
            }

            $collage->save(FILES_PATH . DS . $imageName, [
                'resolution-units' => ImageInterface::RESOLUTION_PIXELSPERINCH,
                'resolution-x' => 300,
                'resolution-y' => 300,
                'jpeg_quality' => 100,
            ]);

            $imgSrc = BASE_URL . "/files/{$imageName}";

            $this->flash->addMessage('success', 'A Foto 3x4 foi gerada com sucesso! Clique no botão <strong>Baixar Imagem</strong> para fazer o download!');

        } catch(Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
        }
    }

    return $this->view->render($response, "index.phtml", [
        'imgSrc' => $imgSrc,
        'imageName' => $imageName,
        'flash' => $this->flash
    ]);
});

$app->get('/download/{filename}', function (Request $request, Response $response) {

    $filename = $request->getAttribute('filename');
    $file = FILES_PATH . DS . $filename;
    $fh = fopen($file, 'rb');
    $stream = new Stream($fh); // create a stream instance for the response body

    return $response->withHeader('Content-Type', 'application/force-download')
        ->withHeader('Content-Type', 'application/octet-stream')
        ->withHeader('Content-Type', 'application/download')
        ->withHeader('Content-Description', 'File Transfer')
        ->withHeader('Content-Transfer-Encoding', 'binary')
        ->withHeader('Content-Disposition', 'attachment; filename="'.basename($file).'"')
        ->withHeader('Expires', '0')
        ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
        ->withHeader('Pragma', 'public')
        ->withHeader('Content-Length', filesize($file))
        ->write($stream);
});

$app->run();