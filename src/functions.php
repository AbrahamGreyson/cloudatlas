<?php

namespace CloudStorage;

/**
 * 调试函数，用来描述给定值的类型或类。
 *
 * @param mixed $input
 *
 * @return string 返回一个字符串包含给定变量的类型，如果给定的是对象，则返回类名。
 */
function describeType($input)
{
    switch (gettype($input)) {
        case 'object':
            return 'object(' . get_class($input) . ')';
        case 'array':
            return 'array(' . count($input) . ')';
        default:
            ob_start();
            var_dump($input);

            // 统一化浮点数
            return str_replace('double(', 'float(', rtrim(ob_get_clean()));
    }
}