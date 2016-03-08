<?php

/*
 * CloudAtlas
 * @link  : https://github.com/AbrahamGreyson/cloudatlas
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

/**
 * @link  : http://www.yinhexi.com/
 * @author: AbrahamGreyson <82011220@qq.com>
 * @date  : 02/17/2016
 */

namespace CloudAtlas\Upyun;

use CloudAtlas\Credentials\AbstractCredentialProvider;

class UpyunCredentialProvider extends AbstractCredentialProvider
{
    const ENV_KEY    = 'CLOUDATLAS_UPYUN_KEY';
    const ENV_SECRET = 'CLOUDATLAS_UPYUN_SECRET';

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
