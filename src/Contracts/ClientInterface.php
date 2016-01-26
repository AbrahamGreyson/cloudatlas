<?php
namespace CloudStorage\Contracts;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\UriInterface;

/**
 * 代表一个云服务的客户端。
 */
interface ClientInterface
{
    /**
     * 根据操作名称创建并执行一个命令。
     *
     * 以 Async 为后缀的操作名称将返回一个可以被异步执行的 promise。
     *
     * @param string $name      执行的操作名称。
     * @param array  $arguments 传递给 getCommand 方法的参数。
     *
     * @return ResultInterface
     * @throws \Exception
     */
    public function __call($name, array $arguments);

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
    public function getCommand($name, array $args = []);

    /**
     * 执行一个命令。
     *
     * @param CommandInterface $command 要执行的命令。
     *
     * @return ResultInterface
     */
    public function execute(CommandInterface $command);

    /**
     * 异步执行一个命令。
     *
     * @param CommandInterface $command 要执行的命令。
     *
     * @return PromiseInterface
     */
    public function executeAsync(CommandInterface $command);

    /**
     * 返回一个已完成的 {@see CredentialsInterface} 对象的 promise。
     *
     * 如果你需要同步获得凭证，在返回的 promise 对象上调用 wait() 方法即可。
     *
     * @return PromiseInterface
     */
    public function getCredentials();

    /**
     * 获取默认的连接点或基本 URL，供客户端使用。
     *
     * @return UriInterface
     */
    public function getEndpoint();

    ///**
    // * @return mixed
    // */
    //public function getApi();

    /**
     * 获取一个客户端配置的值。
     *
     * @param string|null $options 要取回的配置选项，传递空取回所有配置。
     *
     * @return string|array|null
     */
    public function getConfig($options = null);

    /**
     * 获取执行命令所用到的处理器列表。
     *
     * 这个列表可以被修改，增加中间件或更改底层处理器去发送 HTTP 请求。
     *
     * @return HandlerList
     */
    public function getHandlerList();

    /**
     * 为指定操作获取一个资源迭代器。
     *
     * @param string $name 想要取回的迭代器名称。
     * @param array  $args 每个命令所使用的命令参数。
     *
     * @return \Iterator
     * @throws \UnexpectedValueException 如果迭代器配置无效。
     */
    public function getIterator($name, array $args = []);

    /**
     * 为指定操作获取一个结果分页器。
     *
     * @param string $name 迭代器使用的操作名称。
     * @param array  $args 每个命令所使用的命令参数。
     *
     * @return ResultPaginator
     * @throws \UnexpectedValueException 如果迭代器配置无效。
     */
    public function getPaginator($name, array $args = []);

    ///**
    // *
    // * @param       $name
    // * @param array $args
    // *
    // * @return mixed
    // */
    //public function waitUntil($name, array $args = []);
    //
    ///**
    // * @param       $name
    // * @param array $args
    // *
    // * @return mixed
    // */
    //public function getWaiter($name, array $args = []);

}