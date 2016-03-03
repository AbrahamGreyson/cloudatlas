<?php

/*
 * CloudStorage
 * @link  : https://github.com/AbrahamGreyson/cloudstorage
 * @author: AbrahamGreyson <82011220@qq.com>
 * @license: MIT
 */

namespace CloudStorage;

/**
 * 代表一个处理器列表。
 *
 * 创建一个单一的处理器，或带有中间件的处理器。然后此列表将被用来发送命令对象并
 * 返回一个 promise，promise 代表已完成的 ResultInterface。
 *
 * 列表中的处理器/中间件按照从前到后的顺序被调用。可以使用带有 prepend 的方法添加中间件至
 * 处理器列表的前面，使用带有 append 的方法添加中间件至处理器列表的后面。处理器列表中最后一个
 * 被调用的函数是处理器（函数不接受下一个处理器，而是负责返回一个 promise，用来表示一个已完成
 * 的 {@see \CloudStorage\Contracts\ResultInterface} 对象）。
 *
 * 列表中的处理器以 steps 属性排序， 用来描述 SDK 在发送命令时处于哪一步。可用的步骤包括：
 *
 * - init：命令被初始化，允许你做诸如添加默认选项的事。
 * - validate：一个命令将在它被序列化之前验证。
 * - build：命令被序列化成为一个 HTTP 请求。位于此步骤中的中间件必须序列化 HTTP 请求并将其
 *   填充至命令参数 @request 中，使其能在随后的中间件中可用。
 * - sign：请求将被签名并准备通过网络发送。
 *
 * 中间件能以一个名称来注册，便于通过名称简单的将它添加到另一个中间件的之前或之后。也便于
 * 通过名称移除一个中间件（还能通过实例移除）。
 */
class HandlerList implements \Countable
{
    const INIT = 'init';
    const VALIDATE = 'validate';
    const BUILD = 'build';
    const SIGN = 'sign';
    const HANDLE = 'handle';

    /**
     * @var callable 处理器列表中的唯一一个处理器（其它的是中间件）
     */
    private $handler;

    /**
     * 某个命名的中间件属于哪个步骤，结构：.
     *
     * <code>
     * [
     *     'name' => self::INIT,
     *     'name2' => self::BUILD,
     * ]
     * </code>
     *
     * @var array
     */
    private $named = [];

    /**
     * 排序过的中间件。
     *
     * <code>
     * [
     *     function(){},
     *     function(){},
     * ]
     * </code>
     *
     * @var array
     */
    private $sorted = [];

    /**
     * @var callable|null
     */
    private $interposeFn;

    /**
     * @var array 中间件执行步骤的相反顺序。
     */
    private $steps = [
        self::HANDLE   => [],
        self::SIGN     => [],
        self::BUILD    => [],
        self::VALIDATE => [],
        self::INIT     => [],
    ];

    /**
     * @param callable|null $handler 默认处理器
     */
    public function __construct(callable $handler = null)
    {
        $this->handler = $handler;
    }

    /**
     * 处理器列表的字符串表示。
     *
     * @return string
     */
    public function __toString()
    {
        $str = '';
        $i = 0;

        foreach (array_reverse($this->steps) as $k => $step) {
            foreach (array_reverse($step) as $j => $tuple) {
                $str .= "{$i}) Step: {$k}, ";
                if ($tuple[1]) {
                    $str .= "Name: {$tuple[1]}, ";
                }
                $str .= 'Function: '.$this->debugCallable($tuple[0])."\n";
                $i++;
            }
        }

        if ($this->handler) {
            $str .= "{$i} Handler: ".$this->debugCallable($this->handler).
                "\n";
        }

        return $str;
    }

    /**
     * 为给定 callable 类型生成字符串表示用于 debug。
     *
     * @param array|callable $fn 准备生成字符串的函数。
     *
     * @return string
     */
    private function debugCallable($fn)
    {
        if (is_string($fn)) {
            return "callable({$fn})";
        } elseif (is_array($fn)) {
            $element = is_string($fn[0]) ? $fn[0] : get_class($fn[0]);

            return "callable(['{$element}', '{$fn[1]}'])";
        } else {
            return 'callable('.spl_object_hash($fn).')';
        }
    }

    /**
     * 设置 HTTP 处理器。
     *
     * @param callable $handler 接受请求和数组选项作为参数，并返回 promise 的函数。
     */
    public function setHandler(callable $handler)
    {
        $this->handler = $handler;
    }

