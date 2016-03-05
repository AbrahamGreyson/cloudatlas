<?php

/*
 * CloudStorage
 * @link  : https://github.com/AbrahamGreyson/cloudstorage
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudStorage\Api;

use CloudStorage\Exceptions\UnresolvedApiException;

/**
 * API 提供者。
 *
 * 是一个函数，它接收分类（如 api，paginator，waiter），服务名称（如 upyun，qiniu）
 * 和版本，并返回一个 API 数据的数组。如果提供的信息找不到对应 API 数据则返回 null。
 *
 * 你可以使用 {@see Apiprovider::resolve} 方法包装调用的 API 提供者去确保 API 数据已经
 * 被创建。如果没有创建 API 数据，则该方法会抛出
 * {@see CloudStorage\Exceptions\UnresolvedApiException} 异常。
 *
 * <code>
 * use CloudStorage\Api\ApiProvider;
 * $provider = ApiProvider::defaultProvider();
 * // 返回数组或 null。
 * $data = $provider('api', 'upyun', 'v1');
 * // 返回数组或抛出异常。
 * $data = ApiProvider::resolve($provider, 'api', 'upyun', 'v1');
 * </code>
 *
 * 可以使用 {@see CloudStorage\orChain} 函数组合多个提供者至单独的一个。这个函数接受
 * 提供者作为参数，返回一个将会依次调用所有提供者直到非空值被返回的函数。
 *
 * <code>
 * $a = ApiProvider::filesystem(sys_get_temp_dir() . 'test-apis');
 * $b = ApiProvider::manifest();
 *
 * $c = \CloudStorage\orChain($a, $b);
 * $data = $c('api', 'testApi', 'v1'); // $a 处理这个。
 * $data = $c('api', 'qiniu', 'v1'); // $b 处理这个。
 * $data = $c('api', 'invalid', '2099-12-31'); // 哪个都不能处理无效的 API 数据请求。
 * </code>
 *
 * @package CloudStorage\Api
 */
class ApiProvider
{
    /**
     * @var array 公共 API 的文件名后缀映射。
     */
    private static $typeMap = [
        'api'       => 'api',
        'paginator' => 'paginator',
        'waiter'    => 'waiters',
        'docs'      => 'docs',
    ];

    /**
     * @var array API 清单。
     */
    private $manifest;

    /**
     * @var string 包含服务模型的目录。
     */
    private $modelsDir;

    /**
     * 默认 API 提供者。
     *
     * 本提供者从 `data` 目录加载预定义的 API 清单。
     *
     * @return ApiProvider
     */
    public static function defaultProvider()
    {
        return new self(__DIR__ . '/data', \CloudStorage\manifest());
    }

    /**
     * @param string $modelsDir 服务模型的目录。
     * @param array  $manifest  服务数据清单中的 API 版本。
     */
    private function __construct($modelsDir, array $manifest = null)
    {
        $this->manifest = $manifest;
        $this->modelsDir = rtrim($modelsDir, '/');
        if (!is_dir($this->modelsDir)) {
            throw new \InvalidArgumentException(
                "The specified models directory , {$modelsDir} , was not found."
            );
        }
    }

    /**
     * 从指定目录加载 API 数据。
     *
     * 如果指定的版本为 `latest`，提供者必须遍历整个目录去找到哪个版本是最新的可用 API 版本。
     *
     * @param string $dir 　包含服务模型的目录。
     *
     * @return ApiProvider
     * @throws \InvalidArgumentException 如果参数的 `$dir` 无效。
     */
    public static function filesystem($dir)
    {
        return new self($dir);
    }

    public static function manifest($dir, $manifest)
    {
        return new self($dir, $manifest);
    }

    /**
     * 针对特定服务取回有效的版本列表。
     *
     * @param string $service 服务名称。
     *
     * @return array
     */
    public function getVersions($service)
    {
        if (!isset($this->manifest)) {
            $this->buildVersionList($service);
        }

        if (!isset($this->manifest[$service]['versions'])) {
            return [];
        }

        return array_values(array_unique($this->manifest[$service]['versions']));
    }

    /**
     *
     * 解析 API 提供者确保返回非空值。
     *
     * @param callable $provider 要调用的提供者。
     * @param string   $type     数据类型（api，waiter，paginator）。
     * @param string   $service  服务名称
     * @param string   $version  API 版本
     *
     * @return array
     * @throws UnresolvedApiException
     */
    public static function resolve(callable $provider, $type, $service, $version)
    {
        // 执行提供者并返回结果（如果有结果的话）。
        $result = $provider($type, $service, $version);
        if (is_array($result)) {
            return $result;
        }

        // 根据输入信息抛出异常。
        if (!isset(self::$typeMap[$type])) {
            $msg = "The type must be one of: " . implode(', ', self::$typeMap);
        } elseif ($service) {
            $msg = "The {$service} service does not have version: {$version}.";
        } else {
            $msg = "You must specify a service name to retrieve its API data.";
        }

        throw new UnresolvedApiException($msg);
    }

    /**
     * 执行这个 API 提供者。
     *
     * @param string $type    数据类型（api、waiter、paginator）。
     * @param string $service 服务名。
     * @param string $version API 版本。
     *
     * @return array|null
     */
    public function __invoke($type, $service, $version)
    {
        // 解析类型或返回空
        if (isset(self::$typeMap[$type])) {
            $type = self::$typeMap[$type];
        } else {
            return null;
        }

        // 解析版本或返回空
        if (!isset($this->manifest)) {
            $this->buildVersionList($service);
        }

        if (!isset($this->manifest[$service]['versions'][$version])) {
            return null;
        }

        $version = $this->manifest[$service]['versions'][$version];
        $path = "{$this->modelsDir}/{$service}/{$version}/{$type}.php";

        try {
            return \CloudStorage\loadApiFileOrThrow($path);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * 通过通配整个目录，构建特定服务的可用版本列表。
     *
     * @param string $service 服务名称。
     */
    private function buildVersionList($service)
    {
        $dir = "{$this->modelsDir}/{$service}";

        if (!is_dir($dir)) {
            return;
        }
        // 取得版本号，移除 . 和 .. 并降序排列。
        $results = array_diff(scandir($dir, SCANDIR_SORT_DESCENDING), [
            '..',
            '.']);

        if (!$results) {
            $this->manifest[$service] = ['versions' => []];
        } else {
            $this->manifest[$service] = [
                'versions' => [
                    'latest' => $results[0],
                ],
            ];
            $this->manifest[$service]['versions'] += array_combine($results, $results);
        }
    }
}
