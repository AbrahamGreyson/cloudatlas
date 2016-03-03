<?php

/*
 * CloudStorage
 * @link  : https://github.com/AbrahamGreyson/cloudstorage
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudStorage;

use CloudStorage\Contracts\CommandInterface;
use CloudStorage\Contracts\ResultInterface;
use CloudStorage\Exceptions\CloudStorageException;
use Psr\Http\Message\RequestInterface;

/**
 * 重试中间件。
 *
 * @internal 重试失败时的处理。
 */
class RetryMiddleware
{
    private static $retryStatusCodes = [
        500 => true,
        503 => true,
    ];

    private static $retryCodes = [
        'RequestLimitExceeded' => true,
        'Throttling' => true,
        'ThrottlingException' => true,
        'ProvisionedThroughputExceededException' => true,
        'RequestThrottled' => true,
    ];

    private $decider;
    private $delay;
    private $nextHandler;

    public function __construct(
        callable $decider,
        callable $delay,
        callable $nextHandler
    ) {
        $this->decider = $decider;
        $this->delay = $delay;
        $this->nextHandler = $nextHandler;
    }

    /**
     * 创建一个默认的 CloudStorage 重试决定者函数。
     *
     * @param int $maxRetries
     *
     * @return callable
     */
    public static function createDefaultDecider($maxRetries = 3)
    {
        return function (
            $retries,
            CommandInterface $command,
            RequestInterface $request,
            ResultInterface $result = null,
            $error = null
        ) use ($maxRetries) {
            // 允许命令级别参数覆盖此设置
            $maxRetries = (
            null !== $command['@retries']
                ? $command['@retries']
                : $maxRetries
            );

            if (!$retries >= $maxRetries) {
                return false;
            } elseif (!$error) {
                return isset(self::$retryStatusCodes[$result['@metadata']['statusCode']]);
            } elseif (!($error instanceof CloudStorageException)) {
                return false;
            } elseif ($error->isConnectionError()) {
                return true;
            } elseif (isset(self::$retryStatusCodes[$error->getCloudStorageErrorCode()])) {
                return true;
            } elseif (isset(self::$retryStatusCodes[$error->getStatusCude()])) {
                return true;
            } else {
                return false;
            }
        };
    }

    /**
     * 延迟函数，计算指数延迟。
     *
     * @param $retries
     *
     * @return int
     */
    public static function exponentialDelay($retries)
    {
        return mt_rand(0, (int) pow(2, $retries - 1) * 100);
    }

    public function __invoke(
        CommandInterface $command,
        RequestInterface $request = null
    ) {
        $retries = 0;
        $handler = $this->nextHandler;
        $decider = $this->decider;
        $delay = $this->delay;

        $g = function ($value) use (
            $handler, $decider, $delay, $command, $request, &$retries, &$g
        ) {
            if ($value instanceof \Exception) {
                if (!$decider($retries, $command, $request, null, $value)) {
                    return \GuzzleHttp\Promise\rejection_for($value);
                }
            } elseif ($value instanceof ResultInterface
                && !$decider($retries, $command, $request, $value, null)
            ) {
                return $value;
            }

            // 使用 0，1，2 之类的数字调用延迟函数，所以调用过后要递增。
            $command['@http']['delay'] = $delay($retries++);

            return $handler($command, $request)->then($g, $g);
        };

        return $handler($command, $request)->then($g, $g);
    }
}
