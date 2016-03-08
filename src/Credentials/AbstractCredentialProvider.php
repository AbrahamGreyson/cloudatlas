<?php

/*
 * CloudAtlas
 * @link  : https://github.com/AbrahamGreyson/cloudatlas
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudAtlas\Credentials;

use CloudAtlas\Exceptions\CredentialsException;
use GuzzleHttp\Promise;

/**
 * 凭证提供者。
 *
 * 凭证提供者是不接受参数，并返回一个 promise，代表已完成的
 * {@see \CloudAtlas\Credentials\CredentialsInterface} 或已失败的
 * {@see \CloudAtlas\Exceptions\CloudAtlasException} 的函数。
 *
 * <code>
 * use CloudAtlas\Credentials\AbstractCredentialProvider;
 * $provider = AbstractCredentialProvider::defaultProvider();
 * // 返回 CredentialsInterface 或抛异常。
 * $creds = $provider()->wait();
 * </code>
 *
 * 凭证提供者可以被有条件的组合到一起，以便在不同环境中使用不同的凭证。可以通过使用 {@see
 * \CloudAtlas\Credentials\AbstractCredentialProvider::chain()} 组合多个提供者至
 * 单独一个提供者内。这个方法接受一个提供者作为参数，并返回一个新的函数，新的函数将调用每一个提供者
 * 直到一组凭证被成功返回。
 *
 * <code>
 * // 首先从特定 INI 配置文件中加载凭证。
 * $a = AbstractCredentialProvider::ini(null, '/path/to/file.ini');
 * // 然后从另一个 INI 配置文件中加载凭证。
 * $b = AbstractCredentialProvider::ini(null, 'path/to/other-file.ini');
 * // 然后从环境变量中加载。
 * $c = AbstractCredentialProvider::env();
 * // 组合它们到一起（返回一个在内部调用每个凭证提供者，直到成功获取凭证的函数）。
 * $composed = AbstractCredentialProvider::chain($a, $b, $c);
 * // 返回一个 promise，代表已完成的凭证或抛出异常（已失败）。
 * $promise = $composed();
 * // 同步等待凭证状态被取得。
 * $creds = $promise->wait();
 * </code>
 *
 * 如果凭证来自环境变量、配置文件或构造参数以外的地方，可以使用
 * {@see AbstractCredentialProvider::extend()} 方法扩展凭证提供者，该方法接受一个字符串
 * 类型的提供者名称和一个 callable 类型的参数，callable 返回一个 promise，promise 代表
 * 什么参见本文档块最开始的段落。
 * 一旦扩展凭证提供者之后，你就可以使用 AbstractCredentialProvider::name() 去在任何地方
 * 使用这个凭证提供者。
 *
 * <code>
 * AbstractCredentialProvider::extend('yaconf', function(){
 *      // 首先从 yaconf 中取得 key 和 secret。
 *      return function () {
 *          return Promise\promise_for(
 *              new Credentials($key, $secret)
 *          );
 *      };
 * });
 * </code>
 *
 * @package CloudAtlas\Credentials
 */
abstract class AbstractCredentialProvider implements CredentialProviderInterface
{
    const ENV_KEY     = 'undefined';
    const ENV_SECRET  = 'undefined';
    const ENV_PROFILE = 'CLOUDATLAS_PROFILE';

    protected static $service = null;


    /**
     * @var array 自定义的凭证提供者。
     */
    protected static $extensions = [];

    /**
     * 创建默认凭证提供者，首先检查环境变量，然后检查 include_path 中的
     * .cloudatlas/credentials 文件。
     *
     * @return callable
     */
    public static function defaultProvider()
    {
        return self::memoize(
            self::chain(
                self::env(),
                self::ini()
            )
        );
    }

    /**
     * 从静态凭证创建凭证提供者。
     *
     * @param CredentialsInterface $credentials
     *
     * @return callback
     */
    public static function fromCredentials(CredentialsInterface $credentials)
    {
        $promise = Promise\promise_for($credentials);

        return function () use ($promise) {
            return $promise;
        };
    }

    /**
     * 扩展自定义的凭证提供者。
     *
     * @param  string  $name
     * @param callable $provider
     */
    public static function extend($name, callable $provider)
    {
        // 为了防止静态属性指向基类那唯一的值（子类一处修改，则处处修改），
        // 我们用被调用的类名作为数组的 key，这样不同的子类，例如 UpyunCredentialProvider
        // 或 QiniuCredentialProvider 可以单独扩展凭证提供者。
        $key = get_called_class();
        static::$extensions[$key][$name] = $provider;
    }

