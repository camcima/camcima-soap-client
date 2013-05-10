<?php

namespace Camcima\Soap\Test;

use Camcima\Soap\Client;
use Camcima\Soap\Test\Fixtures\ParentClass;
use Camcima\Soap\Test\Fixtures\ChildClass;
use Camcima\Soap\Test\Fixtures\GetCityForecastByZIP;

/**
 * SoapClientTest
 *
 * @author Carlos Cima <ccima@rocket-internet.com>
 */
class ClientTest extends \PHPUnit_Framework_TestCase
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
     * testConstruct
     */
    public function testConstruct()
    {
        $this->assertInstanceOf('\Camcima\Soap\Client', $this->soapClient);
    }

    /**
     * testInheritance
     */
    public function testInheritance()
    {
        $functions = $this->soapClient->__getFunctions();
        $this->assertInternalType('array', $functions);
        $this->assertCount(23, $functions);
    }

    /**
     * testGetCurlOptions
     */
    public function testGetCurlOptions()
    {
        $curlOptions1 = $this->soapClient->getCurlOptions();
        $this->assertInternalType('array', $curlOptions1);
        $this->assertCount(5, $curlOptions1);

        $curlOptionsFixture = array(
            CURLOPT_CRLF => true, // new value
            CURLOPT_HEADER => true, // mandatory value
            CURLOPT_SSL_VERIFYPEER => true // default value
        );
        $this->soapClient->setCurlOptions($curlOptionsFixture);

        $curlOptions2 = $this->soapClient->getCurlOptions();
        $this->assertCount(6, $curlOptions2);
        $this->assertArrayHasKey(CURLOPT_CRLF, $curlOptions2);
        $this->assertArrayHasKey(CURLOPT_HEADER, $curlOptions2);
        $this->assertArrayHasKey(CURLOPT_SSL_VERIFYPEER, $curlOptions2);
        $this->assertTrue($curlOptions2[CURLOPT_CRLF]);
        $this->assertTrue($curlOptions2[CURLOPT_HEADER]);
        $this->assertTrue($curlOptions2[CURLOPT_SSL_VERIFYPEER]);
    }

    /**
     * testGetUseProxy
     */
    public function testGetUseProxy()
    {
        $this->soapClient->useProxy();
        $curlOptions = $this->soapClient->getCurlOptions();

        $this->assertArrayHasKey(CURLOPT_PROXY, $curlOptions);
        $this->assertArrayHasKey(CURLOPT_PROXYPORT, $curlOptions);
        $this->assertEquals(Client::DEFAULT_PROXY_HOST, $curlOptions[CURLOPT_PROXY]);
        $this->assertEquals(Client::DEFAULT_PROXY_PORT, $curlOptions[CURLOPT_PROXYPORT]);
    }

    /**
     * testGetSoapVariables
     */
    public function testGetSoapVariables()
    {
        $firstChild = new ChildClass('Child 1', 7);
        $secondChild = new ChildClass('Child 2', 4);
        $thirdChild = new ChildClass('Child 3', 1);

        $parent = new ParentClass('Parent');
        $parent->addChildren($firstChild)->addChildren($secondChild)->addChildren($thirdChild);
        $parent->setEldestChild($firstChild);

        $soapVars = $this->soapClient->getSoapVariables($parent);
        $this->assertInternalType('array', $soapVars);
        $this->assertCount(1, $soapVars);
        $this->assertArrayHasKey('ParentClass', $soapVars);

        $parentClassNode = $soapVars['ParentClass'];
        $this->assertInternalType('array', $parentClassNode);
        $this->assertCount(4, $parentClassNode);
        $this->assertArrayHasKey('name', $parentClassNode);
        $this->assertArrayHasKey('children', $parentClassNode);
        $this->assertArrayHasKey('eldestChild', $parentClassNode);
        $this->assertArrayHasKey('nullAttribute', $parentClassNode);
        $this->assertEquals('Parent', $parentClassNode['name']);
        $this->assertInternalType('string', $parentClassNode['nullAttribute']);
        $this->assertEmpty($parentClassNode['nullAttribute']);

        $childrenNode = $parentClassNode['children'];
        $eldestChildNode = $parentClassNode['eldestChild'];
        $this->assertInternalType('array', $childrenNode);
        $this->assertCount(3, $childrenNode);
        $firstChildNode = $childrenNode[0];
        $secondChildNode = $childrenNode[1];
        $thirdChildNode = $childrenNode[2];

        $this->assertInternalType('array', $firstChildNode);
        $this->assertCount(2, $firstChildNode);
        $this->assertEquals('Child 1', $firstChildNode['name']);
        $this->assertEquals('7', $firstChildNode['age']);
        $this->assertInternalType('string', $firstChildNode['age']);

        $this->assertInternalType('array', $secondChildNode);
        $this->assertCount(2, $secondChildNode);
        $this->assertEquals('Child 2', $secondChildNode['name']);
        $this->assertEquals('4', $secondChildNode['age']);
        $this->assertInternalType('string', $secondChildNode['age']);

        $this->assertInternalType('array', $thirdChildNode);
        $this->assertCount(2, $thirdChildNode);
        $this->assertEquals('Child 3', $thirdChildNode['name']);
        $this->assertEquals('1', $thirdChildNode['age']);
        $this->assertInternalType('string', $thirdChildNode['age']);

        $this->assertInternalType('array', $eldestChildNode);
        $this->assertCount(2, $eldestChildNode);
        $this->assertEquals('Child 1', $eldestChildNode['name']);
        $this->assertEquals('7', $eldestChildNode['age']);
        $this->assertInternalType('string', $eldestChildNode['age']);
    }

    /**
     * testGetSoapVariablesWithOptions
     */
    public function testGetSoapVariablesWithOptions()
    {
        $firstChild = new ChildClass('Child 1', 7);
        $secondChild = new ChildClass('Child 2', 4);
        $thirdChild = new ChildClass('Child 3', 1);

        $parent = new ParentClass('Parent');
        $parent->addChildren($firstChild)->addChildren($secondChild)->addChildren($thirdChild);
        $parent->setEldestChild($firstChild);

        $lowerCaseFirst = true;
        $keepNullProperties = false;
        $soapVars = $this->soapClient->getSoapVariables($parent, $lowerCaseFirst, $keepNullProperties);
        $this->assertInternalType('array', $soapVars);
        $this->assertCount(1, $soapVars);
        $this->assertArrayHasKey('parentClass', $soapVars);

        $parentClassNode = $soapVars['parentClass'];
        $this->assertInternalType('array', $parentClassNode);
        $this->assertCount(3, $parentClassNode);
        $this->assertArrayHasKey('name', $parentClassNode);
        $this->assertArrayHasKey('children', $parentClassNode);
        $this->assertArrayHasKey('eldestChild', $parentClassNode);
        $this->assertArrayNotHasKey('nullAttribute', $parentClassNode);
        $this->assertEquals('Parent', $parentClassNode['name']);

        $childrenNode = $parentClassNode['children'];
        $eldestChildNode = $parentClassNode['eldestChild'];
        $this->assertInternalType('array', $childrenNode);
        $this->assertCount(3, $childrenNode);
        $firstChildNode = $childrenNode[0];
        $secondChildNode = $childrenNode[1];
        $thirdChildNode = $childrenNode[2];

        $this->assertInternalType('array', $firstChildNode);
        $this->assertCount(2, $firstChildNode);
        $this->assertEquals('Child 1', $firstChildNode['name']);
        $this->assertEquals('7', $firstChildNode['age']);
        $this->assertInternalType('string', $firstChildNode['age']);

        $this->assertInternalType('array', $secondChildNode);
        $this->assertCount(2, $secondChildNode);
        $this->assertEquals('Child 2', $secondChildNode['name']);
        $this->assertEquals('4', $secondChildNode['age']);
        $this->assertInternalType('string', $secondChildNode['age']);

        $this->assertInternalType('array', $thirdChildNode);
        $this->assertCount(2, $thirdChildNode);
        $this->assertEquals('Child 3', $thirdChildNode['name']);
        $this->assertEquals('1', $thirdChildNode['age']);
        $this->assertInternalType('string', $thirdChildNode['age']);

        $this->assertInternalType('array', $eldestChildNode);
        $this->assertCount(2, $eldestChildNode);
        $this->assertEquals('Child 1', $eldestChildNode['name']);
        $this->assertEquals('7', $eldestChildNode['age']);
        $this->assertInternalType('string', $eldestChildNode['age']);
    }

    /**
     * testDoRequest
     */
    public function testDoRequest()
    {
        $wsdlUrl = 'http://wsf.cdyne.com/WeatherWS/Weather.asmx?WSDL';
        $actionName = 'GetCityForecastByZIP';

        $soapClient = new Client($wsdlUrl);
        //$soapClient->setDebug(true);
        //$soapClient->setDebugLogFilePath(__DIR__ . '/../../../../log/debug.log');
        $getForecastByZip = new GetCityForecastByZIP();
        $getForecastByZip->ZIP = '90210';

        $soapResult = $soapClient->GetCityForecastByZIP($getForecastByZip);
        $resultClassmap = array(
            'GetCityForecastByZIPResult' => '\Camcima\Soap\Test\Fixtures\GetCityForecastByZIPResult',
            'ForecastResult' => '\Camcima\Soap\Test\Fixtures\ForecastResult',
            'array|Forecast' => '\Camcima\Soap\Test\Fixtures\ForecastEntry',
            'Temperatures' => '\Camcima\Soap\Test\Fixtures\Temperatures',
            'ProbabilityOfPrecipiation' => '\Camcima\Soap\Test\Fixtures\ProbabilityOfPrecipiation'
        );
        $getCityForecastByZIPResult = $soapClient->mapSoapResult($soapResult, 'GetCityForecastByZIPResult', $resultClassmap);
        /* @var $getCityForecastByZIPResult \Camcima\Soap\Test\Fixtures\GetCityForecastByZIPResult */
        $this->assertInstanceOf('\Camcima\Soap\Test\Fixtures\GetCityForecastByZIPResult', $getCityForecastByZIPResult);
        $this->assertTrue($getCityForecastByZIPResult->Success);
        $this->assertInternalType('string', $getCityForecastByZIPResult->ResponseText);
        $this->assertEquals('City Found', $getCityForecastByZIPResult->ResponseText);
        $this->assertInternalType('string', $getCityForecastByZIPResult->State);
        $this->assertEquals('CA', $getCityForecastByZIPResult->State);
        $this->assertInternalType('string', $getCityForecastByZIPResult->City);
        $this->assertEquals('Beverly Hills', $getCityForecastByZIPResult->City);
        $this->assertInternalType('string', $getCityForecastByZIPResult->WeatherStationCity);
        $forecastResult = $getCityForecastByZIPResult->ForecastResult;
        /* @var $forecastResult \Camcima\Soap\Test\Fixtures\ForecastResult */
        $this->assertInstanceOf('\Camcima\Soap\Test\Fixtures\ForecastResult', $forecastResult);
        $this->assertInternalType('array', $forecastResult->Forecast);
        $forecasts = $forecastResult->Forecast;
        $firstForecast = reset($forecasts);
        /* @var $firstForecast \Camcima\Soap\Test\Fixtures\ForecastEntry */
        $this->assertInstanceOf('\Camcima\Soap\Test\Fixtures\ForecastEntry', $firstForecast);
        $this->assertInstanceOf('\DateTime', $firstForecast->Date);
        $this->assertInternalType('int', $firstForecast->WeatherID);
        $this->assertInternalType('string', $firstForecast->Desciption);
        $temperatures = $firstForecast->Temperatures;
        /* @var $temperatures \Camcima\Soap\Test\Fixtures\Temperatures */
        $this->assertInstanceOf('\Camcima\Soap\Test\Fixtures\Temperatures', $temperatures);
        $this->assertInternalType('string', $temperatures->MorningLow);
        $this->assertInternalType('string', $temperatures->DaytimeHigh);
        $probPrecipitation = $firstForecast->ProbabilityOfPrecipiation;
        /* @var $probPrecipitation \Camcima\Soap\Test\Fixtures\ProbabilityOfPrecipiation */
        $this->assertInstanceOf('\Camcima\Soap\Test\Fixtures\ProbabilityOfPrecipiation', $probPrecipitation);
        $this->assertInternalType('string', $probPrecipitation->Nighttime);
        $this->assertInternalType('string', $probPrecipitation->Daytime);
    }

    /**
     * testDoRequestWithNamespace
     */
    public function testDoRequestWithNamespace()
    {
        $wsdlUrl = 'http://wsf.cdyne.com/WeatherWS/Weather.asmx?WSDL';
        $actionName = 'GetCityForecastByZIP';

        $soapClient = new Client($wsdlUrl);
        //$soapClient->setDebug(true);
        //$soapClient->setDebugLogFilePath(__DIR__ . '/../../../../log/debug.log');
        $getForecastByZip = new GetCityForecastByZIP();
        $getForecastByZip->ZIP = '90210';

        $soapResult = $soapClient->GetCityForecastByZIP($getForecastByZip);
        $resultClassNamespace = '\Camcima\Soap\Test\Fixtures\\';
        $resultClassmap = array(
            'array|Forecast' => '\Camcima\Soap\Test\Fixtures\ForecastEntry',
        );
        $getCityForecastByZIPResult = $soapClient->mapSoapResult($soapResult, 'GetCityForecastByZIPResult', $resultClassmap, $resultClassNamespace);
        /* @var $getCityForecastByZIPResult \Camcima\Soap\Test\Fixtures\GetCityForecastByZIPResult */
        $this->assertInstanceOf('\Camcima\Soap\Test\Fixtures\GetCityForecastByZIPResult', $getCityForecastByZIPResult);
        $this->assertTrue($getCityForecastByZIPResult->Success);
        $this->assertInternalType('string', $getCityForecastByZIPResult->ResponseText);
        $this->assertEquals('City Found', $getCityForecastByZIPResult->ResponseText);
        $this->assertInternalType('string', $getCityForecastByZIPResult->State);
        $this->assertEquals('CA', $getCityForecastByZIPResult->State);
        $this->assertInternalType('string', $getCityForecastByZIPResult->City);
        $this->assertEquals('Beverly Hills', $getCityForecastByZIPResult->City);
        $this->assertInternalType('string', $getCityForecastByZIPResult->WeatherStationCity);
        $forecastResult = $getCityForecastByZIPResult->ForecastResult;
        /* @var $forecastResult \Camcima\Soap\Test\Fixtures\ForecastResult */
        $this->assertInstanceOf('\Camcima\Soap\Test\Fixtures\ForecastResult', $forecastResult);
        $this->assertInternalType('array', $forecastResult->Forecast);
        $forecasts = $forecastResult->Forecast;
        $firstForecast = reset($forecasts);
        /* @var $firstForecast \Camcima\Soap\Test\Fixtures\ForecastEntry */
        $this->assertInstanceOf('\Camcima\Soap\Test\Fixtures\ForecastEntry', $firstForecast);
        $this->assertInstanceOf('\DateTime', $firstForecast->Date);
        $this->assertInternalType('int', $firstForecast->WeatherID);
        $this->assertInternalType('string', $firstForecast->Desciption);
        $temperatures = $firstForecast->Temperatures;
        /* @var $temperatures \Camcima\Soap\Test\Fixtures\Temperatures */
        $this->assertInstanceOf('\Camcima\Soap\Test\Fixtures\Temperatures', $temperatures);
        $this->assertInternalType('string', $temperatures->MorningLow);
        $this->assertInternalType('string', $temperatures->DaytimeHigh);
        $probPrecipitation = $firstForecast->ProbabilityOfPrecipiation;
        /* @var $probPrecipitation \Camcima\Soap\Test\Fixtures\ProbabilityOfPrecipiation */
        $this->assertInstanceOf('\Camcima\Soap\Test\Fixtures\ProbabilityOfPrecipiation', $probPrecipitation);
        $this->assertInternalType('string', $probPrecipitation->Nighttime);
        $this->assertInternalType('string', $probPrecipitation->Daytime);
    }
}
