<?php

/*
 * CloudStorage
 * @link  : https://github.com/AbrahamGreyson/cloudstorage
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

return [
    'version' => 'v1',
    'metadata' => [
        'apiVersion' => 'v1',
        'checksumFormat' => 'md5',
        'endpointPrefix' => 'v0',
        'globalEndpoint' => 'api.upyun.com',
        'protocol' => 'rest-xml',
        'serviceAbbreviation' => 'Upyun',
        'serviceFullName' => 'Upyun cloud storage and CDN service.',
        'signatureVersion' => 'v1',
        'timestampFormat' => 'rfc822',
    ],
    'operations' => [
        'CreateBucket' => [
            'name' => 'CreateBucket',
            'http' => [
                'method' => 'PUT',
                'requestUri' => '/{Bucket}',
            ],
            'input' => [
                'shape' => 'CreateBucketRequest',
            ],
            'output' => [
                'shape' => 'CreateBucketOutput',
            ],
            'errors' => [
                'shape' => 'BucketAlreadyExists',
            ],
            'documentationUrl' => 'https://www.google.com',
            'alias' => 'PutBucket',
        ],
    ],
    'shapes' => [
        'CreateBucketRequest' => [
            'type' => 'structure',
            'required' => [
                'Bucket',
            ],
            'members' => [
                'ACL' => [
                    'shape' => 'BucketCannedACL',
                    'location' => 'header',
                    'locationName' => 'x-amz-zcl',
                ],
                'Bucket' => [
                    'shape' => 'BucketName',
                    'location' => 'uri',
                    'locationName' => 'Bucket',
                ],
                'CreateBucketConfiguration' => [
                    'shape' => 'CreateBucketConfiguration',
                    'locationName' => 'CreateBucketConfiguration',
                    'xmlNamespace' => [
                        'uri' => 'www.google.com',
                    ],
                ],
            ],
        ],
    ],
];
