<?php

/*
 * CloudAtlas
 * @link  : https://github.com/AbrahamGreyson/cloudatlas
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudAtlas\Upyun;

use CloudAtlas\Client;
use CloudAtlas\ClientResolver;

class UpyunClient extends Client
{
    /**
     * 获取默认的客户端构造参数用于实例化客户端。
     *
     * @return array
     */
    public static function getDefaultArguments()
    {
        return ClientResolver::getDefaultArguments();
    }

    /**
     * 获取和对应云服务客户端相关联的服务描述。
     *
     * @return \CloudAtlas\Api\Service
     */
    public function getApi()
    {
        // TODO: Implement getApi() method.
    }

    /**
     * 为指定操作获取一个结果分页器。
     *
     * @param string $name 迭代器使用的操作名称。
     * @param array  $args 每个命令所使用的命令参数。
     *
     * @return ResultPaginator
     * @throws \UnexpectedValueException 如果迭代器配置无效。
     */
    public function getPaginator($name, array $args = [])
    {
        // TODO: Implement getPaginator() method.
    }

    public function addSignatureMiddleware()
    {
    }
}
