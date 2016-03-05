<?php

namespace CloudStorage\Test\Unit\Api;

use CloudStorage\Api\ApiProvider;
use CloudStorage\Exceptions\UnresolvedApiException;
use CloudStorage\Test\Unit\AbstractTestCase;

class ApiProviderTest extends AbstractTestCase
{

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFilesystemProviderChecksDirectoryIsValid()
    {
        ApiProvider::filesystem('/path/to/invalid/dir');
    }
    
    private function getTestApiProvider($useManifest = true)
    {
        $dir = __DIR__ . '/api_provider_fixtures';
        $manifest = include $dir . '/manifest.php';
        return $useManifest
            ? ApiProvider::manifest($dir, $manifest)
            : ApiProvider::filesystem($dir);
    }

    public function testCanResolveProvider()
    {
        $p = function ($a, $b, $c) { return []; };
        $this->assertEquals([], ApiProvider::resolve($p, 'a', 's', 'v'));

        $p = function ($a, $b, $c) { return null; };
        $this->expectException(UnresolvedApiException::class);
        ApiProvider::resolve($p, 'a', 's', 'v');
    }

    public function testThrowsOnBadType()
    {
        $p = $this->getTestApiProvider();
        $this->expectException(UnresolvedApiException::class);
        ApiProvider::resolve($p, 'badType', 'upyun', 'latest');
    }

    public function testThrowsOnBadService()
    {
        $p = $this->getTestApiProvider();
        $this->expectException(UnresolvedApiException::class);
        ApiProvider::resolve($p, 'api', '', 'latest');
    }

    public function testThrowsOnBadVersion()
    {
        $p = $this->getTestApiProvider();
        $this->expectException(UnresolvedApiException::class);
        ApiProvider::resolve($p, 'api', 'upyun', 'bad-version');
    }
}
