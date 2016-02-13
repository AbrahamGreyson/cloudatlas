<?php

namespace CloudStorage\Contracts;

/**
 * 表示执行特定 REST API 操作返回的结果。
 */
interface ResultInterface extends \ArrayAccess, \Countable, \JsonSerializable
{
    public function get($key);

    public function has($key);

    public function __toString();

    //public function search();
}
