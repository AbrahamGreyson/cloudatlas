<?php

/*
 * CloudAtlas
 * @link  : https://github.com/AbrahamGreyson/cloudatlas
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudAtlas;
use GuzzleHttp\ClientInterface;

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
 * 从 CloudAtlas 服务清单文件中取回内置的服务数据。
 *
 * @param string $service 大小写不敏感的服务命名空间或终端前缀。
 *
 * @return array
 * @throws \InvalidArgumentException 服务不支持。
 */
function manifest($service = null)
{
    // 载入 API 清单并为命名空间的小写创建别名。
    static $manifest = [];
    static $aliases = [];
    if (empty($manifest)) {
        $manifest = require(__DIR__ . '/Api/data/manifest.php');
        foreach ($manifest as $endpoint => $info) {
            $alias = strtolower($info['namespace']);
            // todo endpoint
            if ($alias !== $endpoint) {
                $aliases[$alias] = $endpoint;
            }
        }
    }

    // 如果没指定服务名称，则返回整个 API 清单
    if ($service === null) {
        return $manifest;
    }

    // 检查 API 清单中的服务返回对应数据。
    $service = strtolower($service);
    if (isset($manifest[$service])) {
        // todo endpoint
        return $manifest[$service] + ['endpoint' => $service];
    } elseif (isset($aliases[$service])) {
        return manifest($aliases[$service]);
    } else {
        throw new \InvalidArgumentException(
            "The service \"{$service}\" is not provided by CloudAtlas."
        );
    }
}

/**
 * 返回一个函数，函数中依次调用传入的可变函数直到返回一个非空值。函数依次调用传入可变函数时，将
 * 会使用对应的参数。
 *
 * <code>
 * $a = function ($x, $y) { return null; }
 * $b = function ($x, $y) { return $x + $y; }
 * $fn = \CloudAtlas\orChain($a, $b);
 * echo $fn(1, 2); // 3
 * </code>
 *
 * @return callable
 */
function orChain()
{
    $fns = func_get_args();

    return function () use ($fns) {
        $args = func_get_args();
        foreach ($fns as $fn) {
            $result = $args ? call_user_func_array($fn, $args) : $fn();
            if ($result) {
                return $result;
            }
        }

        return null;
    };
}

/**
 * 载入 API 配置文件返回其中数组。
 *
 * @param string $path 要载入的文件。
 *
 * @return array API 配置关联数组。
 * @throws \InvalidArgumentException 没找到文件或文件不可读。
 */
function loadApiFileOrThrow($path)
{
    if (file_exists($path) && $apis = @include("$path")) {
        return $apis;
    }
    throw new \InvalidArgumentException(
        sprintf("File not found or can not be read: %s", $path)
    );
}

/**
 * 根据 HTTP 客户端可用情况创建默认的 HTTP 处理器。
 *
 * @return callable
 */
function defaultHttpHandler()
{
    $version = (string) ClientInterface::VERSION;
    // todo guzzle handler
    return function () use ($version) {
    };
}
