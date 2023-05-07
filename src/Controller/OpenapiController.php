<?php

namespace WebmanTech\Swagger\Controller;

use InvalidArgumentException;
use OpenApi\Generator;
use OpenApi\Util;
use Webman\Http\Response;

class OpenapiController
{
    protected $config = [
        'scan_paths' => [],
    ];
    private $cacheKey;
    private static $memoryCached = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
        $this->cacheKey = uniqid();
    }

    public function index(): Response
    {
        $assetBasePath = 'https://unpkg.com/swagger-ui-dist';

        $html = <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="utf-8" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <meta
                    name="description"
                    content="SwaggerUI"
                />
                <title>SwaggerUI</title>
                <link rel="stylesheet" href="{$assetBasePath}/swagger-ui.css" />
            </head>
            <body>
            <div id="swagger-ui"></div>
            <script src="{$assetBasePath}/swagger-ui-bundle.js" crossorigin></script>
            <script>
                window.onload = () => {
                    window.ui = SwaggerUIBundle({
                        // @link https://github.com/swagger-api/swagger-ui/blob/master/docs/usage/configuration.md
                        dom_id: '#swagger-ui',
                        url: window.location.pathname + '/doc',
                        filter: '',
                        persistAuthorization: true,
                    });
                };
            </script>
            </body>
            </html>
HTML;
        return response($html);
    }

    public function doc(): Response
    {
        if (!isset(static::$memoryCached[$this->cacheKey])) {
            if (!$this->config['scan_paths']) {
                throw new InvalidArgumentException('scan_paths must be set');
            }
            $openapi = Generator::scan(Util::finder($this->config['scan_paths']));
            $yaml = $openapi->toYaml();

            static::$memoryCached[$this->cacheKey] = $yaml;
        }
        $yaml = static::$memoryCached[$this->cacheKey];

        return response($yaml, 200, [
            'Content-Type' => 'application/x-yaml',
        ]);
    }
}