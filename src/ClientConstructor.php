<?php

/*
 * CloudStorage
 * @link  : https://github.com/AbrahamGreyson/cloudstorage
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudStorage;

use Aws\Credentials\CredentialProvider;
use CloudStorage\Credentials\CredentialsInterface;
use CloudStorage\Exceptions\CloudStorageException;
use GuzzleHttp\RetryMiddleware;

/**
 * 客户端构造者。
 *
 * @internal 内部使用，解析一系列默认配置至客户端。
 *
 * @package CloudStorage
 */
class ClientConstructor
{
    /**
     * @var array
     */
    private $arguments;

    /**
     * @var array 类型和类型判断函数之间的映射。
     */
    private static $typeMap = [
        'resource' => 'is_resource',
        'callable' => 'is_callable',
        'int'      => 'is_int',
        'bool'     => 'is_bool',
        'string'   => 'is_string',
        'object'   => 'is_object',
        'array'    => 'is_array',
    ];

    /**
     * @var array 默认配置。
     */
    private static $defaultArguments = [
        'service'           => [
            'type'     => 'value',
            'valid'    => ['string'],
            'required' => true,
            'internal' => true,
            'doc'      => '初始化的云服务名称。通过对应的云服务客户端使用 CloudStorage 时，这个值是默认自动填充的（如：Cloudstorage\\Upyun\\UpyunClient）填充为 upyun。',
        ],
        'exceptionClass'    => [
            'type'     => 'value',
            'valid'    => ['string'],
            'default'  => CloudStorageException::class,
            'internal' => true,
            'doc'      => '报错时使用的异常类。',
        ],
        'scheme'            => [
            'type'    => 'value',
            'valid'   => ['string'],
            'default' => 'https',
            'doc'     => '连接云服务时使用的 URI 模式。CloudStorage 将默认启用 https（如 SSL/TLS 连接）连接云服务的端点。你可以通过设置 ``scheme`` 为 http 不加密连接云服务，但是极不推荐。',
        ],
        'signatureProvider' => [
            'type'    => 'value',
            'value'   => ['callable'],
            'default' => [__CLASS__, 'applyDefaultSignatureProvider'],
            'doc'     => '签名提供者。一个 callable 类型的函数，接受云服务名称（如 upyun）和签名版本（例如 base）为参数，并返回 SignatureInterface 对象或 null。这个签名提供者被客户端用来创建签名。CloudStorage\\Signature\\SignatureProvider 中列举了一组内置的提供者。',
        ],
        'signatureVersion'  => [
            'type'    => 'config',
            'valid'   => ['string'],
            'default' => [__CLASS__, 'applyDefaultSignatureVersion'],
            'doc'     => '代表一个云服务的自定义签名版本的字符串（如 base）。注意：不同操作的签名版本可能会覆盖这个默认版本。',
        ],
        'profile'           => [
            'type'  => 'config',
            'valid' => ['string'],
            'fn'    => [__CLASS__, 'applyProfile'],
            'doc'   => '当云服务凭证是从配置文件中创建的，指定使用哪一个身份。这个设置会覆盖 CLOUDSTORAGE_PROFILE 环境变量。注意：指定 profile 会导致 credentials 设置中的内容被忽略。',
        ],
        'credentials'       => [
            'type'  => 'value',
            'valid' => [
                CredentialsInterface::class, 'array', 'bool',
                'callable'],
            'fn'    => [__CLASS__, 'applyDefaultProvider'],
            'doc'   => '指定用来签名请求的凭证。可以提供
            CloudStorage\\Credentials\\CredentialsInterface 对象，一个包含
            key，secret 的关联数组，`false` 作为空凭证，或一个 callable 凭证提供者创建凭证或返回 null。CloudStorage\\Credentials\\AbstractCredentialProvider 中列举了一组内置的凭证提供者。如果没有提供凭证，CloudStorage 将试图从环境变量中加载它们。',
        ],
        'retries'           => [
            'type'    => 'value',
            'valid'   => ['int'],
            'fn'      => [__CLASS__, 'applyRetries'],
            'default' => 3,
            'doc'     => '客户端最大重试次数（传入 0 禁用重试）。',
        ],
        'validate'          => [
            'type'    => 'value',
            'valid'   => ['bool', 'array'],
            'default' => true,
            'fn'      => [__CLASS__, 'applyValidate'],
            'doc'     => '设为 `false` 禁用客户端参数验证。设为 `true` 使用默认的验证约束。设为一个关联数组去弃用特殊的验证约束。',
        ],
        'debug'             => [
            'type'  => 'value',
            'valid' => ['bool', 'array'],
            'fn'    => [__CLASS__, 'applyDebug'],
            'doc'   => '设为 `true` 时在客户端向云服务发送请求时显示调试信息。此外，你还可以提供一个关联数组，含有以下键名 —— logfn：（callable）随日志信息一起调用的函数；stream_size：（int）当流数据的尺寸大于此数字时，流数据将不会被记录（设为 0 禁止所有流数据的记录）；scrub_auth：（bool）设为 `false` 禁用从日志信息中筛选授权信息；http：（bool）设为 `false` 禁用更底层 HTTP 适配器的调试特性（例如 curl 的详细信息输出）。',
        ],
        'http'              => [
            'type'  => 'value',
            'valid' => ['array'],
            'doc'   => '设置一个关联数组作为 CloudStorage 客户端每个请求的选项（例如 proxy，verify 等）。',
        ],
        'httpHandler'       => [
            'type'  => 'value',
            'valid' => ['callable'],
            'fn'    => [__CLASS__, 'applyHttpHandler'],
            'doc'   => 'HTTP 处理器是一个函数，接受 PSR-7 请求对象作为参数，返回一个 promise， 代表已完成的 PSR-7 响应对象或已失败的包含异常数据的数组。注意：这个选项将覆盖任何已有的 handler 选项。',
        ],
        'handler'           => [
            'type'    => 'value',
            'valid'   => ['callable'],
            'fn'      => [__CLASS__, 'applyHandler'],
            'default' => [__CLASS__, 'applyDefaultHandler'],
            'doc'     => '处理器，接受 CloudStorage\\Contracts\\CommandInterface 和
            PSR-7 请求对象作为参数，返回一个 promise， 代表已完成的
            CloudStorage\\Contracts\\ResultInterface 对象或已失败的
            CloudStorage\\Exceptions\\CloudStorageException
            。处理器并不接收下一个处理器，因为其是最终的，用来完成一个命令的函数。如果没有提供处理器，则使用默认的 Guzzle 处理器。',
        ],
    ];


