<?php

/*
 * CloudStorage
 * @link  : https://github.com/AbrahamGreyson/cloudstorage
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudStorage\Api;

/**
 * 验证一组输入是否符合对应 API 要求。
 */
class Validator
{
    private $path = [];
    private $errors = [];
    private $constrains = [];

    private static $defaultConstrains = [
        'required' => true,
        'min'      => true,
        'max'      => true,
        'pattern'  => false,
    ];

    /**
     * @param array|null $constrains 约束执行的关联数组。接受下述键名：required， min，
     *                               max，以及 pattern。如果没有提供某个键名，约束将
     *                               视为失败。
     */
    public function __construct(array $constrains = null)
    {
        static $assumedFalseValues = [
            'required' => false,
            'min'      => false,
            'max'      => false,
            'pattern'  => false,
        ];
        $this->constrains = empty($constrains)
            ? self::$defaultConstrains
            : $constrains + $assumedFalseValues;
    }

    /**
     * 根据特定 API 模式验证给定数据。
     *
     * @param string $name
     * @param Shape  $shape
     * @param array  $input
     *
     * @throws \InvalidArgumentException 如果给定值无效。
     */
    public function validate($name, Shape $shape, array $input)
    {
        $this->dispatch($shape, $input);

        if ($this->errors) {
            $message = sprintf(
                'Found %d error%s while validating the input provided for the '
                ."%s operations: \n%s",
                count($this->errors),
                count($this->errors) > 1 ? 's' : '',
                $name,
                implode("\n", $this->errors)
            );
            $this->errors = [];

            throw new \InvalidArgumentException($message);
        }
    }

    private function dispatch($shape, $value)
    {
        static $methods = [
            'structure' => 'checkStructure',
            'list'      => 'checkList',
            'map'       => 'checkMap',
            'blob'      => 'checkBlob',
            'boolean'   => 'checkBoolean',
            'integer'   => 'checkNumeric',
            'float'     => 'checkNumeric',
            'long'      => 'checkNumeric',
            'string'    => 'checkString',
            'byte'      => 'checkString',
            'char'      => 'checkString',
        ];

        $type = $shape->getType();
        if (isset($methods[$type])) {
            $this->{$methods[$type]}($shape, $value);
        }
    }

    private function checkStructure(StructureShape $shape, $value)
    {
        if (! $this->checkAssociativeArray($value)) {
            return;
        }

        if ($this->constrains['required'] && $shape['required']) {
            foreach ($shape['required'] as $req) {
                if (! isset($value[$req])) {
                    $this->path[] = $req;
                    $this->addError('is missing and is a required parameter');
                    array_pop($this->path);
                }
            }
        }

        foreach ($value as $name => $v) {
            if ($shape->hasMember($name)) {
                $this->path[] = $name;
                $this->dispatch(
                    $shape->getMember($name),
                    isset($value[$name]) ? $value[$name] : null
                );
                array_pop($this->path);
            }
        }
    }

    private function checkList(ListShape $shape, $value)
    {
        if (! is_array($value)) {
            $this->addError('must be an array. Found '
                .\CloudStorage\describeType($value));

            return;
        }

        $this->validateRange($shape, count($value), 'list element count');

        $item = $shape->getMember();
        foreach ($value as $index => $v) {
            $this->path[] = $index;
            $this->dispatch($item, $v);
            array_pop($this->path);
        }
    }

    private function checkMap(MapShape $shape, $value)
    {
        if (! $this->checkAssociativeArray($value)) {
            return;
        }

        $value = $shape->getValue();
        foreach ($value as $key => $v) {
            $this->path[] = $key;
            $this->dispatch($value, $v);
            array_pop($this->path);
        }
    }

    private function checkBlob(Shape $shape, $value)
    {
        static $valid = [
            'string'   => true,
            'integer'  => true,
            'double'   => true,
            'resource' => true,
        ];

        $type = gettype($value);
        if (! isset($valid[$type])) {
            if ($type != 'object' || ! method_exists($value, '__toString')) {
                $this->addError('must be an fopen resource, a '
                    .'GuzzleHttp\Stream\StreamInterface object, or something '
                    .'that can be cast to a string. Found '
                    .\CloudStorage\describeType($value));
            }
        }
    }

    private function checkNumric(Shape $shape, $value)
    {
        if (! is_numeric($value)) {
            $this->addError('must be numeric. Found '
                .\CloudStorage\describeType($value));

            return;
        }
        $this->validateRange($shape, $value, 'numeric value');
    }

    private function checkBoolean(Shape $shape, $value)
    {
        if (! is_bool($value)) {
            $this->addError('must be a boolean. Found '
                .\CloudStorage\describeType($value));
        }
    }

    private function checkString(Shape $shape, $value)
    {
        if (! $this->checkCanString($value)) {
            $this->addError('must be a string or an object that implements '
                .'__toString(). Found '.\CloudStorage\describeType($value));

            return;
        }

        if ($this->constrains['pattern']) {
            $pattern = $shape['pattern'];
            if ($pattern && ! preg_match("/$pattern/", $value)) {
                $this->addError("Pattern /$pattern/ failed to match '$value'");
            }
        }
    }

    private function checkCanString($value)
    {
        static $valid = [
            'string'  => true,
            'integer' => true,
            'double'  => true,
            'NULL'    => true,
        ];

        $type = gettype($value);

        return isset($valid[$type]) ||
        ('object' == $type && method_exists($value, '__toString'));
    }

    private function validateRange(Shape $shape, $length, $descriptor)
    {
        if ($this->constrains['min']) {
            $min = $shape['min'];
            if ($min && $length < $min) {
                $this->addError("expected $descriptor to be >= $min, but "
                    ."found $descriptor of $length");
            }
        }

        if ($this->constrains['max']) {
            $max = $shape['max'];
            if ($max && $length > $max) {
                $this->addError("expected $descriptor to be <= $max, but "
                    ."found $descriptor of $length");
            }
        }
    }

    private function checkAssociativeArray($value)
    {
        if (! is_array($value) || isset($value[0])) {
            $this->addError('must be an associate array. Found '
                .\CloudStorage\describeType($value));

            return false;
        }

        return true;
    }

    private function addError($message)
    {
        $this->errors[] =
            implode('', array_map(function ($s) {
                return "[{$s}]";
            }, $this->path))
            .' '
            .$message;
    }
}
