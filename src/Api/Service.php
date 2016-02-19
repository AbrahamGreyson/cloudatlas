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
 *
 * @package CloudStorage\Api
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
            'shapes' => [],
            'metadata' => [],
        ];
        static $defaultMeta = [
            'version' => null,
            'service' => null,
            'endpoint'  => null,
            'signature' => null,
            'signatureVersion' => null,
            'protocol' => null
        ];

        $definition += $default;
        $definition['metadata'] += $defaultMeta;
        $this->apiProvider = $provider;
    }
}
