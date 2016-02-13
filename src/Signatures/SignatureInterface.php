<?php

namespace CloudStorage\Signatures;

use CloudStorage\Credentials\CredentialsInterface;
use Psr\Http\Message\RequestInterface;

/**
 * 为各个云服务、以及同一云服务的不同版本的签名方法，提供了统一接口。
 */
interface SignatureInterface
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
    );

    //public function presign(
    //    RequestInterface $request,
    //    CredentialsInterface $credential,
    //    $expires
    //);
}
