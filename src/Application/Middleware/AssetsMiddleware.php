<?php
declare(strict_types=1);

namespace Externals\Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Stratify\Http\Middleware\Middleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

/**
 * Serve assets.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class AssetsMiddleware implements Middleware
{
    /**
     * @var string
     */
    private $publicPath;

    public function __construct(string $publicPath)
    {
        $this->publicPath = rtrim($publicPath, '/') . '/';
    }

    public function __invoke(ServerRequestInterface $request, callable $next) : ResponseInterface
    {
        $path = $request->getUri()->getPath();

        $file = $this->publicPath . $path;

        if (!is_file($file)) {
            return $next($request);
        }

        $body = new Stream($file);

        $array = explode('.', $file);
        $extension = strtolower(array_pop($array));
        $mimeType = self::MIME_TYPES[$extension] ?? 'application/octet-stream';

        return new Response($body, 200, [
            'Content-Type' => $mimeType,
        ]);
    }

    private const MIME_TYPES = [
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',

        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    ];
}
