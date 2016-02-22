<?php

/*
 * CloudStorage
 * @link  : https://github.com/AbrahamGreyson/cloudstorage
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudStorage\Api;

use Aws\Api\ShapeMap;
use CloudStorage\Contracts\Arrayable;

/**
 * API 基类。
 *
 * @package CloudStorage\Api
 */
abstract class AbstractModel implements \ArrayAccess, \Countable, Arrayable
{
    protected $definition;

    protected $shapeMap;

    public function __construct(array $definition, ShapeMap $shapeMap)
    {
        $this->definition = $definition;
        $this->shapeMap = $shapeMap;
    }

    public function toArray()
    {
    }

    public function count()
    {
        return count($this->definition);
    }

    public function offsetGet($offset)
    {
        return isset($this->definition[$offset])
            ? $this->definition[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        $this->definition[$offset] = $value;
    }

    public function offsetExists($offset)
    {
        return isset($this->definition[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->definition[$offset]);
    }

    protected function shapeAt($key)
    {
    }

    protected function shapeFor(array $definition)
    {
    }
}