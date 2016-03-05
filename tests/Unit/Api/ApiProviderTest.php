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

    /**
     * @param bool $useManifest
     *
     * @return ApiProvider
     */
    private function getTestApiProvider($useManifest = true)
    {
        $dir = __DIR__ . '/api_provider_fixtures';
        $manifest = @include($dir . '/manifest.php');
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
        $this->expectExceptionMessage('type must be');
        ApiProvider::resolve($p, 'badType', 'upyun', 'latest');
    }

    public function testThrowsOnBadService()
    {
        $p = $this->getTestApiProvider();
        $this->expectException(UnresolvedApiException::class);
        $this->expectExceptionMessage('must specify a service');
        ApiProvider::resolve($p, 'api', '', 'latest');
    }

    public function testThrowsOnBadVersion()
    {
        $p = $this->getTestApiProvider();
        $this->expectException(UnresolvedApiException::class);
        $this->expectExceptionMessage('does not have version');
        ApiProvider::resolve($p, 'api', 'upyun', 'bad-version');
    }

    public function testCanGetServiceVersions()
    {
        $mp = $this->getTestApiProvider();
        $this->assertEquals([], $mp->getVersions('bad-service'));
        $this->assertEquals(['v3', 'v1'], $mp->getVersions('test'));

        $fp = $this->getTestApiProvider(false);
        $this->assertEquals([], $fp->getVersions('cover-call-buildVersionList'));
    }

    public function testCanGetDefaultProvider()
    {
        $p = ApiProvider::defaultProvider();
        $this->assertArrayHasKey('upyun', $this->readAttribute($p, 'manifest'));
    }

    public function testManifestProviderReturnsNullOnMissingService()
    {
        $p = $this->getTestApiProvider();
        $this->assertNull($p('api', 'bad-service', 'latest'));
        $this->assertNull($p('bad-type', 'upyun', 'latest'));
    }
    
    public function testManifestProviderCanLoadData()
    {
        $p = $this->getTestApiProvider();
        $data = $p('api', 'upyun', 'latest');
        $this->assertInternalType('array', $data);
        $this->assertEquals('bar', $data['foo']);
    }

    public function testManifestProviderReturnsNullOnMissingFile()
    {
        $p = $this->getTestApiProvider();
        $this->assertNull($p('api', 'upyun', 'file-not-exists'));
    }

    public function testReturnsLatestServiceData()
    {
        $p = $this->getTestApiProvider(false);
        $this->assertEquals(['foo' => 'bar'], $p('api', 'upyun', 'latest'));
    }
    public function testReturnsNullWhenNoLatestVersionIsAvailable()
    {
        $p = $this->getTestApiProvider(false);
        $this->assertnull($p('api', 'no-service', 'latest'));
        $this->assertNull($p('api', 'service_no_version', 'latest'));
    }

}
