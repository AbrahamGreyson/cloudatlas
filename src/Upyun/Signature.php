<?php

/*
 * CloudAtlas
 * @link  : https://github.com/AbrahamGreyson/cloudatlas
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudAtlas\Upyun;

use CloudAtlas\Credentials\CredentialsInterface;
use CloudAtlas\Signatures\SignatureInterface;
use Psr\Http\Message\RequestInterface;

class Signature implements SignatureInterface
{
    
    /**
     * 通过提供的凭证，对特定请求进行签名，并添加对应的 HTTP 头至请求中。
     *
     * @param RequestInterface     $request    要签名的请求。
     * @param CredentialsInterface $credential 使用的凭证。
     *
     * @return RequestInterface 修改（签名）过的请求。
     */
    public function signRequest(
        RequestInterface $request,
        CredentialsInterface $credential
    ) {
    }
}
