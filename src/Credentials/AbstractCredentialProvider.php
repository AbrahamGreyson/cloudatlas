<?php

/*
 * CloudStorage
 * @link  : https://github.com/AbrahamGreyson/cloudstorage
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudStorage\Credentials;

use Aws\Exception\CredentialsException;
use CloudStorage\Upyun\Credential;
use GuzzleHttp\Promise;

/**
 * 凭证提供者是一组不接受参数，并返回一个 promise，代表已完成的 {@see
 * \CloudStorage\Credentials\CredentialsInterface } 或已失败的 {@see
 * \CloudStorage\Exceptions\CloudStorageException} 的方法。
 *
 * <code>
 * use CloudStorage\Credentials\CredentialProvider;
 * $provider = CredentialProvider::defaultProvider();
 * // 返回 CredentialsInterface 或抛异常。
 * $creds = $provider()->wait();
 * </code>
 *
 * 凭证提供者可以被有条件的组合到一起，以便在不同环境中使用不同的凭证。可以通过使用 {@see
 * \CloudStorage\Credentials\CredentialProvider::compose} 组合多个提供者至单独
 * 一个提供者内。这个方法接受一个提供者作为参数，并返回一个新的函数，新的函数将调用每一个提供者
 * 直到一组凭证被成功返回。
 *
 * <code>
 * // 首先从特定 INI 配置文件中加载凭证。
 * $a = CredentialProvider::ini(null, '/path/to/file.ini');
 * // 然后从另一个 INI 配置文件中加载凭证。
 * $b = CredentialProvider::ini(null, 'path/to/other-file.ini');
 * // 然后从环境变量中加载。
 * $c = CredentialProvider::env();
 * // 组合它们到一起（返回一个在内部调用每个凭证提供者，直到成功获取凭证的函数）。
 * $composed = CredentialProvider()::compose($a, $b, $c);
 * // 返回一个 promise，代表已完成的凭证或抛出异常（已失败）。
 * $promise = $composed();
 * // 同步等待凭证状态被取得。
 * $creds = $promise->wait();
 * </code>
 *
 * @package CloudStorage\Credentials
 */
abstract class AbstractCredentialProvider
{
    const ENV_KEY    = 'undefined';
    const ENV_SECRET = 'undefined';

    /**
     * 创建默认凭证提供者，首先检查环境变量，然后检查 include_path 中的
     * .cloudstorage/credentials 文件。
     *
     * 这个提供者被 memoize 方法包裹，用来缓存之前提供过的凭证。
     *
     * @return callable
     */
    public static function defaultProvider()
    {
        return self::chain(
            self::env(),
            self::ini()
        );
    }

    public static function compose()
    {
    }

    public static function env()
    {
        /**
         * @return mixed
         */
        return function () {
            // 查找环境变量中的凭证
            $key = getenv(static::ENV_KEY);
            $secret = getenv(static::ENV_SECRET);
            if ($key && $secret) {
                return Promise\promise_for(
                // todo static credential
                    new Credential($key, $secret)
                );
            }

            return self::reject('Could not find environment variable
            credentials in ' . static::ENV_KEY . '/' . static::ENV_SECRET);
        };
    }

    public static function ini()
    {
    }


    private static function reject($msg)
    {
        return new Promise\RejectedPromise(new CredentialsException($msg));
    }

    /**
     * 请求内缓存。
     *
     * @param callable $provider
     */
    public static function memoize(callable $provider)
    {
    }

    private static function chain()
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
                $promise = $promise->otherwise($next);
            }

            return $promise;
        };
    }
}
