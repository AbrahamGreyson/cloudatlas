<?php

/*
 * CloudStorage
 * @link  : https://github.com/AbrahamGreyson/cloudstorage
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudStorage\Api;

use CloudStorage\Contracts\Arrayable;

/**
 * API 基类。
 *
 * @package CloudStorage\Api
 */
abstract class AbstractModel implements \ArrayAccess, \Countable, Arrayable
{
    /**
     * @var array
     */
    protected $definition;

    /**
     * @var ShapeMap
     */
    protected $shapeMap;

    /**
     * @param array    $definition 服务描述。
     * @param ShapeMap $shapeMap   用来创建形状的形状表。
     */
    public function __construct(array $definition, ShapeMap $shapeMap)
    {
        $this->definition = $definition;
        $this->shapeMap = $shapeMap;
    }

    public function toArray()
    {
        return $this->definition;
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
        if (!isset($this->definition[$key])) {
            throw new \InvalidArgumentException(
                "Expected shape definition at {$key}"
            );
        }

        return $this->shapeFor($this->definition[$key]);
    }

    protected function shapeFor(array $definition)
    {
        return isset($definition['shape'])
            ? $this->shapeMap->resolve($definition)
            : Shape::create($definition, $this->shapeMap);
    }
}
