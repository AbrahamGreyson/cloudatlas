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
        // todo serializer
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
     * 为指定的协议创建错误解析器。
     *
     * @param string $protocol 要解析的协议（如 query，json 等）。
     *
     * @return callable
     * @throws \UnexpectedValueException
     */
    public static function createErrorParser($protocol)
    {
        static $mapping = [
            'json'      => '',
            'query'     => '',
            'rest-json' => '',
            'rest-xml'  => '',
            'ec2'       => '',
        ];

        if (isset($mapping[$protocol])) {
            return new $mapping[$protocol]();
        }

        throw new \UnexpectedValueException("Unknown protocol: $protocol");
    }

    /**
     * 根据客户端模型创建解析器。
     *
     * @param Service $api 要为哪个 API 创建解析器。
     *
     * @return callable
     * @throws \UnexpectedValueException
     */
    public static function createParser(Service $api)
    {
        static $mapping = [
            'json' => '',
            'query',
            'rest-json',
            'rest-xml',
        ];

        $protocol = $api->getProtocol();
        if (isset($mapping[$protocol])) {
            return new $mapping[$protocol]($api);
        } elseif ('ec2' === $protocol) {
            /** todo \CloudStorage\Api\QueryParser */
            return new QueryParser($api, null, false);
        }

        throw new \UnexpectedValueException(
            'Unknown protocol: ' . $api->getProtocol()
        );
    }

    /**
     * 获取服务 API 的协议。
     *
     * @return string
     */
    public function getProtocol()
    {
        return $this->definition['metadata']['protocol'];
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

    /**
     * 获取 API 端点前缀。
     *
     * @return string
     */
    public function getEndpointPrefix()
    {
        return $this->definition['metadata']['endpointPrefix'];
    }

    /**
     * 获取服务的默认签名版本，如果版本没指定，默认为 v1。
     *
     * @return string
     */
    public function getSignatureVersion()
    {
        return $this->definition['metadata']['signatureVersion'] ?: 'v1';
    }

    /**
     * 根据操作名称检查 API 是否存在某个操作。
     *
     * @param string $name 要检查的操作名称。
     *
     * @return bool
     */
    public function hasOperation($name)
    {
        return isset($this['operations'][$name]);
    }

    /**
     * 根据名称获取一个特定操作。
     *
     * @param string $name 要获取的操作名称。
     *
     * @return Operation
     * @throws \InvalidArgumentException 如果没有找到操作。
     */
    public function getOperation($name)
    {
        if (!$this->hasOperation($name)) {
            if (!isset($this->definition['operations'][$name])) {
                throw new \InvalidArgumentException("Unknown operation: $name");
            }
            $this->operations[$name] = new Operation(
                $this->definition['operations'][$name],
                $this->shapeMap
            );
        }

        return $this->operations[$name];
    }

    /**
     * 获取 API 的所有操作。
     *
     * @return Operation[]
     */
    public function getOperations()
    {
        $result = [];
        foreach ($this->definition['operations'] as $name => $detail) {
            $result[$name] = $this->getOperation($name);
        }

        return $result;
    }

    /**
     * 获取当前 API 的所有元数据或元数据中的某一项。
     *
     * @param null|string $key 要获取的元数据键名，默认为空获取所有元数据。
     *
     * @return mixed|null 对应结果，或没找到对应元数据则 null。
     */
    public function getMetadata($key = null)
    {
        if (!$key) {
            return $this['metadata'];
        } elseif (isset($this->definition['metadata'][$key])) {
            return $this->definition['metadata'][$key];
        }

        return null;
    }

    /**
     * 获取一个可用的分页器配置的关联数组，键为分页器名称，值为分页器配置。
     *
     * @return array
     * @unstable 分页器配置的格式有可能更改。
     */
    public function getPaginators()
    {
        if (!isset($this->paginators)) {
            $response = call_user_func(
                $this->apiProvider,
                'paginator',
                $this->serviceName,
                $this->apiVersion
            );
            $this->paginators = isset($response['pagination'])
                ? $response['pagination']
                : [];
        }

        return $this->paginators;
    }

    /**
     * 根据分页器名称判断服务是否有对应的分页器。
     *
     * @param string $name 分页器名称。
     *
     * @return bool
     */
    public function hasPaginator($name)
    {
        return isset($this->getPaginators()[$name]);
    }

    /**
     * 根据名称获取一个分页器。
     *
     * @param string $name 要获取的分页器名称，这个参数是个典型的操作名称。
     *
     * @return array
     * @throws \UnexpectedValueException 如果分页器不存在。
     * @unstable 分页器的配置格式也许会改变。
     */
    public function getPaginatorConfig($name)
    {
        static $defaults = [
            'input_token'  => null,
            'out_token'    => null,
            'limit_key'    => null,
            'result_key'   => null,
            'more_results' => null,
        ];

        if ($this->hasPaginator($name)) {
            return $this->paginators[$name] + $defaults;
        }

        throw new \UnexpectedValueException("There is no $name paginator"
            . "defined for the {$this->serviceName} service.");
    }

    /**
     * 获取一个可用的等待器配置的关联数组，键为等待器名称，值为等待器配置。
     *
     * @return array
     */
    public function getWaiters()
    {
        if (!isset($this->waiters)) {
            $response = call_user_func(
                $this->apiProvider,
                'waiter',
                $this->serviceName,
                $this->apiVersion
            );
            $this->waiters = isset($response['waiters'])
                ? $response['waiters']
                : [];
        }

        return $this->waiters;
    }

    /**
     * 根据等待器名称判断服务是否有对应的等待器。
     *
     * @param string $name 等待器名称。
     *
     * @return bool
     */
    public function hasWaiter($name)
    {
        return isset($this->getWaiters()[$name]);
    }

    /**
     * 根据名称获取等待器配置。
     *
     * @param string $name 等待器名称。
     *
     * @return mixed
     * @throws \UnexpectedValueException 如果等待器不存在。
     */
    public function getWaiterConfig($name)
    {
        if ($this->hasWaiter($name)) {
            return $this->waiters[$name];
        }

        throw new \UnexpectedValueException("There is no $name waiter defined"
            . "for the {$this->serviceName} service.");
    }

    /**
     * 获取 API 使用的形状表。
     *
     * @return ShapeMap
     */
    public function getShapeMap()
    {
        return $this->shapeMap;
    }
}
