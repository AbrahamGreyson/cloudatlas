<?php

/*
 * CloudStorage
 * @link  : https://github.com/AbrahamGreyson/cloudstorage
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudStorage\Signatures;

use CloudStorage\Exceptions\UnresolvedSignatureException;
use CloudStorage\Upyun\BasicSignature;

/**
 * 签名提供者。
 *
 * 签名提供者是一个接受版本号，服务名的函数。返回 {@see SignatureInterface} 对象，如果
 * 提供的参数无法创建签名，则返回 null。
 *
 * 你可以使用 {@see SignatureProvider::resolve} 包装对签名提供者的调用，去确保签名对象
 * 已经被创建。如果无法创建签名对象，则 resolve() 方法会抛出异常
 * {@see CloudStorage\Exceptions\UnresolvedSignatureException}。
 *
 * <code>
 * use CloudStorage\Signatures\SignatureProvider;
 * $provider = SignatureProvider::defaultProvider();
 * // 返回 SignatureInterface 或 null。
 * $signer = $provider('v1', 'upyun');
 * // 返回 SignatureInterface 或抛出异常。
 * $signer = SignatureProvider::resolve($provider, 'v100', 'upyun');
 * </code>
 *
 * 你可以使用 {@see CloudStorage\orChain} 组合多个签名提供者至单独的一个。该函数接受不定
 * 个数的签名提供者作为参数，返回一个新函数，该函数将会依次调用签名提供者直到一个非空的值被返回。
 *
 * <code>
 * $a = SignatureProvider::defaultProvider();
 * $b = function ($version, $service) {
 *      if ($version) === 'foo' {
 *          return new MyFooSignature();
 *      }
 * }
 * $c = \CloudStorage\orChain($a, $b);
 * $signer = $c('v1', 'abc'); // $a 提供者处理这个调用。
 * $signer = $c('foo', 'abc'); // $b 提供者处理这个调用。
 * $nullValue = $c('v100', '???'); // 没有提供者能处理，返回 null。
 * </code>
 *
 * @package CloudStorage\Signatures
 */
class SignatureProvider
{
    public static function defaultProvider()
    {
        // todo version split
        return self::memoize(self::version());
    }

    public static function resolve(callable $provider, $version, $service)
    {
        $result = $provider($version, $service);
        if ($result instanceof SignatureInterface) {
            return $result;
        }

        throw new UnresolvedSignatureException(
            "Unable to resolve a signature for $version/$service. \n"
            . "Valid signature versions include v1 and anonymous."
        );
    }

    public static function memoize(callable $provider)
    {
        $cache = [];

        return function ($version, $service) use (&$cache, $provider) {
            $key = "($version)($service)";
            if (!isset($cache[$key])) {
                $cache[$key] = $provider($version, $service);
            }

            return $cache[$key];
        };
    }

    public static function version()
    {
        return function ($version, $service) {
            switch ($version) {
                case 'v1':
                    return new BasicSignature();
                case 'anonymous':
                    return new AnonymousSignature();
                default:
                    return null;
            }
        };
    }
}
