<?php

namespace WebmanTech\Swagger\DTO;

use WebmanTech\DTO\BaseConfigDTO;
use WebmanTech\Swagger\Helper\ConfigHelper;

final class ConfigHostForbiddenDTO extends BaseConfigDTO
{
    public function __construct(
        public bool        $enable = true,
        public ?bool       $ip_white_list_intranet = true, // 是否允许所有内网访问，为 null 时不检查
        public ?array      $ip_white_list = [], // 允许访问的指定 ip，为 null 时不检查
        public ?array      $host_white_list = [], // 允许访问的指定 host，为 null 时不检查
        public bool|string $forbidden_show_detail = true, // 阻止时显示详情信息
    )
    {
    }

    protected static function getAppConfig(): array
    {
        return ConfigHelper::get('app.host_forbidden', []);
    }
}
