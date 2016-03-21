<?php

/*
 * CloudAtlas
 * @link  : https://github.com/AbrahamGreyson/cloudatlas
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudAtlas\Api;

/**
 * 代表一个云服务的 API 模型。
 *
 * @package CloudAtlas\Api
 */
class Service extends AbstractModel
{
    /**
     * @var callable
     */
    private $apiProvider;

    /**
     * @var string
     */
    private $serviceName;

    /**
     * @var string
     */
    private $apiVersion;

    /**
     * @var Operation[]
     */
    private $operations = [];

    /**
     * @var array
     */
    private $paginators = null;

    /**
     * @var array
     */
    private $waiters = null;

    /**
     * @param array    $definition
     * @param callable $provider
     */
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
        $this->definition = $definition;
        $this->apiProvider = $provider;
        parent::__construct($definition, new ShapeMap($definition['shapes']));
        $this->serviceName = $this->getServiceName();
        $this->apiVersion = $this->getApiVersion();
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
            'Unknown protocol: ' . $protocol
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

    public static function createErrorParser($getProtocol)
    {
    }

    /**
     * 获取 API 的服务名。
     *
     * @return string
     */
    public function getServiceName()
    {
        return $this->definition['metadata']['serviceName'];
    }

    /**
     * 获取 API 的版本号。
     *
     * @return string
     */
    public function getApiVersion()
    {
        return $this->definition['metadata']['apiVersion'];
    }
}
