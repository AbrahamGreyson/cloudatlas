<?php
namespace CloudStorage\Api;

/**
 * 代表一个结构形状并解析成员形状的引用。
 */
class StructureShape extends Shape
{
    /**
     * @var Shape[]
     */
    private $members;

    public function __construct(array $definition, ShapeMap $shapeMap)
    {
        $definition['type'] = 'structure';

        if (!isset($definition['members'])) {
            $definition['members'] = [];
        }

        parent::__construct($definition, $shapeMap);
    }

    /**
     * 获取所有成员的列表。
     *
     * @return Shape[]
     */
    public function getMembers()
    {
        if (empty($this->members)) {
            $this->generateMembersHash();
        }

        return $this->members;
    }

    /**
     * 根据名称检查一个成员是否存在。
     *
     * @param string $name 要检查的成员名称。
     *
     * @return bool
     */
    public function hasMember($name)
    {
        return isset($this->definition['members'][$name]);
    }

    /**
     * 根据名称获取一个成员。
     *
     * @param string $name 要获取的成员名称。
     *
     * @return Shape
     * @throws \InvalidArgumentException 如果成员没找到。
     */
    public function getMember($name)
    {
        $members = $this->getMembers();

        if (!isset($members[$name])) {
            throw new \InvalidArgumentException('Unknown member ' . $name);
        }

        return $members[$name];
    }


    private function generateMembersHash()
    {
        $this->members = [];

        foreach ($this->definition['members'] as $name => $definition) {
            $this->members[$name] = $this->shapeFor($definition);
        }
    }
}
