<?php

/*
 * CloudStorage
 * @link  : https://github.com/AbrahamGreyson/cloudstorage
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudStorage\Api;

/**
 * 代表一个云服务的 API 模型。
 */
class Service extends AbstractModel
{
    private $apiProvider;

    private $service;

    private $version;

    private $operations = [];

    public function __construct(array $definition, callable $provider)
    {
        static $default = [
            'operations' => [],
            'shapes'     => [],
            'metadata'   => [],
        ];
        static $defaultMeta = [
            'version'          => null,
            'service'          => null,
            'endpoint'         => null,
            'signature'        => null,
            'signatureVersion' => null,
            'protocol'         => null,
        ];

        $definition += $default;
        $definition['metadata'] += $defaultMeta;
        $this->apiProvider = $provider;
    }

    /**
     * 为给定的 API 对象创建请求序列器。
     *
     * @param Service $api      包含请求协议的 API。
     * @param string  $endpoint 发送请求的端点。
     *
     * @return callable
     * @throws \UnexpectedValueException
     */
    public static function createSerializer(Service $api, $endpoint)
    {
        static $mapping = [
            'json'      => '',
            'query'     => '',
            'rest-json' => '',
            'rest-xml'  => '',
        ];

        $protocol = $api->getProtocol();

        if (isset($mapping[$protocol])) {
            return new $mapping[$protocol]($api, $endpoint);
        }

        throw new \UnexpectedValueException(
            'Unknown protocol: '.$protocol
        );
    }

    /**
     * 获取服务 API 的协议。
     *
     * @return string
     */
    private function getProtocol()
    {
        return $this->definition['metadata']['protocol'];
    }
}
