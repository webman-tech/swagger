<?php

namespace WebmanTech\Swagger\Middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class HostForbiddenMiddleware implements MiddlewareInterface
{
    protected $config = [
        'enable' => true,
        'ip_white_list_intranet' => true, // 允许所有内网访问
        'ip_white_list' => [], // 允许访问的 ip
        'host_white_list' => [], // 允许访问的 host
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
            [$can, $ip] = $this->checkIp($request);
            if (!$can) {
                [$can, $host] = $this->checkHost($request);
                if (!$can) {
                    return response("Forbidden for ip({$ip}) and host({$host})", 403);
                }
            }
        }

        return $handler($request);
    }

    private function checkIp(Request $request): array
    {
        if ($this->config['ip_white_list_intranet'] === null || $this->config['ip_white_list'] === null) {
            return [true, ''];
        }
        $ip = $request->getRealIp();
        if ($this->config['ip_white_list_intranet'] && Request::isIntranetIp($ip)) {
            return [true, ''];
        }
        if (in_array($ip, $this->config['ip_white_list'] ?? [])) {
            return [true, ''];
        }
        return [false, $ip];
    }

    private function checkHost(Request $request): array
    {
        if ($this->config['host_white_list'] === null) {
            return [true, ''];
        }
        $host = $request->host();
        foreach ($this->config['host_white_list'] as $needle) {
            if ($needle !== '' && strpos($host, $needle) !== false) {
                return [true, ''];
            }
        }
        return [false, $host];
    }
}
