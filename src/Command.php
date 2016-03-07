<?php

/*
 * CloudAtlas
 * @link  : https://github.com/AbrahamGreyson/cloudatlas
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudAtlas;

use CloudAtlas\Contracts\CommandInterface;
use CloudAtlas\Facilities\DataTrait;

/**
 * 命令对象。
 *
 * @package CloudAtlas
 */
class Command implements CommandInterface
{
    use DataTrait;

    /**
     * @var string 命令名称。
     */
    private $name;

    /**
     * @var HandlerList 处理器列表。
     */
    private $handlerList;

    /**
     * 接受一个关联数组作为命令选项，包括：
     * - @http：(array) 设置传输选项的关联数组。
     *
     * @param string           $name 命令名称。
     * @param array            $args 传递给命令的参数。
     * @param HandlerList|null $list 处理器列表。
     */
    public function __construct($name, array $args = [], HandlerList $list = null)
    {
        $this->name = $name;
        $this->data = $args;
        $this->handlerList = $list;

        if (!isset($this->data['@http'])) {
            $this->data['@http'] = [];
        }
    }

    /**
     * 获取命令的名称。
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 检查命令是否有特定的参数。
     *
     * @param string $name 要检查的参数名。
     *
     * @return bool
     */
    public function hasParam($name)
    {
        return array_key_exists($name, $this->data);
    }

    /**
     * 获取用来传输命令的处理器列表。
     *
     * @return HandlerList
     */
    public function getHandlerList()
    {
        return $this->handlerList;
    }
}
