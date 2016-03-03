<?php

/*
 * CloudStorage
 * @link  : https://github.com/AbrahamGreyson/cloudstorage
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudStorage\Api;

/**
 * 代表 API 模型形状的基类。
 */
class Shape extends AbstractModel
{
    public static function create(array $definition, ShapeMap $shapeMap)
    {
        static $map = [
            'structure' => [],
            'map' => [],
            'list' => [],
            'timestamp' => [],
            'integer' => [],
            'double' => [],
            'float' => [],
            'long' => [],
            'string' => [],
            'byte' => [],
            'character' => [],
            'blob' => [],
            'boolean' => [],
        ];

        if (isset($definition['shape'])) {
            return $shapeMap->resolve($definition);
        }

        if (!isset($map[$definition['type']])) {
            throw new \RuntimeException(
                'Invalid type: '.print_r($definition, true)
            );
        }

        $type = $map[$definition['type']];

        return new $type($definition, $shapeMap);
    }

    public function getType()
    {
        return $this->definition['type'];
    }

    public function getName()
    {
        return $this->definition['name'];
    }
}
