<?php

namespace CloudStorage\Test\Unit;

use PHPUnit_Runner_Version;

abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string phpunit major version string.
     */
    protected $phpunitSeries;

    public function setUp()
    {
        $id = PHPUnit_Runner_Version::id();
        $second = strpos($id, '.') + 1;
        $this->phpunitSeries = substr($id, 0, strpos($id, '.', $second));
    }

    public function tearDown()
    {
        $this->phpunitSeries = null;
    }

    /**
     * In phpunit 5.2 setExpectedException() method is deprecated.
     *
     * @param mixed $exception
     */
    public function expectException($exception)
    {
        if ($this->phpunitSeries < 5.2) {
            $this->setExpectedException($exception);
        } else {
            parent::expectException($exception);
        }
    }
}
