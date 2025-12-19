<?php

namespace WebmanTech\Swagger\Middleware;

use Webman\Http\Request as WebmanRequest;
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
        if ($this->config->ip_white_list_intranet === true && $this->isIntranetIp($ip)) {
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

    private function isIntranetIp(string $ip): bool
    {
        if (class_exists(WebmanRequest::class)) {
            return WebmanRequest::isIntranetIp($ip);
        }

        return $this->isIntranetIpFromWebman($ip);
    }

    /**
     * 从 Webman 复制来的
     * @param string $ip
     * @return bool
     */
    private function isIntranetIpFromWebman(string $ip): bool
    {
        // Not validate ip .
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        // Is intranet ip ? For IPv4, the result of false may not be accurate, so we need to check it manually later .
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }
        // Manual check only for IPv4 .
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        // Manual check .
        $reservedIps = [
            1681915904 => 1686110207, // 100.64.0.0 -  100.127.255.255
            3221225472 => 3221225727, // 192.0.0.0 - 192.0.0.255
            3221225984 => 3221226239, // 192.0.2.0 - 192.0.2.255
            3227017984 => 3227018239, // 192.88.99.0 - 192.88.99.255
            3323068416 => 3323199487, // 198.18.0.0 - 198.19.255.255
            3325256704 => 3325256959, // 198.51.100.0 - 198.51.100.255
            3405803776 => 3405804031, // 203.0.113.0 - 203.0.113.255
            3758096384 => 4026531839, // 224.0.0.0 - 239.255.255.255
        ];
        $ipLong = ip2long($ip);
        foreach ($reservedIps as $ipStart => $ipEnd) {
            if (($ipLong >= $ipStart) && ($ipLong <= $ipEnd)) {
                return true;
            }
        }
        return false;
    }
}
