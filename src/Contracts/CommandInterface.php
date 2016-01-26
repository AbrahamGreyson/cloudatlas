<?php
namespace CloudStorage\Contracts;


/**
 * 一个命令对象，用来代表创建 HTTP 请求的输入参数以及处理 HTTP 响应。
 *
 * 使用 toArray() 方法将会以关联数组的形式返回该命令的输入参数。
 *
 * @package CloudStorage
 */
interface CommandInterface extends \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * 关联数组的形式返回命令的参数。
     *
     * @return array
     */
    public function toArray();

    /**
     * 获取命令的名称。
     *
     * @return string
     */
    public function getName();

    /**
     * 检查命令是否有特定的参数。
     *
     * @param string $name 要检查的参数名。
     *
     * @return bool
     */
    public function hasParam($name);

    /**
     * 获取用来传输命令的处理器列表。
     *
     * @return HandlerList
     */
    public function getHandlerList();
}