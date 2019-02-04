<?php

namespace Camcima\Tests\Fixtures;

/**
 * ParentClass Fixture
 *
 * @author Carlos Cima
 */
class ParentClass
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    public $children;

    /**
     * @var ChildClass 
     */
    public $eldestChild;
    
    /**
     * @var null 
     */
    public $nullAttribute;

    /**
     * Constructor
     * 
     * @param string $name
     */
    function __construct($name)
    {
        $this->name = $name;
        $this->children = array();
    }

    /**
     * Add Children
     * 
     * @param \Camcima\Tests\Fixtures\ChildClass $children
     * @return \Camcima\Tests\Fixtures\ParentClass
     */
    public function addChildren(ChildClass $children)
    {
        $this->children[] = $children;
        return $this;
    }

    /**
     * Set Special Child
     * 
     * @param \Camcima\Tests\Fixtures\ChildClass $eldestChild
     * @return \Camcima\Tests\Fixtures\ParentClass
     */
    public function setEldestChild(ChildClass $eldestChild)
    {
        $this->eldestChild = $eldestChild;
        return $this;
    }
}
