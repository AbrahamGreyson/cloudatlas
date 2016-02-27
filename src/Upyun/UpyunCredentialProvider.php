<?php

/*
 * CloudStorage
 * @link  : https://github.com/AbrahamGreyson/cloudstorage
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

/**
 * @link  : http://www.yinhexi.com/
 * @author: AbrahamGreyson <82011220@qq.com>
 * @date  : 02/17/2016
 */

namespace CloudStorage\Upyun;

use CloudStorage\Credentials\AbstractCredentialProvider;

class UpyunCredentialProvider extends AbstractCredentialProvider
{
    const ENV_KEY    = 'CLOUDSTORAGE_UPYUN_KEY';
    const ENV_SECRET = 'CLOUDSTORAGE_UPYUN_SECRET';

    /**
     * 获取不同服务的不同凭证实现，这个方法需要子类来实现，返回对应服务的 Credential 类
     * 的完全限定名。
     *
     * @return string
     */
    public static function getCredentialConcrete()
    {
        return Credential::class;
    }
}
