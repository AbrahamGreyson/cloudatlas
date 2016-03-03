<?php

/*
 * CloudStorage
 * @link  : https://github.com/AbrahamGreyson/cloudstorage
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudStorage\Api;

class ShapeMap
{
    private $definitions;

    private $simple;

    public function __construct(array $shapeModels)
    {
        $this->definitions = $shapeModels;
    }

    public function getShapeNames()
    {
        return array_keys($this->definitions);
    }

    public function resolve(array $shapeReference)
    {
        $shape = $shapeReference['shape'];

        if (! isset($this->definitions[$shape])) {
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
            $this->simple[$shape] = $resule;
        }

        return $result;
    }
}
