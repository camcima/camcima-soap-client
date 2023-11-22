<?php

namespace Camcima\Soap;

use DateTime;
use RuntimeException;
use ReflectionException;
use Camcima\Exception\ConnectionErrorException;
use Camcima\Exception\InvalidParameterException;
use Camcima\Exception\InvalidSoapOptionException;
use Camcima\Exception\InvalidClassMappingException;
use Camcima\Exception\MissingClassMappingException;

/**
 * Soap Client
 *
 * @author Carlos Cima
 */
class Client extends \SoapClient
{
    /**
     * Default Values
     */
    const DEFAULT_USER_AGENT = 'CamcimaSoapClient/1.0';
    const DEFAULT_CONTENT_TYPE = 'text/xml; charset=UTF-8';
    const DEFAULT_PROXY_TYPE = CURLPROXY_HTTP;
    const DEFAULT_PROXY_HOST = 'localhost';
    const DEFAULT_PROXY_PORT = 8888;

    /**
     * Cookies
     * 
     * @var array
     */
    protected $cookies = array();
	
    /**
     * User Agent
     * 
     * @var string
     */
    protected $userAgent;
    
    /**
     * Content Type
     * 
     * @var string
     */
    protected $contentType;

    /**
     * cURL Options
     * 
     * @var array 
     */
    protected $curlOptions = array();

    /**
     * Proxy Type
     *
     * @var int
     */
    protected $proxyType;

    /**
     * Proxy Host
     * 
     * @var string 
     */
    protected $proxyHost;

    /**
     * Proxy Port
     * 
     * @var int 
     */
    protected $proxyPort = self::DEFAULT_PROXY_PORT;

    /**
     * Proxy User
     *
     * @var string|int
     */
    protected $proxyUser;

    /**
     * Proxy Password
     *
     * @var string|int
     */
    protected $proxyPassword;

    /**
     * Lowercase first character of the root element name
     * 
     * @var boolean 
     */
    protected $lowerCaseFirst;

    /**
     * Keep empty object properties when building the request parameters
     *  
     * @var boolean 
     */
    protected $keepNullProperties = true;

    /**
     * Debug Mode
     * 
     * @var boolean 
     */
    protected $debug = false;

    /**
     * Debug Log File Path
     * 
     * @var string 
     */
    protected $debugLogFilePath;

    /**
     * Communication Log of Last Request
     * 
     * @var string 
     */
    protected $communicationLog;

    /**
     * Original SoapClient Options
     * 
     * @var array 
     */
    protected $soapOptions = array();

    /**
     * Client constructor.
     * @param $wsdl
     * @param array $options
     * @param bool $sslVerifyPeer
     * @param bool $debugMode
     * @param bool $keepNullProperties
     */
    function __construct($wsdl, array $options = array(), $sslVerifyPeer = true, $debugMode = false, $keepNullProperties = true)
    {
        if ($sslVerifyPeer === false) {
            $stream_context = stream_context_create(array(
                'ssl' => array(
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true
                    )
                ));

            $options['stream_context'] = $stream_context;
        }

        $this->setSoapOptions($options);
        $this->setCurlOptions(array());
        $this->setLowerCaseFirst(false);
        $this->setKeepNullProperties($keepNullProperties);
        $this->setDebug($debugMode);

        parent::__construct($wsdl, $this->getSoapOptions());
    }

    /**
     * @param array $options
     */
    private function setSoapOptions($options = array())
    {
        $this->soapOptions = $options;
    }

    /**
     * @param $option
     * @return bool
     */
    private function hasSoapOption($option)
    {
        return array_key_exists($option, $this->soapOptions);
    }

    /**
     * @param null $option
     * @return string|int
     * @throws InvalidSoapOptionException
     */
    private function getSoapOptions($option = null)
    {
        if(!is_null($option)){
            if($this->hasSoapOption($option)) {
                return $this->soapOptions[ $option ];
            } else {
                throw new InvalidSoapOptionException(sprintf('Soap option \'%s\' invalid.', $option));
            }
        }

        return $this->soapOptions;
    }

