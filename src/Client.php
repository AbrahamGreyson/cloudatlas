<?php

/*
 * CloudAtlas
 * @link  : https://github.com/AbrahamGreyson/cloudatlas
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudAtlas;

use CloudAtlas\Contracts\ClientInterface;
use CloudAtlas\Contracts\CommandInterface;
use CloudAtlas\Contracts\ResultInterface;
use CloudAtlas\Exceptions\CloudAtlasException;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

/**
 * CloudAtlas 客户端，用来和云服务进行交互。
 * @method Promise PutObject($bucket, $key, $stream);
 * @method Promise Copy($key, $to);
 *
 * @package CloudAtlas
 */
class Client implements ClientInterface
{
    /**
     * @var Service
     */
    private $api;

    /**
     * @var array
     */
    private $config;

    /**
     * @var callable
     */
    private $credentialProvider;

    /**
     * @var callable
     */
    private $signatureProvider;

    /**
     * @var Uri|string
     */
    private $endpoint;

    /**
     * @var HandlerList
     */
    private $handlerList;

    /**
     * @var array
     */
    private $defaultRequestOptions;

    /**
     * 客户端构造方法接受一个关联数组作为参数，以下是关联数组的可用选项：
     *
     * - credentials：（CredentialsInterface|array|bool|callable）
     *   指定用来给请求签名的凭证。可以提供：
     *   a) CredentialsInterface 对象。
     *   b) 一个包含 key，secret 和一个可选 token 的关联数组。
     *   c) `false` 不使用凭证。
     *   d) 一个 callable 类型的凭证提供者用来创建凭证或返回 null。
     *      见 CredentialProvider，里面提供了一组内建的凭证提供者列表。
     *   如果没有提供凭证，客户端将尝试从环境变量中获取它们。
     * - endpoint：（string）连接服务接口的完整 URI。只有当你需要自定义连接端点时才需要
     *   设置这个参数，否则将使用各个云服务默认的。
     * - handler：（callable） 一个接收 CommandInterface 和 RequestInterface，并返回
     *   promise 的函数，promise 代表一个完成了的 ResultInterface 或失败了的
     *   CloudAtlasException。处理器并不接收下一个处理器，它是被期望完成命令的终端。
     *   如果没有提供处理器，则使用默认的 Guzzle 处理器。
     * - profile：（string）当凭证是从配置文件中加载时，允许你指定具体使用哪一个身份信息。
     *   这个设置还会覆写 CLOUD_PROFILE 环境变量。注意：指定 profile 会导致 credentials
     *   设置中的 key 被忽略，因为 profile 意味着使用的凭证不是从构造方法中传入的。
     * - scheme：（string，默认为 "https"）连接云服务时使用的 URI 方案。默认
     *   将使用 https（也就是利用 SSL/TLS 进行连接） 的 endpoint。可以通过设置 scheme 为
     *   http 而使用未加密的连接。鉴于大陆互联网环境，建议不要更改此选项。
     * - retries：（int，默认为 3）配置客户端允许的最大重试次数（设为 0 禁用重试）。
     * - validate：（bool，默认为 `true`） 设为 `false` 禁用客户端参数验证。
     * - signature_provider：（callable）接受一个签名版本号，返回一个
     *   SignatureInterface 或 null。用来为客户端请求进行签名。
     *   见 SignatureProvider，里面提供了一组内建的签名提供者列表。
     *
     * @param array $arguments
     */
    public function __construct(array $arguments)
    {
        list($arguments['service'], $arguments['exceptionClass']) = $this->parseClass();

        $this->handlerList = new HandlerList();
        $clientConstructor = new ClientConstructor(static::getDefaultArguments());
        $config = $clientConstructor->resolve($arguments, $this->handlerList);
        $this->api = $config['api'];
        $this->credentialProvider = $config['credentialProvider'];
        $this->signatureProvider = $config['signatureProvider'];
        $this->endpoint = new Uri($config['endpoint']);
        $this->config = $config['config'];
        $this->defaultRequestOptions = $config['http'];
        $stack = static::getHandlerList();
        static::addSignatureMiddleware();
        //static::
    }

    /**
     * 获取默认的客户端构造参数用于实例化客户端。
     * @return array
     */
    public static function getDefaultArguments()
    {
        return ClientConstructor::getDefaultArguments();
    }

    /**
     * @return mixed
     */
    // abstract public function addSignatureMiddleware();

