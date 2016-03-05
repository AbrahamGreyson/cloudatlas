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
        $this->phpunitSeries = PHPUnit_Runner_Version::series();
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