    /**
     * @return string|null
     * @throws InvalidSoapOptionException
     */
    private function getSoapUserPasswordString()
    {
        if ($this->hasSoapOption('login') && $this->hasSoapOption('password')) {
            $login = $this->getSoapOptions('login');
            $password = $this->getSoapOptions('password');

            // return login and password in soap format
            return sprintf('%s:%s', $login, $password);
        }

        return null;
    }
	
    /**
     * {@inheritDoc}
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0) : ?string
    {
        $userAgent = $this->getUserAgent();
        $contentType = $this->getContentType();

        $headers = array(
            'Connection: Close',
            'User-Agent: ' . $userAgent,
            'Content-Type: ' . $contentType,
            'SOAPAction: "' . $action . '"',
            'Expect:'
        );

        $soapRequest = is_object($request) ?
            $this->getSoapVariables($request, $this->getLowerCaseFirst(), $this->getKeepNullProperties()) :
            $request;

        $curlOptions = $this->getCurlOptions();

        $curlOptions[CURLOPT_POSTFIELDS] = $soapRequest;
        $curlOptions[CURLOPT_HTTPHEADER] = $headers;
        $curlOptions[CURLINFO_HEADER_OUT] = true;
        $curlOptions[CURLOPT_COOKIE] = $this->parseCookies();

        if ($this->hasSoapOption('login') && $this->hasSoapOption('password')) {
            $curlOptions[CURLOPT_USERPWD] = $this->getSoapUserPasswordString();
        }

        $ch = curl_init($location);
        curl_setopt_array($ch, $curlOptions);
        $requestDateTime = new DateTime();
        try {
            $response = curl_exec($ch);
        } catch (\Exception $e) {
            throw new ConnectionErrorException('Soap Connection Error: ' . $e->getMessage(), $e->getCode(), $e);
        }
        if (curl_errno($ch)) {
            throw new ConnectionErrorException('Soap Connection Error: ' . curl_error($ch), curl_errno($ch));
        }
        if ($response === false) {
            throw new ConnectionErrorException('Soap Connection Error: Empty Response');
        }
        $requestMessage = curl_getinfo($ch, CURLINFO_HEADER_OUT) . $soapRequest;
        $parsedResponse = $this->parseCurlResponse($response);

        if ($this->hasEnabledDebugMode()) {
            $this->logCurlMessage($requestMessage, $requestDateTime);
            $this->logCurlMessage($response, new DateTime());
        }

        $this->setCommunicationLog($requestMessage . "\n\n" . $response);

        $body = $parsedResponse['body'];

        curl_close($ch);

        return $body;
    }

    /**
     * Maps Result XML Elements to Classes
     *
     * @param \stdClass $soapResult
     * @param $rootClassName
     * @param array $resultClassMap
     * @param string $resultClassNamespace
     * @param bool $skipRootObject
     *
     * @return object
     *
     * @throws InvalidClassMappingException
     * @throws InvalidParameterException
     * @throws MissingClassMappingException
     * @throws \ReflectionException
     */
    public function mapSoapResult($soapResult, $rootClassName, array $resultClassMap = array(), $resultClassNamespace = '', $skipRootObject = false)
    {
        if (!is_object($soapResult)) {
            throw new InvalidParameterException('Soap Result is not an object');
        }

        if ($skipRootObject) {
            $objVarsNames = array_keys(get_object_vars($soapResult));
            $rootClassName = reset($objVarsNames);
            $soapResultObj = $this->mapObject($soapResult->$rootClassName, $rootClassName, $resultClassMap, $resultClassNamespace);
        } else {
            $soapResultObj = $this->mapObject($soapResult, $rootClassName, $resultClassMap, $resultClassNamespace);
        }

        return $soapResultObj;
    }

	/**
	 * {@inheritDoc}
	 */
	public function __setCookie( $name, $value = null ) : void
	{
		$this->cookies[ $name ] = $value;
	}
	
	/**
	 * Parse the cookies into a valid HTTP Cookie header value
	 * 
	 * @return string
	 */
	protected function parseCookies ()
	{
		$cookie = '';
		
		foreach( $this->cookies as $name => $value )
			$cookie .= $name . '=' . $value . '; ';
				
		return rtrim( $cookie, ';' );
	}
	
    /**
     * Set cURL Options
     * 
     * @param array $curlOptions
     * @return \Camcima\Soap\Client
     */
    public function setCurlOptions(array $curlOptions)
    {
        $this->curlOptions = $curlOptions;

        return $this;
    }

