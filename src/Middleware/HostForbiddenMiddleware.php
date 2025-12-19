<?php

namespace WebmanTech\Swagger\Middleware;

use WebmanTech\CommonUtils\Ip;
use WebmanTech\CommonUtils\Middleware\BaseMiddleware;
use WebmanTech\CommonUtils\Request;
use WebmanTech\CommonUtils\Response;
use WebmanTech\Swagger\DTO\ConfigHostForbiddenDTO;

class HostForbiddenMiddleware extends BaseMiddleware
{
    protected ConfigHostForbiddenDTO $config;

    public function __construct(
        array|ConfigHostForbiddenDTO $config = []
    )
    {
        $this->config = ConfigHostForbiddenDTO::fromConfig($config);
    }

    public function processRequest(Request $request, \Closure $handler): Response
    {
        if ($this->config->enable) {
            [$can, $ip] = $this->checkIp($request);
            if (!$can) {
                [$can, $host] = $this->checkHost($request);
                if (!$can) {
                    $content = $this->config->forbidden_show_detail;
                    if (is_bool($content)) {
                        $content = $content ? "Forbidden for ip({$ip}) and host({$host})" : 'Forbidden';
                    }
                    return Response::make()
                        ->withStatus(403)
                        ->withBody($content);
                }
            }
        }

        return $handler($request);
    }

    private function checkIp(Request $request): array
    {
        if ($this->config->ip_white_list === null) {
            return [true, ''];
        }
        $ip = $request->getUserIp();
        if (!$ip) {
            return [false, ''];
        }
        if ($this->config->ip_white_list_intranet === true && Ip::isIntranetIp($ip)) {
            return [true, ''];
        }
        if (in_array($ip, $this->config->ip_white_list)) {
            return [true, ''];
        }
        return [false, $ip];
    }

    private function checkHost(Request $request): array
    {
        if ($this->config->host_white_list === null) {
            return [true, ''];
        }
        $host = $request->getHost();
        foreach ($this->config->host_white_list as $needle) {
            if ($needle !== '' && str_contains($host, $needle)) {
                return [true, ''];
            }
        }
        return [false, $host];
    }
}
