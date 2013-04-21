<?php

namespace Camcima\Soap\Test\Fixtures;

/**
 * ChildClass Fixture
 *
 * @author Carlos Cima
 */
class ChildClass
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var int 
     */
    public $age;

    /**
     * Constructor
     * 
     * @param string $name
     * @param int $age
     */
    function __construct($name, $age)
    {
        $this->name = $name;
        $this->age = $age;
    }
}
