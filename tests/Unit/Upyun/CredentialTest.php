<?php

namespace CloudStorage\Test\Unit\Upyun;

use CloudStorage\Test\Unit\AbstractTestCase;
use CloudStorage\Upyun\Credential;

class CredentialTest extends AbstractTestCase
{
    public function testGettersAndSetters()
    {
        $credential = new Credential('foo', 'bar');
        $this->assertEquals('foo', $credential->getKey());
        $this->assertEquals('bar', $credential->getSecret());
        $this->assertEquals([
            'key'    => 'foo',
            'secret' => 'bar',
        ], $credential->toArray());
        $this->assertContains(
            '{"key":"foo","secret":"bar"}', serialize($credential)
        );

        $newCredential = new Credential('alpha', 'beta');
        $data = serialize($newCredential);
        $final = unserialize($data);
        $this->assertEquals([
            'key'    => 'alpha',
            'secret' => 'beta',
        ], $final->toArray());

        $credential = new Credential('foo', 'bar');
        ob_start();
        var_export($credential);
        $code = ob_get_clean() . ';';
        eval('$object = ' . $code);
        /** @var \CloudStorage\Upyun\Credential $object */
        $this->assertEquals($object, $credential);
    }
}

