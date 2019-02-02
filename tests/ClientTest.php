<?php

namespace Camcima\Tests;

use Camcima\Soap\Client;

use Camcima\Tests\Fixtures\ChildClass;
use Camcima\Tests\Fixtures\ParentClass;
use Camcima\Tests\Fixtures\GetCityForecastByZIP;

/**
 * SoapClientTest
 *
 * @author Carlos Cima <ccima@rocket-internet.com>
 */
class ClientTest extends TestCase
{
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
     * @expectedException   \Camcima\Exception\InvalidParameterException
     * @expectedExceptionMessageRegExp /Parameter requestObject is not an object/
     */
    public function testGetSoapVariablesException()
    {
        $this->soapClient->getSoapVariables(null);
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
        $this->markTestIncomplete('Service WeatherWS Offline.');

        $wsdlUrl = 'http://wsf.cdyne.com/WeatherWS/Weather.asmx?WSDL';
        $actionName = 'GetCityForecastByZIP';

        $soapClient = new Client($wsdlUrl);
        //$soapClient->setDebug(true);
        //$soapClient->setDebugLogFilePath(__DIR__ . '/../../../../log/debug.log');
        $getForecastByZip = new GetCityForecastByZIP();
        $getForecastByZip->ZIP = '90210';

        $soapResult = $soapClient->GetCityForecastByZIP($getForecastByZip);
        $resultClassmap = array(
            'GetCityForecastByZIPResult' => '\Camcima\Tests\Fixtures\GetCityForecastByZIPResult',
            'ForecastResult' => '\Camcima\Tests\Fixtures\ForecastResult',
            'array|Forecast' => '\Camcima\Tests\Fixtures\ForecastEntry',
            'Temperatures' => '\Camcima\Tests\Fixtures\Temperatures',
            'ProbabilityOfPrecipiation' => '\Camcima\Tests\Fixtures\ProbabilityOfPrecipiation'
        );
        $getCityForecastByZIPResult = $soapClient->mapSoapResult($soapResult, 'GetCityForecastByZIPResult', $resultClassmap, '', true);
        /* @var $getCityForecastByZIPResult \Camcima\Tests\Fixtures\GetCityForecastByZIPResult */
        $this->assertInstanceOf('\Camcima\Tests\Fixtures\GetCityForecastByZIPResult', $getCityForecastByZIPResult);
        $this->assertTrue($getCityForecastByZIPResult->Success);
        $this->assertInternalType('string', $getCityForecastByZIPResult->ResponseText);
        $this->assertEquals('City Found', $getCityForecastByZIPResult->ResponseText);
        $this->assertInternalType('string', $getCityForecastByZIPResult->State);
        $this->assertEquals('CA', $getCityForecastByZIPResult->State);
        $this->assertInternalType('string', $getCityForecastByZIPResult->City);
        $this->assertEquals('Beverly Hills', $getCityForecastByZIPResult->City);
        $this->assertInternalType('string', $getCityForecastByZIPResult->WeatherStationCity);
        $forecastResult = $getCityForecastByZIPResult->ForecastResult;
        /* @var $forecastResult \Camcima\Tests\Fixtures\ForecastResult */
        $this->assertInstanceOf('\Camcima\Tests\Fixtures\ForecastResult', $forecastResult);
        $this->assertInternalType('array', $forecastResult->Forecast);
        $forecasts = $forecastResult->Forecast;
        $firstForecast = reset($forecasts);
        /* @var $firstForecast \Camcima\Tests\Fixtures\ForecastEntry */
        $this->assertInstanceOf('\Camcima\Tests\Fixtures\ForecastEntry', $firstForecast);
        $this->assertInstanceOf('\DateTime', $firstForecast->Date);
        $this->assertInternalType('int', $firstForecast->WeatherID);
        $this->assertInternalType('string', $firstForecast->Desciption);
        $temperatures = $firstForecast->Temperatures;
        /* @var $temperatures \Camcima\Tests\Fixtures\Temperatures */
        $this->assertInstanceOf('\Camcima\Tests\Fixtures\Temperatures', $temperatures);
        $this->assertInternalType('string', $temperatures->MorningLow);
        $this->assertInternalType('string', $temperatures->DaytimeHigh);
        $probPrecipitation = $firstForecast->ProbabilityOfPrecipiation;
        /* @var $probPrecipitation \Camcima\Tests\Fixtures\ProbabilityOfPrecipiation */
        $this->assertInstanceOf('\Camcima\Tests\Fixtures\ProbabilityOfPrecipiation', $probPrecipitation);
        $this->assertInternalType('string', $probPrecipitation->Nighttime);
        $this->assertInternalType('string', $probPrecipitation->Daytime);
    }

