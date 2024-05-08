<?php

namespace WebmanTech\Swagger\DTO;

use WebmanTech\Swagger\Helper\ConfigHelper;

/**
 * @property bool $enable
 * @property bool|null $ip_white_list_intranet 是否允许所有内网访问，为 null 时不检查
 * @property array|null $ip_white_list 允许访问的指定 ip，为 null 时不检查
 * @property array|null $host_white_list 允许访问的指定 host，为 null 时不检查
 */
class ConfigHostForbiddenDTO extends BaseDTO
{
    protected function initData()
    {
        $this->_data = array_merge(
            [
                'enable' => true,
                'ip_white_list_intranet' => true,
                'ip_white_list' => [],
                'host_white_list' => [],
            ],
            ConfigHelper::get('app.host_forbidden', []),
            $this->_data
        );
    }
}
