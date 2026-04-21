<?php

namespace WebmanTech\Swagger\Middleware;

use WebmanTech\CommonUtils\Middleware\BaseMiddleware;
use WebmanTech\CommonUtils\Request;
use WebmanTech\CommonUtils\Response;
use WebmanTech\Swagger\DTO\ConfigBasicAuthDTO;

class BasicAuthMiddleware extends BaseMiddleware
{
    protected ConfigBasicAuthDTO $config;

    public function __construct(
        array|ConfigBasicAuthDTO $config = []
    )
    {
        $this->config = ConfigBasicAuthDTO::fromConfig($config);
    }

    public function processRequest(Request $request, \Closure $handler): Response
    {
        if ($this->config->enable) {
            $authHeader = $request->header('authorization') ?? '';
            if (!$this->checkCredentials($authHeader)) {
                return Response::make()
                    ->withStatus(401)
                    ->withHeaders(['WWW-Authenticate' => 'Basic realm="' . $this->config->realm . '"'])
                    ->withBody('Unauthorized');
            }
        }

        return $handler($request);
    }

    private function checkCredentials(string $authHeader): bool
    {
        if (!str_starts_with($authHeader, 'Basic ')) {
            return false;
        }

        $decoded = base64_decode(substr($authHeader, 6), true);
        if ($decoded === false) {
            return false;
        }

        $colonPos = strpos($decoded, ':');
        if ($colonPos === false) {
            return false;
        }

        $username = substr($decoded, 0, $colonPos);
        $password = substr($decoded, $colonPos + 1);

        return $username === $this->config->username && $password === $this->config->password;
    }
}