    /**
     * Set User Agent
     * 
     * @param string $userAgent
     * @return \Camcima\Soap\Client
     */
    public function setUserAgent($userAgent = self::DEFAULT_USER_AGENT)
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserAgent()
    {
        $userAgent = $this->userAgent ? $this->userAgent : self::DEFAULT_USER_AGENT;

        return $userAgent;
    }
    
    /**
     * Set Content Type
     * 
     * @param string $contentType
     * @return \Camcima\Soap\Client
     */
    public function setContentType($contentType = self::DEFAULT_CONTENT_TYPE)
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        $contentType = $this->contentType ? $this->contentType : self::DEFAULT_CONTENT_TYPE;

        return $contentType;
    }

    /**
     * Lowercase first character of the root element name
     * 
     * Defaults to false
     * 
     * @param boolean $lowerCaseFirst
     * @return \Camcima\Soap\Client
     */
    public function setLowerCaseFirst($lowerCaseFirst)
    {
        $this->lowerCaseFirst = $lowerCaseFirst;

        return $this;
    }

    /**
     * @return bool
     */
    public function getLowerCaseFirst()
    {
        return $this->lowerCaseFirst;
    }

    /**
     * Keep null object properties when building the request parameters
     * 
     * Defaults to true
     * 
     * @param boolean $keepNullProperties
     * @return \Camcima\Soap\Client
     */
    public function setKeepNullProperties($keepNullProperties)
    {
        $this->keepNullProperties = $keepNullProperties;

        return $this;
    }

    /**
     * @return bool
     */
    public function getKeepNullProperties()
    {
        return $this->keepNullProperties;
    }

    /**
     * Set Debug Mode
     * 
     * @param boolean $debug
     * @return \Camcima\Soap\Client
     */
    public function setDebug($debug = false)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * @return bool
     */
    private function hasEnabledDebugMode()
    {
        return $this->debug;
    }

    /**
     * Set Debug Log File Path
     * 
     * @param string $debugLogFilePath
     * @return \Camcima\Soap\Client
     */
    public function setDebugLogFilePath($debugLogFilePath)
    {
        $this->debugLogFilePath = $debugLogFilePath;

        return $this;
    }

    /**
     * @return string
     */
    private function getDebugLogFilePath()
    {
        return $this->debugLogFilePath;
    }

    /**
     * Use Proxy
     *
     * @param string $host
     * @param int $port
     * @param int $type
     * @return \Camcima\Soap\Client
     */
    public function useProxy($host = self::DEFAULT_PROXY_HOST, $port = self::DEFAULT_PROXY_PORT, $type = self::DEFAULT_PROXY_TYPE)
    {
        $this->setProxyType($type);
        $this->setProxyHost($host);
        $this->setProxyPort($port);

        return $this;
    }

    /**
     * Set proxy auth data
     *
     * @param $user
     * @param $password
     * @return $this
     */
    public function setProxyAuth($user, $password)
    {
        $this->setProxyUser($user);
        $this->setProxyPassword($password);

        return $this;
    }

    /**
     * @param $host
     */
    private function setProxyHost($host)
    {
        $this->proxyHost = $host;
    }

    /**
     * @return string
     */
    private function getProxyHost()
    {
        return $this->proxyHost;
    }

    /**
     * @param $type
     */
    private function setProxyType($type)
    {
        $this->proxyType = $type;
    }

    /**
     * @return string
     */
    private function getProxyType()
    {
        return $this->proxyType;
    }

    /**
     * @param $port
     */
    private function setProxyPort($port)
    {
        $this->proxyPort = $port;
    }

    /**
     * @return string
     */
    private function getProxyPort()
    {
        return $this->proxyPort;
    }

    /**
     * @param string|int $user
     */
    private function setProxyUser($user)
    {
        $this->proxyUser = $user;
    }

    /**
     * @param string|int $user
     */
    private function setProxyPassword($password)
    {
        $this->proxyPassword = $password;
    }

    /**
     * @return string|int
     */
    private function getProxyUser()
    {
        return $this->proxyUser;
    }

    /**
     * @return string|int
     */
    private function getProxyPassword()
    {
        return $this->proxyPassword;
    }

    /**
     * @return string
     */
    private function getProxyUserPwd()
    {
        return $this->getProxyUser() . ':' . $this->getProxyPassword();
    }

    /**
     * @return bool
     */
    private function hasProxyConfigured()
    {
        return (strlen($this->proxyHost) > 0);
    }

    /**
     * Merge Curl Options
     * 
     * @return array
     */
    public function getCurlOptions()
    {
        $mandatoryOptions = array(
            CURLOPT_POST => true,
            CURLOPT_HEADER => true
        );

        $defaultOptions = array(
            CURLOPT_VERBOSE => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        );

        $curlOptions = ($mandatoryOptions + $this->curlOptions + $defaultOptions);

        if ($this->hasProxyConfigured()) {
            $curlOptions[CURLOPT_PROXYTYPE] = $this->getProxyType();
            $curlOptions[CURLOPT_PROXY] = $this->getProxyHost();
            $curlOptions[CURLOPT_PROXYPORT] = $this->getProxyPort();
            $curlOptions[CURLOPT_PROXYUSERPWD] = $this->getProxyUserPwd();
        }

        return $curlOptions;
    }

    /**
     * Get SOAP Request Variables
     *
     * Prepares request parameters to be
     * sent in the SOAP Request Body.
     *
     * @param object $requestObject
     * @param boolean $lowerCaseFirst Lowercase first character of the root element name
     * @param boolean $keepNullProperties Keep null object properties when building the request parameters
     * @throws \Camcima\Exception\InvalidParameterException
     * @return array
     */
    public function getSoapVariables($requestObject, $lowerCaseFirst = false, $keepNullProperties = true)
    {
        if (!is_object($requestObject)) {
            throw new InvalidParameterException('Parameter requestObject is not an object');
        }
        $objectName = $this->getClassNameWithoutNamespaces($requestObject);
        if ($lowerCaseFirst) {
            $objectName = lcfirst($objectName);
        }
        $stdClass = new \stdClass();
        $stdClass->$objectName = $requestObject;

        return $this->objectToArray($stdClass, $keepNullProperties);
    }

    /**
     * Get Communication Log of Last Request
     * 
     * @return string
     */
    public function getCommunicationLog()
    {
        return $this->communicationLog;
    }

    /**
     * @param string $log
     */
    private function setCommunicationLog($log)
    {
        $this->communicationLog = $log;
    }

    /**
     * Get Class Without Namespace Information
     * 
     * @param mixed $object
     * @return string
     */
    protected function getClassNameWithoutNamespaces($object)
    {
        $class = explode('\\', get_class($object));

        return end($class);
    }

    /**
     * Convert Object to Array
     * 
     * This method omits null value properties
     * 
     * @param mixed $obj
     * @param boolean $keepNullProperties Keep null object properties when building the request parameters
     * @return array
     */
    protected function objectToArray($obj, $keepNullProperties = true)
    {
        $arr = array();
        $arrObj = is_object($obj) ? get_object_vars($obj) : $obj;
        foreach ($arrObj as $key => $val) {
            $val = (is_array($val) || is_object($val)) ? $this->objectToArray($val, $keepNullProperties) : $val;
            if ($keepNullProperties || $val !== null) {
                $val = ($val === null) ? $val = '' : $val;
                $arr[$key] = is_scalar($val) ? ((string) $val) : $val;
            }
        }
        return $arr;
    }

    /**
     * Map Remote SOAP Objects(stdClass) to local classes
     *
     * @param mixed $obj Remote SOAP Object
     * @param string $className Root (or current) class name
     * @param array $classMap Class Mapping
     * @param string $classNamespace Namespace where the local classes are located
     * @throws \Camcima\Exception\MissingClassMappingException
     * @throws \Camcima\Exception\InvalidClassMappingException
     * @throws \ReflectionException
     * @return \Camcima\Soap\mappedClassName
     */
    protected function mapObject($obj, $className, $classMap = array(), $classNamespace = '')
    {
        // If object is array of simple types just return inner array stored in item attribute
        if (is_object($obj) && array_key_exists($className, $classMap) && $classMap[$className] === 'array') {
            $payload = json_decode(json_encode($obj), true);
            $result = reset($payload);
            return is_array($result) ? $result : array($result);
        } elseif (is_object($obj)) {

            // Check if there is a mapping.
            if (isset($classMap[$className])) {
                $mappedClassName = $classMap[$className];
            } else {
                if ($classNamespace) {
                    $mappedClassName = str_replace('\\\\', '\\', $classNamespace . '\\' . $className);
                } else {
                    throw new MissingClassMappingException('Missing mapping for element "' . $className . '"');
                }
            }

            // Check if local class exists.
            if (!class_exists($mappedClassName)) {
                throw new InvalidClassMappingException('Class not found: "' . $mappedClassName . '"');
            }
            // Get class properties and methods.
            $objProperties = array_keys(get_class_vars($mappedClassName));
            $objMethods = get_class_methods($mappedClassName);

            // Instantiate new mapped object.
            $objInstance = new $mappedClassName();

            // Map remote object to local object.
            $arrObj = get_object_vars($obj);
            foreach ($arrObj as $key => $val) {
                if (!is_null($val)) {
                    $useSetter = false;
                    if (in_array('set' . $key, $objMethods)) {
                        $useSetter = true;
                    } elseif (!in_array($key, $objProperties)) {
                        throw new InvalidClassMappingException('Property "' . $mappedClassName . '::' . $key . '" doesn\'t exist');
                    }

                    // If it's not scalar, recursive call the mapping function
                    if (is_array($val) || is_object($val)) {
                        $val = $this->mapObject($val, $key, $classMap, $classNamespace);
                    }

                    // If there is a setter, use it. If not, set the property directly.
                    if ($useSetter) {
                        $setterName = 'set' . $key;

                        // Check if parameter is \DateTime
                        $reflection = new ReflectionMethod($mappedClassName, $setterName);
                        $params = $reflection->getParameters();
                        if (count($params) != 1) {
                            throw new InvalidClassMappingException('Wrong Argument Count in Setter for property ' . $key);
                        }
                        $param = reset($params);
                        /* @var $param \ReflectionParameter */

                        // Get the parameter class (if type-hinted)
                        try {
                            $paramClass = $param->getClass();
                        } catch (ReflectionException $e) {
                            throw new ReflectionException('Invalid type hint for method "' . $setterName . '"');
                        }
                        if ($paramClass) {
                            $paramClassName = $paramClass->getNamespaceName() . '\\' . $param->getClass()->getName();
                            // If setter parameter is typehinted, cast the value before calling the method
                            if ($paramClassName == '\DateTime') {
                                $val = new DateTime($val);
                            }
                        }

                        $objInstance->$setterName($val);
                    } else {
                        $objInstance->$key = $val;
                    }
                }
            }
            return $objInstance;
        } elseif (is_array($obj)) {
            // Value is array.
            $returnArray = array();
            // If array mapping exists, map array elements.
            if (array_key_exists('array|' . $className, $classMap)) {
                $className = 'array|' . $className;
                foreach ($obj as $key => $val) {
                    $returnArray[$key] = $this->mapObject($val, $className, $classMap, $classNamespace);
                }
            } else {
                // If array mapping doesn't exist, return the array.
                $returnArray = $obj;
            }
            return $returnArray;
        } else {
            // Value is scalar. Just return it.
            return $obj;
        }
    }

    /**
     * Parse cURL response into header and body
     * 
     * Inspired by shuber cURL wrapper.
     * 
     * @param string $response
     * @return array
     */
    protected function parseCurlResponse($response)
    {
        $result = explode("\r\n\r\n", $response);

        // split headers / body parts
        return array(
            'header' => $result[0],
            'body' => $result[1],
        );
    }

    /**
     * Log cURL Debug Message
     * 
     * @param string $message
     * @param \DateTime $messageTimestamp
     * @throws \RuntimeException
     */
    protected function logCurlMessage($message, DateTime $messageTimestamp)
    {
        if (!$debugFilePath = $this->getDebugLogFilePath()) {
            throw new RuntimeException('Debug log file path not defined.');
        }
        $logMessage = '[' . $messageTimestamp->format('Y-m-d H:i:s') . "] ----------------------------------------------------------\n" . $message . "\n\n";
        $logHandle = fopen($debugFilePath, 'a+');
        fwrite($logHandle, $logMessage);
        fclose($logHandle);
    }
}
