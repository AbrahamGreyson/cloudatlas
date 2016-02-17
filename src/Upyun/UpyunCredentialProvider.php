<?php
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
}