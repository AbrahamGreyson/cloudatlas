<?php

namespace CloudStorage;

use Psr\Http\Message\RequestInterface;

/**
 * 请求中间件。
 */
final class Middleware
{
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
     * @param callable $signatureFunction 函数接受一个 Command 对象并返回一个 SignatureInterface
     *                                    对象。
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
}