    /**
     * 根据名称创建并执行一个 REST API 操作命令。
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        $parameters = isset($arguments[0]) ? $arguments[0] : [];
        if ('Async' === substr($name, -5)) {
            return $this->executeAsync(
                $this->getCommand(substr($name, 0, -5), $parameters)
            );
        }

        return $this->execute($this->getCommand($name, $parameters));
    }

    /**
     * 通过操作名称创建一个命令。
     *
     * 特殊键的参数可能会传递进来，用来控制命令的行为，
     * 包括：
     * - @http：设置该命令在数据请求过程中传输数据特殊选项的关联数组，可用的键为
     *   proxy，verify，timeout，connect_timeout，debug，delay，headers。
     *
     * @param string $name 命令所使用的操作名称。
     * @param array  $args 传递给命令的额外参数。
     *
     * @return CommandInterface
     * @throws \InvalidArgumentException 如果命令不存在。
     */
    public function getCommand($name, array $args = [])
    {
        if (!isset($this->api['operations'][$name])) {
            $name = ucfirst($name);
            if (!isset($this->api['operations'][$name])) {
                throw new \InvalidArgumentException("Operation not found:
                $name");
            }
        }

        if (!isset($args['@http'])) {
            $args['@http'] = $this->defaultRequestOptions;
        } else {
            $args['@http'] += $this->defaultRequestOptions;
        }

        return new Command($name, $args, clone $this->getHandlerList());
    }

    /**
     * 执行一个命令。
     *
     * @param CommandInterface $command 要执行的命令。
     *
     * @return ResultInterface
     */
    public function execute(CommandInterface $command)
    {
        return $this->executeAsync($command)->wait();
    }

    /**
     * 异步执行一个命令。
     *
     * @param CommandInterface $command 要执行的命令。
     *
     * @return PromiseInterface
     */
    public function executeAsync(CommandInterface $command)
    {
        $handler = $command->getHandlerList()->resolve();

        return $handler($command);
    }

    /**
     * 返回一个已完成的 {@see CredentialsInterface} 对象的 promise。
     *
     * 如果你需要同步获得凭证，在返回的 promise 对象上调用 wait() 方法即可。
     *
     * @return PromiseInterface
     */
    public function getCredentials()
    {
        $fn = $this->credentialProvider;

        return $fn();
    }

    /**
     * 获取默认的连接点或基本 URL，供客户端使用。
     *
     * @return UriInterface
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * 获取一个客户端配置的值。
     *
     * @param string|null $options 要取回的配置选项，传递空取回所有配置。
     *
     * @return string|array|null
     */
    public function getConfig($options = null)
    {
        return $options === null
            ? $this->config
            : (
            isset($this->config[$options]) ? $this->config[$options] : null
            );
    }

    /**
     * 获取执行命令所用到的处理器列表。
     *
     * 这个列表可以被修改，增加中间件或更改底层处理器去发送 HTTP 请求。
     *
     * @return HandlerList
     */
    public function getHandlerList()
    {
        return $this->handlerList;
    }

    /**
     * 为指定操作获取一个资源迭代器。
     *
     * @param string $name 想要取回的迭代器名称。
     * @param array  $args 每个命令所使用的命令参数。
     *
     * @return \Iterator
     * @throws \UnexpectedValueException 如果迭代器配置无效。
     */
    public function getIterator($name, array $args = [])
    {
        $config = $this->api->getPaginatorConfig($name);
        if (!$config['result_key']) {
            throw new \UnexpectedValueException(sprintf(
                'There are no resources to iterate for the %s
                operation of $s', $name, $this->api['serviceFullName']));
        }

        $key = is_array($config['result_key'])
            ? $config['result_key'][0]
            : $config['result_key'];

        if ($config['output_token'] && $config['input_key']) {
            return $this->getPaginator($name, $args)->search($key);
        }

        $result = $this->execute($this->getCommand($name, $args))->search($key);

        return new \ArrayIterator((array)$result);
    }

    /**
     * 为指定操作获取一个结果分页器。
     *
     * @param string $name 迭代器使用的操作名称。
     * @param array  $args 每个命令所使用的命令参数。
     *
     * @return ResultPaginator
     * @throws \UnexpectedValueException 如果迭代器配置无效。
     */
    public function getPaginator($name, array $args = [])
    {
        $config = $this->api->getPaginatorConfig($name);

        return new ResultPaginator($this, $name, $args, $config);
    }

    /**
     * 获取和对应云服务客户端相关联的服务描述。
     *
     * @return \CloudAtlas\Api\Service
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * 根据客户端类名解析服务名和对应的异常类并返回。
     *
     * @return array
     */
    private function parseClass()
    {
        $class = get_class($this);

        if ($class === __CLASS__) {
            return ['', CloudAtlasException::class];
        }

        $service = substr($class, strrpos($class, '\\') + 1, -6);

        return [
            strtolower($service),
            "\\CloudAtlas\\{$service}\\Exceptions\\{$service}Exception",
        ];
    }
}
