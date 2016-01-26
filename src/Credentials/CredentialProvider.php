<?php
namespace CloudStorage\Credentials;


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
 * @package CloudStorage\Credentials
 */
class CredentialProvider
{
    public static function defaultProvider()
    {

    }

    public static function compose()
    {

    }

    public static function env()
    {

    }

    public static function ini()
    {

    }
}