    /**
     * 判断当前列表是否已经有处理器。
     *
     * @return bool
     */
    public function hasHandler()
    {
        return (bool) $this->handler;
    }

    /**
     * 附加一个中间件至 INIT 步骤之后。
     *
     * @param callable    $middleware 要添加的中间件函数。
     * @param null|string $name       中间件的名称。
     */
    public function prependInit(callable $middleware, $name = null)
    {
        $this->add(self::INIT, $name, $middleware, true);
    }

    /**
     * 附加一个中间件至 INIT 步骤之前。
     *
     * @param callable    $middleware 要添加的中间件函数。
     * @param null|string $name       中间件的名称。
     */
    public function appendInit(callable $middleware, $name = null)
    {
        $this->add(self::INIT, $name, $middleware);
    }

    /**
     * 附加一个中间件至  VALIDATE 步骤之后。
     *
     * @param callable    $middleware 要添加的中间件函数。
     * @param null|string $name       中间件的名称。
     */
    public function prependValidate(callable $middleware, $name = null)
    {
        $this->add(self::VALIDATE, $name, $middleware, true);
    }

    /**
     * 附加一个中间件至  VALIDATE 步骤之前。
     *
     * @param callable    $middleware 要添加的中间件函数。
     * @param null|string $name       中间件的名称。
     */
    public function appendValidate(callable $middleware, $name = null)
    {
        $this->add(self::VALIDATE, $name, $middleware);
    }

    /**
     * 附加一个中间件至  BUILD 步骤之后。
     *
     * @param callable    $middleware 要添加的中间件函数。
     * @param null|string $name       中间件的名称。
     */
    public function prependBuild(callable $middleware, $name = null)
    {
        $this->add(self::BUILD, $name, $middleware, true);
    }

    /**
     * 附加一个中间件至  BUILD 步骤之前。
     *
     * @param callable    $middleware 要添加的中间件函数。
     * @param null|string $name       中间件的名称。
     */
    public function appendBuild(callable $middleware, $name = null)
    {
        $this->add(self::BUILD, $name, $middleware);
    }

    /**
     * 附加一个中间件至  SIGN 步骤之后。
     *
     * @param callable    $middleware 要添加的中间件函数。
     * @param null|string $name       中间件的名称。
     */
    public function prependSign(callable $middleware, $name = null)
    {
        $this->add(self::SIGN, $name, $middleware, true);
    }

    /**
     * 附加一个中间件至  SIGN 步骤之前。
     *
     * @param callable    $middleware 要添加的中间件函数。
     * @param null|string $name       中间件的名称。
     */
    public function appendSign(callable $middleware, $name = null)
    {
        $this->add(self::SIGN, $name, $middleware);
    }

    /**
     * 根据名称添加一个中间件至给定中间件之前。
     *
     * @param string|callable $search     添加到这个中间件之前。
     * @param string          $name       添加的中间件名称。
     * @param callable        $middleware 添加的中间件。
     */
    public function before($search, $name, callable $middleware)
    {
        $this->insert($search, $name, $middleware, true);
    }

    /**
     * 根据名称添加一个中间件至给定中间件之后。
     *
     * @param string|callable $search     添加到这个中间件之后。
     * @param string          $name       添加的中间件名称。
     * @param callable        $middleware 添加的中间件。
     */
    public function after($search, $name, callable $middleware)
    {
        $this->insert($search, $name, $middleware);
    }

    /**
     * 根据名称或实例从处理器列表中移除一个中间件。
     *
     * @param string|callable $nameOrInstance 要移除的中间件。
     */
    public function remove($nameOrInstance)
    {
        if (is_callable($nameOrInstance)) {
            $this->removeByInstance($nameOrInstance);
        } elseif (is_string($nameOrInstance)) {
            $this->removeByName($nameOrInstance);
        }
    }

    /**
     * 根据名称从列表中移除中间件。
     *
     * @param string $name
     */
    private function removeByName($name)
    {
        if (! isset($this->named[$name])) {
            return;
        }

        $this->sorted = null;
        $step = $this->named[$name];
        $this->steps[$step] = array_values(
            array_filter(
                $this->steps[$step],
                function ($tuple) use ($name) {
                    return $tuple[1] !== $name;
                }
            )
        );
    }

