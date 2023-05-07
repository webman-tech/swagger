<?php

namespace WebmanTech\Swagger\Middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class HostForbiddenMiddleware implements MiddlewareInterface
{
    protected $config = [
        'enable' => true,
        'host_white_list_default' => [
            // 常规的内网地址允许访问
            '127.0.0.1',
            'localhost',
            '192.168.',
            '172.16.',
            '10.',
        ],
        'host_white_list' => [],
    ];

    public function __construct(array $config = [])
    {
        $this->config = array_merge(
            $this->config,
            config('plugin.webman-tech.swagger.app.host_forbidden', []),
            $config
        );
    }

    /**
     * @inheritDoc
     */
    public function process(Request $request, callable $handler): Response
    {
        if ($this->config['enable']) {
            $host = $request->host();
            if (!$this->isInWhiteList($host)) {
                return response('Forbidden for: ' . $host, 403);
            }
        }

        return $handler($request);
    }

    private function isInWhiteList(string $host): bool
    {
        $whiteList = array_merge($this->config['host_white_list_default'], $this->config['host_white_list']);
        foreach ($whiteList as $needle) {
            if ($needle !== '' && strpos($host, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
