<?php

/*
 * CloudAtlas
 * @link  : https://github.com/AbrahamGreyson/cloudatlas
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudAtlas\Api;

class ShapeMap
{
    /**
     * @var array
     */
    private $definitions;

    /**
     * @var Shape[]
     */
    private $simple;

    /**
     * @param array $shapeModels 形状定义的关联数组。
     */
    public function __construct(array $shapeModels)
    {
        $this->itions = $shapeModels;
    }

    /**
     * 获取形状名称列表。
     *
     * @return array
     */
    public function getShapeNames()
    {
        return array_keys($this->definitions);
    }

    /**
     *
     * @param array $shapeReference
     *
     * @return Shape
     */
    public function resolve(array $shapeReference)
    {
        $shape = $shapeReference['shape'];

        if (!isset($this->definitions[$shape])) {
            throw new \InvalidArgumentException("Shape not found: {$shape}");
        }

        $isSimple = count($shapeReference) == 1;
        if ($isSimple && isset($this->simple[$shape])) {
            return $this->simple[$shape];
        }

        $definition = $shapeReference + $this->definitions[$shape];
        $definition['name'] = $definition['shape'];
        unset($definition['shape']);

        $result = Shape::create($definition, $this);

        if ($isSimple) {
            $this->simple[$shape] = $result;
        }

        return $result;
    }
}
