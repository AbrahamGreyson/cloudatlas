<?php

/*
 * CloudAtlas
 * @link  : https://github.com/AbrahamGreyson/cloudatlas
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudAtlas;

use CloudAtlas\Api\Service;
use CloudAtlas\Contracts\CommandInterface;
use CloudAtlas\Credentials\CredentialsInterface;
use GuzzleHttp\Psr7\LazyOpenStream;
use Psr\Http\Message\RequestInterface;

/**
 * 内置的中间件函数。
 *
 * @package CloudAtlas
 */
final class Middleware
{
    /**
     * 中间件用于在上传操作时，用一个命令参数（如 SourceFile）去指定数据源。
     *
     * @param Service $api
     * @param string  $bodyParameter
     * @param string  $sourceParameter
     *
     * @return callable
     */
    public static function sourceFile(
        Service $api,
        $bodyParameter = 'Body',
        $sourceParameter = 'SourceFile'
    ) {
        return function (callable $handler) use (
            $api,
            $bodyParameter,
            $sourceParameter
        ) {
            return function (
                CommandInterface $command,
                RequestInterface $request = null
            ) use (
                $handler,
                $api,
                $bodyParameter,
                $sourceParameter
            ) {
                // todo method
                $operation = $api->getOperation($command->getName());
                $source = $command[$sourceParameter];

                if (null !== $source
                    // todo method
                    && $operation->getInput()->hasMember($bodyParameter)
                ) {
                    $command[$bodyParameter] = new LazyOpenStream($source, 'r');
                    unset($command[$sourceParameter]);
                }

                return $handler($command, $request);
            };
        };
    }

    /**
     * 用于客户端验证的中间件。
     *
     * @param Service        $api       被访问的 API。
     * @param Validator|null $validator 验证器。
     *
     * @return callable
     */
    public static function validation(Service $api, Validator $validator = null)
    {
        $validator = $validator ?: new \Validator();

        return function (callable $handler) use ($api, $validator) {
            return function (
                CommandInterface $command,
                RequestInterface $request = null
            ) use ($api, $validator, $handler) {
                $operation = $api->getOperation($command->getName());
                $validator->validate(
                    $command->getName(),
                    $operation->getInput(),
                    $command->toArray()
                );

                return $handler($command, $request);
            };
        };
    }

    /**
     * 为命令构造一个 HTTP 请求。
     *
     * @param callable $serializer 为命令序列化请求的函数。
     *
     * @return \Closure
     */
    public static function requestBuilder(callable $serializer)
    {
        return function (callable $handler) use ($serializer) {
            return function (CommandInterface $command) use ($serializer, $handler) {
                return $handler($command, $serializer($command));
            };
        };
    }

    /**
     * 为命令的请求进行签名。
     *
     * @param callable $credProvider      身份凭证提供器，函数应该返回一个 promise 包含
     *                                    已完成的 CredentialsInterface 对象。
     * @param callable $signatureFunction 函数接受一个 Command 对象并返回一个
     *                                    SignatureInterface 对象。
     *
     * @return \Closure
     */
    public static function signer(callable $credProvider, callable $signatureFunction)
    {
        return function (callable $handler) use ($signatureFunction, $credProvider) {
            return function (
                CommandInterface $command,
                RequestInterface $request
            ) use ($handler, $signatureFunction, $credProvider) {
                $signer = $signatureFunction($command);

                return $credProvider()->then(
                    function (CredentialsInterface $creds) use ($handler, $command, $signer, $request) {
                        return $handler(
                            $command,
                            $signer->signRequest($request, $creds)
                        );
                    }
                );
            };
        };
    }


    /**
     * 重试中间件。根据提供的 decider 函数的布尔值结果，包装了请求的重试次数。
     *
     * 如果没提供延迟函数，默认使用一个指数延迟的简单实现。
     *
     * @param callable|null $decider 函数接受重试次数数值，请求，结果，和异常。如果判定
     *                               命令应该重试，则返回 true。
     * @param callable|null $delay   函数接受当前重试是第几次，返回应该延迟的毫秒数。
     *
     * @return callable
     */
    public static function retry(callable $decider = null, callable  $delay = null)
    {
        $decider = $decider ?: RetryMiddleware::createDefaultDecider();
        $delay = $delay ?: [RetryMiddleware::class, 'exponentialDelay'];

        return function (callable  $handler) use ($decider, $delay) {
            return new RetryMiddleware($decider, $delay, $handler);
        };
    }
}