    /**
     * 根据实例从列表中移除中间件。
     *
     * @param callable $fn
     */
    private function removeByInstance(callable $fn)
    {
        foreach ($this->steps as $k => $step) {
            foreach ($step as $j => $tuple) {
                if ($tuple[0] === $fn) {
                    $this->sorted = null;
                    unset($this->named[$this->steps[$k][$j][1]]);
                    unset($this->steps[$k][$j]);
                }
            }
        }
    }

    /**
     * 通过嵌套的方式，组合中间件和处理器至单一的函数。
     * 这个方法返回的结果函数，执行它，就是处理器列表对象表示的，
     * 从 INIT 到发送命令的 HTTP 请求的全部过程。
     *
     * @return callable
     */
    public function resolve()
    {
        if (! ($prev = $this->handler)) {
            throw new \LogicException('No handler has been specified.');
        }

        if ($this->sorted === null) {
            $this->sortMiddleware();
        }

        foreach ($this->sorted as $fn) {
            $prev = $fn($prev);
        }

        return $prev;
    }

    /**
     * 在中间件列表的特定位置插入一个新的中间件函数。
     *
     * @param string   $search
     * @param string   $name
     * @param callable $middleware
     * @param bool     $before
     */
    private function insert($search, $name, callable $middleware, $before = false)
    {
        if (! isset($this->named[$search])) {
            throw new \InvalidArgumentException("$search not found.");
        }

        $index = $this->sorted = null;
        $step = $this->named[$search];

        if ($name) {
            $this->named[$name] = $step;
        }

        foreach ($this->steps[$step] as $i => $tuple) {
            if ($tuple[1] == $search) {
                $index = $i;
                break;
            }
        }

        $replacement = $before
            ? [$this->steps[$step][$index], [$middleware, $name]]
            : [[$middleware, $name], $this->steps[$step][$index]];

        array_splice($this->steps[$step], $index, 1, $replacement);
    }

    /**
     * 计数。
     *
     * @return int
     */
    public function count()
    {
        return count($this->steps[self::INIT])
        + count($this->steps[self::VALIDATE])
        + count($this->steps[self::BUILD])
        + count($this->steps[self::SIGN])
        + count($this->steps[self::HANDLE]);
    }

    /**
     * 在每个中间件之间插入一个函数，这样能为中间件层面提供追踪能力，例如 debug、日志。
     *
     * 传入的函数接受字符串类型的 step 参数和 name 参数。必须在其中返回另一个函数，
     * 该函数接受处理器列表中的下一个处理器。然后返回另一个函数，该函数接受一个
     * CommandInterface 参数和一个可选的 RequestInterface 参数，并返回一个 promise，
     * promise 表示已完成的 ResultInterface 或已失败的 CloudStorageException 对象。
     *
     * 参数应该是这个样子：
     *
     * <code>
     * $fn = function(SELF::INIT, 'init-trace') {
     *      return function(Handler $handler) {
     *          return function (
     *                  CommandInterface $command,
     *                  RequestInterface $request = null
     *              )  use ($handler) {
     *              // do something
     *              $result = $handler->doSomething();
     *              return \GuzzleHttp\promises\promise_for($result);
     *          }
     *      }
     * }
     * </code>
     *
     * @param callable|null $fn null 表示移除先前所有设置过的函数。
     */
    public function interpose(callable $fn = null)
    {
        $this->sorted = null;
        $this->interposeFn = $fn;
    }

    /**
     * 给中间件排序。
     */
    private function sortMiddleware()
    {
        $this->sorted = [];
        if (! $this->interposeFn) {
            foreach ($this->steps as $step) {
                foreach ($step as $fn) {
                    $this->sorted[] = $fn[0];
                }
            }

            return;
        }

        // 如果有干预者方法，把干预者插入到每个处理器之前。
        $ifn = $this->interposeFn;
        foreach ($this->steps as $stepName => $step) {
            foreach ($step as $fn) {
                $this->sorted[] = $ifn($stepName, $fn[1]);
                $this->sorted[] = $fn[0];
            }
        }
    }

    /**
     * 添加中间件到某个步骤。
     *
     * @param string   $step       中间件步骤。
     * @param string   $name       中间件名。
     * @param callable $middleware 中间件函数。
     * @param bool     $prepend    是否前置。
     */
    private function add($step, $name, callable $middleware, $prepend = false)
    {
        $this->sorted = null;
        if ($prepend) {
            $this->steps[$step][] = [$middleware, $name];
        } else {
            array_unshift($this->steps[$step], [$middleware, $name]);
        }

        if ($name) {
            $this->named[$name] = $step;
        }
    }
}
