<?php

/*
 * CloudStorage
 * @link  : https://github.com/AbrahamGreyson/cloudstorage
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudStorage\Api;

/**
 * 代表一个时间戳形状。
 */
class TimestampShape extends Shape
{
    public function __construct(array $definition, ShapeMap $shapeMap)
    {
        $definition['type'] = 'timestamp';
        parent::__construct($definition, $shapeMap);
    }

    /**
     * 为某个服务格式化时间。
     *
     * @param mixed  $value  要格式化的值。
     * @param string $format 使用的格式。
     *
     * @return int|string
     *
     * @throws \UnexpectedValueException 如果格式未找到。
     * @throws \InvalidArgumentException 如果值的格式不支持。
     */
    public static function format($value, $format)
    {
        if ($value instanceof \DateTime) {
            $value = $value->getTimestamp();
        } elseif (is_string($value)) {
            $value = strtotime($value);
        } elseif (!is_int($value)) {
            throw new \InvalidArgumentException('Unable to handle the provided'
                .' timestamp type: '.gettype($value));
        }

        switch ($format) {
            case 'iso8601':
                // todo gmdate
                return gmdate('Y-m-d\TH:i:s\Z', $value);
            case 'rfc822':
                return gmdate('D, d M Y H:i:s \G\M\T', $value);
            case 'unixTimestamp':
                return $value;
            default:
                throw new \UnexpectedValueException('Unknown timestamp format: '
                    .$format);
        }
    }
}