    /**
     * testDoRequestWithNamespace
     */
    public function testDoRequestWithNamespace()
    {
        $this->markTestIncomplete('Service WeatherWS Offline.');

        $wsdlUrl = 'http://wsf.cdyne.com/WeatherWS/Weather.asmx?WSDL';
        $actionName = 'GetCityForecastByZIP';

        $soapClient = new Client($wsdlUrl);
        //$soapClient->setDebug(true);
        //$soapClient->setDebugLogFilePath(__DIR__ . '/../../../../log/debug.log');
        $getForecastByZip = new GetCityForecastByZIP();
        $getForecastByZip->ZIP = '90210';

        $soapResult = $soapClient->GetCityForecastByZIP($getForecastByZip);
        $resultClassNamespace = '\Camcima\Tests\Fixtures\\';
        $resultClassmap = array(
            'array|Forecast' => '\Camcima\Tests\Fixtures\ForecastEntry',
        );
        $getCityForecastByZIPResult = $soapClient->mapSoapResult($soapResult, 'GetCityForecastByZIPResult', $resultClassmap, $resultClassNamespace, true);
        /* @var $getCityForecastByZIPResult \Camcima\Tests\Fixtures\GetCityForecastByZIPResult */
        $this->assertInstanceOf('\Camcima\Tests\Fixtures\GetCityForecastByZIPResult', $getCityForecastByZIPResult);
        $this->assertTrue($getCityForecastByZIPResult->Success);
        $this->assertInternalType('string', $getCityForecastByZIPResult->ResponseText);
        $this->assertEquals('City Found', $getCityForecastByZIPResult->ResponseText);
        $this->assertInternalType('string', $getCityForecastByZIPResult->State);
        $this->assertEquals('CA', $getCityForecastByZIPResult->State);
        $this->assertInternalType('string', $getCityForecastByZIPResult->City);
        $this->assertEquals('Beverly Hills', $getCityForecastByZIPResult->City);
        $this->assertInternalType('string', $getCityForecastByZIPResult->WeatherStationCity);
        $forecastResult = $getCityForecastByZIPResult->ForecastResult;
        /* @var $forecastResult \Camcima\Tests\Fixtures\ForecastResult */
        $this->assertInstanceOf('\Camcima\Tests\Fixtures\ForecastResult', $forecastResult);
        $this->assertInternalType('array', $forecastResult->Forecast);
        $forecasts = $forecastResult->Forecast;
        $firstForecast = reset($forecasts);
        /* @var $firstForecast \Camcima\Tests\Fixtures\ForecastEntry */
        $this->assertInstanceOf('\Camcima\Tests\Fixtures\ForecastEntry', $firstForecast);
        $this->assertInstanceOf('\DateTime', $firstForecast->Date);
        $this->assertInternalType('int', $firstForecast->WeatherID);
        $this->assertInternalType('string', $firstForecast->Desciption);
        $temperatures = $firstForecast->Temperatures;
        /* @var $temperatures \Camcima\Tests\Fixtures\Temperatures */
        $this->assertInstanceOf('\Camcima\Tests\Fixtures\Temperatures', $temperatures);
        $this->assertInternalType('string', $temperatures->MorningLow);
        $this->assertInternalType('string', $temperatures->DaytimeHigh);
        $probPrecipitation = $firstForecast->ProbabilityOfPrecipiation;
        /* @var $probPrecipitation \Camcima\Tests\Fixtures\ProbabilityOfPrecipiation */
        $this->assertInstanceOf('\Camcima\Tests\Fixtures\ProbabilityOfPrecipiation', $probPrecipitation);
        $this->assertInternalType('string', $probPrecipitation->Nighttime);
        $this->assertInternalType('string', $probPrecipitation->Daytime);
    }

    /**
     * testDoRequestWithArrayResponse
     */
    public function testDoRequestWithArrayResponse()
    {
        $this->markTestIncomplete('Service soap-server.pacura Offline.');

        $wsdlUrl = 'http://soap-server.pacura.pl/?wsdl';
        $soapClient = new Client($wsdlUrl);

        $soapResponse = $soapClient->getForecastIcons();
        $icons = $soapClient->mapSoapResult($soapResponse, 'getForecastIconsOut', array('getForecastIconsOut' => 'array'));
        $this->assertInternalType('array', $icons);
        $this->assertArrayHasKey(0, $icons);
        $this->assertInternalType('string', $icons[0]);
        $this->assertEquals('partly-cloudly.png', $icons[0]);
    }

    function testSetUserAgent()
    {
        $userAgent = 'PHPUnit';

        $this->soapClient->setUserAgent($userAgent);

        $this->assertEquals($userAgent, $this->soapClient->getUserAgent());
    }

    function testDefaultUserAgent()
    {
        $this->assertEquals(Client::DEFAULT_USER_AGENT, $this->soapClient->getUserAgent());
    }

    function testSetContentType()
    {
        $contentType = 'PHPUnit';

        $this->soapClient->setContentType($contentType);

        $this->assertEquals($contentType, $this->soapClient->getContentType());
    }

    function testDefaultContentType()
    {
        $this->assertEquals(Client::DEFAULT_CONTENT_TYPE, $this->soapClient->getContentType());
    }

