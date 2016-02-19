<?PHP

/*
 * CloudStorage
 * @link  : https://github.com/AbrahamGreyson/cloudstorage
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudStorage\Upyun;

use CloudStorage\Client;
use CloudStorage\Contracts\ResultPaginator;

class UpyunClient extends Client
{
    
    /**
     * 获取和对应云服务客户端相关联的服务描述。
     *
     * @return \CloudStorage\Api\Service
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
