<?php

namespace Camcima\Soap\Test\Fixtures;

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
     * @param \Camcima\Soap\Test\Fixtures\ChildClass $children
     * @return \Camcima\Soap\Test\Fixtures\ParentClass
     */
    public function addChildren(ChildClass $children)
    {
        $this->children[] = $children;
        return $this;
    }

    /**
     * Set Special Child
     * 
     * @param \Camcima\Soap\Test\Fixtures\ChildClass $eldestChild
     * @return \Camcima\Soap\Test\Fixtures\ParentClass
     */
    public function setEldestChild(ChildClass $eldestChild)
    {
        $this->eldestChild = $eldestChild;
        return $this;
    }
}
