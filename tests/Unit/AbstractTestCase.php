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
        if (2 === substr_count($id, '.')) {
            // x.x.x
            $second = strpos($id, '.') + 1;
            $this->phpunitSeries = substr($id, 0, strpos($id, '.', $second));
        } elseif (1 === substr_count($id, '.')) {
            // x.x
            $this->phpunitSeries = $id;
        } else {
            // x
            $this->phpunitSeries = $id . '.0';
        }
        if ('' == $this->phpunitSeries) {
            $this->phpunitSeries = '4.0';
        }
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

    public function expectExceptionMessage($message)
    {
        if ($this->phpunitSeries < 5.2) {
            $this->setExpectedException($this->getExpectedException(), $message);
        } else {
            parent::expectExceptionMessage($message);
        }
    }
}
