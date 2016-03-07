<?php

/*
 * CloudAtlas
 * @link  : https://github.com/AbrahamGreyson/cloudatlas
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudAtlas\Credentials;

/**
 * 代表一个云服务连接凭证，用来对服务请求进行签名。
 * 因为预计支持多个云服务，不同云服务的凭证名称不尽相同，此处几乎没有办法进行高级抽象。
 * 所以粗暴的使用了 key 代表公钥，secret 代表私钥。
 *
 * @package \CloudAtlas\Credentials
 */
interface CredentialsInterface
{
    public function getKey();

    public function getSecret();
    //public function getExpiration();
    //
    //public function isExpired();
}