    /**
     *
     */
    function testParseCurlResponse()
    {
        $parseCurlResponse = self::getPrivateMethod('parseCurlResponse');

        $curlResponse = "HTTP/2 200 \r\ndate: Sat, 02 Feb 2019 01:46:57 GMT\r\ncontent-type: text/xml; charset=UTF-8\r\n\r\n</xml>";

        /** @var array $responseParsed */
        $responseParsed = $parseCurlResponse->invoke($this->soapClient, $curlResponse);

        $this->assertInternalType('array', $responseParsed);

        $this->assertArrayHasKey('header', $responseParsed);
        $this->assertArrayHasKey('body', $responseParsed);

        $this->assertNotEmpty($responseParsed['header']);
        $this->assertNotEmpty($responseParsed['body']);

        $this->assertEquals("HTTP/2 200 \r\ndate: Sat, 02 Feb 2019 01:46:57 GMT\r\ncontent-type: text/xml; charset=UTF-8", $responseParsed['header']);
        $this->assertEquals("</xml>", $responseParsed['body']);
    }

    function testgetSoapOption()
    {
        $setSoapOptions = self::getPrivateMethod('setSoapOptions');
        $getSoapOptions = self::getPrivateMethod('getSoapOptions');
        $setSoapOptions->invoke($this->soapClient, array('a' => 'b'));
        $soapOption = $getSoapOptions->invoke($this->soapClient, 'a');

        $this->assertEquals('b', $soapOption);
    }

    function testgetSoapOptions()
    {
        $setSoapOptions = self::getPrivateMethod('setSoapOptions');
        $getSoapOptions = self::getPrivateMethod('getSoapOptions');
        $setSoapOptions->invoke($this->soapClient, array('a' => 'b'));
        $soapOptions = $getSoapOptions->invoke($this->soapClient);

        $this->assertInternalType('array', $soapOptions);
        $this->assertArrayHasKey('a', $soapOptions);
        $this->assertEquals('b', $soapOptions['a']);
    }

    /**
     * @expectedException   \Camcima\Exception\InvalidSoapOptionException
     * @expectedExceptionMessageRegExp /Soap option '\w+' invalid./
     */
    function testGetInvalidSoapOption()
    {
        $getSoapOptions = self::getPrivateMethod('getSoapOptions');
        $getSoapOptions->invoke($this->soapClient, 'abc');
    }

    function testSetCookies()
    {
        $this->soapClient->__setCookie('cookie', 'value');

        $parseCookies = self::getPrivateMethod('parseCookies');
        $cookieString = $parseCookies->invoke($this->soapClient);

        $this->assertEquals('cookie=value; ', $cookieString);
    }

    function testSetCustoUserAgent()
    {
        $this->soapClient->setUserAgent('user-agent');

        $this->assertEquals('user-agent', $this->soapClient->getUserAgent());
    }

    function testGetLowerCaseFirst()
    {
        $this->soapClient->setLowerCaseFirst(true);

        $this->assertTrue($this->soapClient->getLowerCaseFirst());
    }

    function testSetKeepNullProperties()
    {
        $this->soapClient->setKeepNullProperties(false);

        $this->assertFalse($this->soapClient->getKeepNullProperties());
    }

    function testSetDebugMode()
    {
        $this->soapClient->setDebug(true);

        $method = self::getPrivateMethod('hasEnabledDebugMode');
        $hasEnabledDebugMode = $method->invoke($this->soapClient, true);

        $this->assertTrue($hasEnabledDebugMode);
    }

    function testSetDebugLogFilePath()
    {
        $this->soapClient->setDebugLogFilePath('somefile.log');

        $method = self::getPrivateMethod('getDebugLogFilePath');
        $debugLogFilePath = $method->invoke($this->soapClient);

        $this->assertEquals('somefile.log', $debugLogFilePath);
    }

    function testSetProxyAuth()
    {
        $this->soapClient->setProxyAuth('user', 'password');

        $method = self::getPrivateMethod('getProxyUserPwd');
        $proxyAuth = $method->invoke($this->soapClient);

        $this->assertEquals('user:password', $proxyAuth);
    }

    function testSoapUserPasswordString()
    {
        $setSoapOptions = self::getPrivateMethod('setSoapOptions');
        $getSoapUserPasswordString = self::getPrivateMethod('getSoapUserPasswordString');
        $setSoapOptions->invoke($this->soapClient, array('login' => 'user', 'password' => 'password'));


        $string = $getSoapUserPasswordString->invoke($this->soapClient);

        $this->assertEquals('user:password', $string);
    }

    function testSoapUserPasswordStringIfIsNull()
    {
        $getSoapUserPasswordString = self::getPrivateMethod('getSoapUserPasswordString');
        $string = $getSoapUserPasswordString->invoke($this->soapClient);

        $this->assertNull($string);
    }

    function testSetCommunicationLog()
    {
        $setCommunicationLog = self::getPrivateMethod('setCommunicationLog');
        $setCommunicationLog->invoke($this->soapClient, 'some string');

        $log = $this->soapClient->getCommunicationLog();

        $this->assertEquals('some string', $log);
    }
}
