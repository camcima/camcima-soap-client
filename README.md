# camcima PHP SOAP Client #

## Overview ##
This is my attempt to address the shortcommings of the native PHP SOAP Client implementation (`\SoapClient`).

## Usage ##

### Instantiate ###

```php
<?php
$soapClient = new \Camcima\Soap\Client($wsdl, $options);
```

### cURL Options ###

This wrapper uses cURL to issue the HTTP requests. It's possible to customize the SOAP request using cURL options.

```php
<?php
$curlOptions = array(
    CURLOPT_CRLF => true,
    CURLOPT_SSL_VERIFYPEER => true
);
$soapClient = new \Camcima\Soap\Client($wsdl, $options);
$soapClient->setCurlOptions($curlOptionsFixture);
```

There are a few cURL options, however, that can't be overwritten as they are essential for the wrapper:

```php
<?php
// Mandatory cURL Options
CURLOPT_POST => true
CURLOPT_HEADER => true
```

To get the cURL options currently in use:

```php
<?php
$curlOptions = $soapClient->getCurlOptions();
```

### Use Proxy ###

If you need to proxy the requests (e.g. debugging), you can use this method to set the proxy host and port:

```php
<?php
$soapClient->useProxy('proxy.local', 8080);
```

The default hostname and port for this methods are `localhost` and `8888`, which is the default binding for Fiddler Web Debugging Proxy ([http://fiddler2.com/](http://fiddler2.com/)).

### User Agent ###

It's possible to customize the User Agent used by the client:

```php
<?php
$soapClient->setUserAgent('MyUserAgent/1.0');
```

### Lower Cased Initial Character ###

When I was developing an integration with a .NET based web-service, I've noticed the name of the root element of the SOAP request payload always had the first letter lowercased. This didn't hold true for the other inner elements which had always the first letter uppercased, common for class names. I don't know if this is the norm for .NET web-services but, in any case, I've implemented an option that handles this.

```php
<?php
$soapClient->setLowerCaseFirst(true);
```

The default for this setting is `false`.

### Keep Null Properties ###

When you pass an object as a SOAP parameter, some of its properties could be null (or not set). The default behavior for this client is to omit these null properties when sending the request. If you need to send all properties, even when null, use this:

```php
<?php
$soapClient->setKeepNullProperties(true);
```

The default for this setting is `true`.

### Debug ###

In order to debug requests and responses, you need to set the debug mode to true and the debug file name and path.

```php
<?php
$soapClient->setDebug(true);
$soapClient->setDebugLogFilePath(__DIR__ . '/../../../../log/debug.log');
```

### Last Request Communication Log ###

You can get the HTTP communication log (request and response) from the last request.

```php
<?php
$soapClient->getCommunicationLog();
```


### Result Class Mapping ###

PHP native implementation of SOAP client has the ability to map the SOAP return to local classes. Unfortunately it didn't work for me as expected. So I've implemented my own version of result class mapping.

It works in two different flavors:

#### Using Classmap ####

You have to build an associative array with the result elements as keys, and the corresponding local classes as values.

```php
<?php
$soapClient = new Client($wsdlUrl);
$soapResult = $soapClient->GetCityForecastByZIP($getForecastByZip);
$resultClassmap = array(
    'GetCityForecastByZIPResult' => '\Camcima\Soap\Test\Fixtures\GetCityForecastByZIPResult',
    'ForecastResult' => '\Camcima\Soap\Test\Fixtures\ForecastResult',
    'array|Forecast' => '\Camcima\Soap\Test\Fixtures\ForecastEntry',
    'Temperatures' => '\Camcima\Soap\Test\Fixtures\Temperatures',
    'ProbabilityOfPrecipiation' => '\Camcima\Soap\Test\Fixtures\ProbabilityOfPrecipiation'
);
$getCityForecastByZIPResult = $soapClient->mapSoapResult($soapResult, 'GetCityForecastByZIPResult', $resultClassmap);
```

The native SOAP client returns all objects as Standard Classes (`StdClass`) and my mapping function "casts" them to the mapped local classes. This functions is based on the property name that holds the standard class object. This works pretty well in most scenarios, but when there is an array of objects, it needs a special config:

```php
<?php
$mapping = array(
    'array|Forecast' => '\Camcima\Soap\Test\Fixtures'
);
```

The name of the property which holds the array serves as the marker for the mapping. You also need to prefix this element name with `array` and use pipe (`|`) to separate them.

#### Using Namespace ####

If all your result classes reside in the same namespace, there is no need to map them individually. You can tell the mapper the namespace your classes live and it will automatically determine the mapping by matching the SOAP result names with the local class names.

```php
<?php
$resultClassNamespace = '\MyProject\SOAP\Result\\';
```

## Improvements ##

I plan to include new features according to my needs. If you need a special feature you have two options:

- Develop it yourself and send me a pull request. I promise to merge it ASAP.

- Create an issue and wait for me to develop it. This can take some time, as I'm usually quite busy.


