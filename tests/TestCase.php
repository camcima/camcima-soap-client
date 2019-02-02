<?php

namespace Camcima\Tests;

use Camcima\Soap\Client;

class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Soap Client Test Instance
     *
     * @var \Camcima\Soap\Client
     */
    protected $soapClient;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $wsdl = __DIR__ . '/fixtures/sample.wsdl';

        $options = array(
            'trace' => false
        );

        $this->soapClient = new Client($wsdl, $options);
    }

    /**
     * @param $name
     * @return \ReflectionMethod
     * @throws \ReflectionException
     */
    protected static function getPrivateMethod($name)
    {
        $class = new \ReflectionClass(Client::class);

        $method = $class->getMethod($name);

        $method->setAccessible(true);

        return $method;
    }
}