    /**
     * 获得默认客户端配置选项的数组，每个包含下述内容：
     *
     * - type：（string，required）类型，支持下述值：
     *   - value：默认的选项类型。
     *   - config：提供的值在客户端的 getConfig() 方法中可以获取到。
     * - valid：（array， required）有效的 PHP 类型或类名。注意：不允许 null 类型。
     * - required：（bool，callable）参数是否是必须的。提供一个函数接受一个数组作为参数，
     *              返回一个字符串作为自定义错误消息。
     * - default：（mixed）如果没有提供参数值，则默认使用的。如果提供的是函数，则它将被调用
     *             来提供默认值。提供给函数选项数组，函数应该返回选项的默认值。
     * - fn：（callable）用来应用参数的函数。函数接受一个给定值，一个参数数组的引用，和一个
     *        事件发射器（处理器列表）。
     * - doc：（string）参数的文档内容。
     *
     * 注意：应用参数时顺序十分重要不可更改。
     *
     * @return array
     */
    public static function getDefaultArguments()
    {
        return self::$defaultArguments;
    }

    /**
     * ClientConstructor constructor.
     *
     * @param array $arguments 客户端参数
     */
    public function __construct(array $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * 解析客户端配置选项和附加的时间监听器。
     *
     * @param array       $arguments 客户端提供的构造参数。
     * @param HandlerList $list      要配置的处理器列表。
     *
     * @return array 返回处理（和默认选项交叉）过的选项。
     * @throws \InvalidArgumentException
     * @see CloudStorage\Client::__construct 一组可用选项。
     */
    public function resolve(array $arguments, HandlerList $list)
    {
        $arguments['config'] = [];
        foreach ($this->arguments as $key => $arg) {
            // 为没有设置的选项添加默认值，验证必须的值，未设置必须则跳过。
            if (!isset($arguments[$key])) {
                if (isset($arg['default'])) {
                    // 没有提供相应设置时，则使用默认值。
                    $arguments[$key] = is_callable($arg['default'])
                        ? $arg['default']($arguments)
                        : $arg['default'];
                } elseif (empty($arg['required'])) {
                    continue;
                } else {
                    $this->throwRequired($arguments);
                }
            }

            // 验证提供的选项类型符合预设
            foreach ($arg['valid'] as $check) {
                if (isset(self::$typeMap[$check])) {
                    $fn = self::$typeMap[$check];
                    if ($fn($arguments[$key])) {
                        goto is_valid;
                    }
                } elseif ($arguments[$key] instanceof $check) {
                    goto is_valid;
                }
            }
            $this->invalidType($key, $arguments[$key]);

            // 使用值
            is_valid:
            if (isset($arg['fn'])) {
                $arg['fn']($arguments[$key], $arguments, $list);
            }
            if ($arg['type'] === 'config') {
                $arguments['config'][$key] = $arguments[$key];
            }

            return $arguments;
        }
    }

    /**
     * 为缺失的必须参数抛出异常。
     *
     * @param array $arguments 传入参数。
     *
     * @throws \InvalidArgumentException
     */
    private function throwRequired(array $arguments)
    {
        $missing = [];
        foreach ($this->arguments as $key => $arg) {
            if (empty($arg['required'])
                || isset($arg['default'])
                || array_key_exists($key, $arguments)
            ) {
                continue;
            }
            $missing[] = $this->getArgMessage($key, $arguments, true);
        }
        $msg = "Missing required client configuration options: \n\n";
        $msg .= implode("\n\n", $missing);
        throw new \InvalidArgumentException($msg);
    }

    /**
     * 创建一个无效参数的详细错误信息。
     *
     * @param string $name       缺失的参数名称。
     * @param array  $arguments  提供的参数。
     * @param bool   $isRequired 设为 true 去显示必须的 fn 文本（如果有）而不是文档。
     *
     * @return string
     */
    private function getArgMessage($name, $arguments = [], $isRequired = false)
    {
        $arg = $this->arguments[$name];
        $msg = '';
        $modifiers = [];
        if (isset($arg['valid'])) {
            $modifiers[] = implode('|', $arg['valid']);
        }
        if (isset($arg['choice'])) {
            $modifiers[] = 'One of ' . implode(', ', $arg['choice']);
        }
        if ($modifiers) {
            $msg .= '(' . implode('; ', $modifiers) . ')';
        }
        $msg = wordwrap("{$name}: {$msg}", 75, "\n  ");

        if ($isRequired && is_callable($arg['required'])) {
            $msg .= "\n\n  ";
            $msg .= str_replace("\n", "\n  ", call_user_func($arg['required'], $arguments));
        } elseif (isset($arg['doc'])) {
            $msg .= wordwrap("\n\n  {$arg['doc']}", 75, "\n  ");
        }

        return $msg;
    }

    /**
     * 遇到无效类型时抛出。
     *
     * @param string $name     要被验证的参数名称。
     * @param mixed  $provided 提供的值。
     *
     * @throws \InvalidArgumentException
     */
    private function invalidType($name, $provided)
    {
        $expected = implode('|', $this->arguments[$name]['valid']);
        $msg = "Invalid configuration value "
            . "provided for \"{$name}\". Expected {$expected}, but got "
            . describeType($provided) . "\n\n"
            . $this->getArgMessage($name);
        throw new \InvalidArgumentException($msg);
    }

    public static function applyRetries($value, array &$arguments, HandlerList $list)
    {
        // todo
        if ($value) {
            $decider = \Aws\RetryMiddleware::createDefaultDecider($value);
            $list->appendSign(Middleware::retry($decider), 'retry');
        }
    }

    public static function applyCredentials($value, array &$arguments)
    {
        if (is_callable($value)) {
            return;
        } elseif ($value instanceof CredentialsInterface) {
            $arguments['credentials'] = CredentialProvider::fromCredentials($value);
        } elseif (is_array($value)
            && isset($value['key'])
            && isset($value['secret'])
        ) {
            $arguments['credentials'] = CredentialProvider::fromCredentials(
                // todo
                new Credentials(
                    $value['key'],
                    $value['secret']
                )
            );
        } elseif (false === $value) {
            $arguments['credentials'] = CredentialProvider::fromCredentials(
                // todo
                new Credentials('', '')
            );
        } else {
            throw new \InvalidArgumentException('Credentials must be an instance of '
                . 'CloudStorage\Credentials\CredentialsInterface, an associative '
                . 'array that contains "key", "secret", and an optional "token" '
                . 'key-value pairs, a credentials provider function, or false.');
        }
    }
}
