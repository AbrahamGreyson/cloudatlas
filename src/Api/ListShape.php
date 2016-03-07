<?php

/*
 * CloudAtlas
 * @link  : https://github.com/AbrahamGreyson/cloudatlas
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudAtlas\Api;

/**
 * 代表一个列表形状。
 */
class ListShape extends Shape
{
    private $member;

    public function __construct(array $definition, ShapeMap $shapeMap)
    {
        $definition['type'] = 'list';
        parent::__construct($definition, $shapeMap);
    }

    /**
     * @return Shape
     * @throws \RuntimeException 如果没有指定 member。
     */
    public function getMember()
    {
        if (!$this->member) {
            if (!isset($this->definition['member'])) {
                throw new \RuntimeException('No member attribute specified');
            }
            $this->member = Shape::create(
                $this->definition['member'],
                $this->shapeMap
            );
        }

        return $this->member;
    }
}
