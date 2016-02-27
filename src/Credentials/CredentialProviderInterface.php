<?php

/*
 * CloudStorage
 * @link  : https://github.com/AbrahamGreyson/cloudstorage
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudStorage\Credentials;

interface CredentialProviderInterface
{
    /**
     * 获取不同服务的不同凭证实现，这个方法需要子类来实现，返回对应服务的 Credential 类
     * 的完全限定名。
     *
     * @return string
     */
    public static function getCredentialConcrete();
}
