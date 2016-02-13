<?php
namespace CloudStorage\Facilities;

/**
 * 实现了 {@see CloudStorage\Contracts\Arrayable } 接口，
 * 实现了 \ArrayAccess, \Countable，\IteratorAggregate。
 * 用来降低同样要求实现这几个接口的类的代码重复。
 *
 * @package CloudStorage
 */
trait DataTrait
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }


    /**
     * 返回变量的引用，以便允许对多维数组进行修改。
     * 如：$foo['bar']['baz'] = 'qux'; 。
     *
     * @param $offset
     *
     * @return mixed|null
     */
    public function & offsetGet($offset)
    {
        if (isset($this->data[$offset])) {
            return $this->data[$offset];
        }

        $value = null;

        return $value;
    }

    /**
     * @param $offset
     * @param $value
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * @param $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * @param $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }
}
