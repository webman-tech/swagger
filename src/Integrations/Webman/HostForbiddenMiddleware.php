<?php

namespace WebmanTech\Swagger\Integrations\Webman;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use WebmanTech\Swagger\DTO\ConfigHostForbiddenDTO;

final class HostForbiddenMiddleware implements MiddlewareInterface
{
    protected ConfigHostForbiddenDTO $config;

    public function __construct(
        array|ConfigHostForbiddenDTO $config = []
    )
    {
        $this->config = ConfigHostForbiddenDTO::fromConfig($config);
    }

    /**
     * @inheritDoc
     */
    public function process(Request $request, callable $handler): Response
    {
        if ($this->config->enable) {
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
        if ($this->config->ip_white_list === null) {
            return [true, ''];
        }
        $ip = $request->getRealIp();
        if ($this->config->ip_white_list_intranet === true && Request::isIntranetIp($ip)) {
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
        $host = (string)$request->host();
        foreach ($this->config->host_white_list as $needle) {
            if ($needle !== '' && str_contains($host, (string)$needle)) {
                return [true, ''];
            }
        }
        return [false, $host];
    }
}
