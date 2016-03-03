<?php

/*
 * CloudStorage
 * @link  : https://github.com/AbrahamGreyson/cloudstorage
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudStorage\Test\Api;

use CloudStorage\Api\ApiProvider;
use CloudStorage\Exceptions\UnresolvedApiException;

class ApiProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testCanResolveProvider()
    {
        $p = function ($a, $b, $c) { return []; };
        $this->assertEquals([], ApiProvider::resolve($p, 'a', 's', 'v'));

        $p = function ($a, $b, $c) { return null; };
        $this->setExpectedException(UnresolvedApiException::class);
        ApiProvider::resolve($p, 'a', 's', 'v');
    }
}
