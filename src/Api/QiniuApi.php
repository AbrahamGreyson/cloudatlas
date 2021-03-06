<?php

/*
 * CloudAtlas
 * @link  : https://github.com/AbrahamGreyson/cloudatlas
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

return [
    'version'       => 'default',
    'metadata'      => [
        'globalEndpoint'   => 'v0.api.upyun.com',
        'timestampFormat'  => 'rfc882',
        'signatureVersion' => 'default',
    ],
    'operationsMap' => [
        'PutObject'  => [
            'name'             => 'PutObject',
            'http'             => [
                'method'     => 'PUT',
                'requestUri' => '/{Bucket}/{key}',
            ],
            'input'            => [
                'shape' => 'PutObjectRequest',
            ],
            'output'           => [
                'shape' => 'PutObjectOutput',
            ],
            'documentationUrl' => 'https://www.google.com',
        ],
        'CopyObject' => [

        ],

    ],
];