    /**
     * 返回自定义凭证提供者或抛出异常。
     *
     * @param string   $name
     * @param callable $arguments
     *
     * @return callable 凭证提供者。
     * @throws \BadMethodCallException 提供者未找到。
     */
    public static function __callStatic($name, $arguments)
    {
        $key = get_called_class();
        if (isset(static::$extensions[$key][$name])) {
            return static::$extensions[$key][$name];
        }

        throw new \BadMethodCallException(
            "Provider is not exists: {$name}"
        );
    }

    /**
     * 组合多个凭证来源，直至返回一个可用凭证。
     *
     * @return callable
     */
    public static function chain()
    {
        $links = func_get_args();
        if (empty($links)) {
            throw new \InvalidArgumentException('No providers in chain.');
        }

        return function () use ($links) {
            /** @var callable $parent */
            $parent = array_shift($links);
            $promise = $parent();
            while ($next = array_shift($links)) {
                /** @var \GuzzleHttp\Promise\Promise $promise */
                $promise = $promise->otherwise($next);
            }

            return $promise;
        };
    }

    /**
     * 环境变量凭证提供者，从环境变量中创建凭证。
     *
     * 不同的云服务，环境变量名不同，这就要求子类覆写本类的常量，把对应的 ENV_KEY 和
     * ENV_SECRET 名称指定为特定云服务的。
     *
     * @return callable
     */
    public static function env()
    {
        $credentialConcrete = static::getCredentialConcrete();

        return function () use ($credentialConcrete) {
            // 查找环境变量中的凭证
            $key = getenv(static::ENV_KEY);
            $secret = getenv(static::ENV_SECRET);
            if ($key && $secret) {
                return Promise\promise_for(
                    new $credentialConcrete($key, $secret)
                );
            }

            return self::reject('Could not find environment variable credentials in ' .
                static::ENV_KEY . '/' . static::ENV_SECRET);
        };
    }

    /**
     * todo ini 文件加载
     *
     * @param null $profile
     * @param null $filename
     *
     * @return \Closure
     */
    public static function ini($profile = null, $filename = null)
    {
        $filename = $filename ?: './.cloudatlas/credentials';
        $profile = $profile ?: (getenv(self::ENV_PROFILE) ?: 'default');
        $credentialConcrete = static::getCredentialConcrete();

        return function () use ($profile, $filename, $credentialConcrete) {
            if (!is_readable($filename)) {
                return self::reject("Cannot read credentials from {$filename}");
            }

            $data = parse_ini_file($filename, true);
            if ($data === false) {
                return self::reject("Invalid credentials file: {$filename}");
            }
            if (!isset($data[$profile])) {
                return self::reject("'{$profile}' not found in credentials file.");
            }
            if (!isset($data[$profile][constant(static::ENV_KEY)])
                || !isset($data[$profile][constant(static::ENV_SECRET)])
            ) {
                return self::reject("No credentials present in INI profile "
                    . "'{$profile}' ($filename)");
            }

            return Promise\promise_for(
                new $credentialConcrete(
                    $data[$profile][constant(static::ENV_KEY)],
                    $data[$profile][constant(static::ENV_SECRET)]
                )
            );
        };
    }

    /**
     * 已失败的 promise 封装。
     *
     * @param string $msg 异常信息。
     *
     * @return Promise\RejectedPromise
     */
    private static function reject($msg)
    {
        return new Promise\RejectedPromise(new CredentialsException($msg));
    }

    /**
     * 缓存凭证提供者，避免内存还没释放，却重新获取凭证。
     *
     * 确保凭证过期时会刷新。
     *
     * @param callable $provider
     *
     * @return callable
     */
    public static function memoize(callable $provider)
    {
        return function () use ($provider) {
            static $result;
            static $isConstant;

            // 无过期的凭证直接返回。
            if ($isConstant) {
                return $result;
            }

            // 创建内部 promise 作为过期前的缓存值。
            if (null === $result) {
                /** @var \GuzzleHttp\Promise\Promise $result */
                $result = $provider();
            }

            // 返回一个过期时可以刷新的凭证。
            return $result->then(
                function (CredentialsInterface $credentials) use (
                    $provider, &$isConstant, &$result
                ) {
                    // 判断是否是无过期凭证。
                    if (!method_exists($credentials, 'getExpiration')
                        || !$credentials->getExpiration()
                    ) {
                        $isConstant = true;

                        return $credentials;
                    }

                    // 未过期就返回。
                    if (!method_exists($credentials, 'isExpired')
                        || !$credentials->isExpired()
                    ) {
                        return $credentials;
                    }

                    // 过期就刷新。
                    return $result = $provider();
                }
            );
        };
    }
}
