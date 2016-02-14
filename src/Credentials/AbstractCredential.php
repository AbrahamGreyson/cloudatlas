<?php

/*
 * CloudStorage
 * @link  : https://github.com/AbrahamGreyson/cloudstorage
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudStorage\Credentials;

use CloudStorage\Contracts\Arrayable;

/**
 * 凭证接口的基本实现，允许客户端代码传递各个云服务的公钥和密钥进来。
 * 这个类必须被继承，必须使用子类。
 *
 * @package CloudStorage\Credentials
 */
abstract class AbstractCredential implements
    CredentialsInterface,
    Arrayable,
    \Serializable
{
    private $key;
    private $secret;

    /**
     * 通过各个云服务的公钥和私钥，构造一个基本的凭证对象。
     *
     * @param string $key    公钥
     * @param string $secret 私钥
     */
    public function __construct($key, $secret)
    {
        $this->key = trim($key);
        $this->secret = trim($secret);
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getSecret()
    {
        return $this->secret;
    }

    public function toArray()
    {
        return [
            'key'    => $this->key,
            'secret' => $this->secret,
        ];
    }

    public function __set_state(array $state)
    {
        return new static($state['key'], $state['secret']);
    }

    public function serialize()
    {
        return json_encode($this->toArray());
    }

    public function unserialize($serialized)
    {
        $data = json_decode($serialized, true);

        $this->key = $data['key'];
        $this->secret = $data['secret'];
    }
}
