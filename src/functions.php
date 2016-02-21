<?php

/*
 * CloudStorage
 * @link  : https://github.com/AbrahamGreyson/cloudstorage
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudStorage;

/**
 * 调试函数，用来描述给定值的类型或类。
 *
 * @param mixed $input
 *
 * @return string 返回一个字符串包含给定变量的类型，如果给定的是对象，则返回类名。
 */
function describeType($input)
{
    switch (gettype($input)) {
        case 'object':
            return 'object(' . get_class($input) . ')';
        case 'array':
            return 'array(' . count($input) . ')';
        default:
            ob_start();
            var_dump($input);

            // 统一化浮点数
            return str_replace('double(', 'float(', rtrim(ob_get_clean()));
    }
}

/**
 * 从 CloudStorage 服务清单文件中取回内置的服务数据。
 *
 * @param string $service 大小写不敏感的服务命名空间或终端前缀。
 *
 * @return array
 * @throws \InvalidArgumentException 服务不支持。
 */
function manifest($service = null)
{
    // 载入 API 清单并为小写命名空间创建别名
    static $manifest = [];
    static $aliases = [];
    if (empty($manifest)) {
        $manifest = require('./Api/data/manifest.php');
        foreach ($manifest as $endpoint => $info) {
            $alias = strtolower($info['namespace']);
            if ($alias !== $endpoint) {
                $aliases[$alias] = $endpoint;
            }
        }
    }

    // 如果没指定服务名称，则返回整个 API 清单
    if ($service === null) {
        return $manifest;
    }

    // 检查 API 清单中的服务信息。
    $service = strtolower($service);
    if (isset($manifest[$service])) {
        return $manifest[$service] + ['endpoint' => $service];
    } elseif (isset($aliases[$service])) {
        return manifest($aliases[$service]);
    } else {
        throw new \InvalidArgumentException(
            "The service \"{$service}\" is not provided by CloudStorage."
        );
    }
}
