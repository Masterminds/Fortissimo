<?php
/**
 * @file
 * Fortissimo::RegistryReader class.
 */
namespace Fortissimo;

/**
 * Provides a data access layer to the registry.
 *
 * The Fortissimo::Registry provides a fluent interface for writing registry
 * files. The RegistryReader provides an interface for accessing registry
 * information. The two are loosely coupled so that registries can be built
 * by another tool, yet still read by the RegistryReader.
 *
 * For legacy reasons, and for performance, the core registry is a well-defined
 * array structure.
 */
class RegistryReader {

  protected $config;

  /**
   * Construct a new configuration object.
   *
   * This loads a registry into the reader.
   *
   * @param mixed $registry
   *   The registry.
   *
   */
  public function __construct($registry = array()) {
    if (is_array($registry)) {
      $this->config = $registry;
    }
    else {
      $this->config = $registry->configuration();
    }
  }

  /**
   * Get an array of additional paths to be added to the include path.
   *
   * Fortissimo uses an autoloader to load classes into the engine. This
   * loader can use additional paths. To pass additional paths to Fortissimo,
   * add them using the <code>include</code> element in the commands.xml file.
   *
   * @return array
   *  An array of include paths as defined in the command configuration
   *  (commands.xml).
   */
  public function getIncludePaths() {
    return $this->config[Registry::PATHS];
  }

  public function getRequestMapper($default = '\Fortissimo\RequestMapper') {
    if (isset($this->config[Registry::REQUEST_MAPPER])) {
      return $this->config[Registry::REQUEST_MAPPER];
    }
    return $default;
  }

  /**
   * Check whether the named request is known to the system.
   *
   * @param string $requestName
   *  The name of the request to check.
   * @param boolean $allowInternalRequests
   *  If this is TRUE, this will allow internal request names. Otherwise, it will flag internal
   *  requests as having illegal names.
   * @return boolean
   *  TRUE if this is a known request, false otherwise.
   */
  public function hasRequest($requestName, $allowInternalRequests = FALSE){
    if (!self::isLegalRequestName($requestName, $allowInternalRequests))  {
      throw new \Fortissimo\Exception('Illegal request name.');
    }
    return isset($this->config[Registry::REQUESTS][$requestName]);
  }

  /**
   * Validate the request name.
   *
   * A request name can be any combination of one or more alphanumeric characters,
   * together with dashes (-) and underscores (_).
   *
   * @param string $requestName
   *  The name of the request. This value will be validated according to the rules
   *  explained above.
   * @param boolean $allowInternalRequests
   *  If this is set to TRUE, the checking will be relaxed to allow at-requests.
   * @return boolean
   *  TRUE if the name is legal, FALSE otherwise.
   */
  public static function isLegalRequestName($requestName, $allowInternalRequests = FALSE) {
    $regex = $allowInternalRequests ? '/^@?[_a-zA-Z0-9\\-]+$/' : '/^[_a-zA-Z0-9\\-]+$/';

    return preg_match($regex, $requestName) == 1;
  }

  /**
   * Get all loggers.
   *
   * This will load all of the loggers from the command configuration
   * (typically commands.xml) and return them in an associative array of
   * the form array('name' => object), where object is a FortissimoLogger
   * of some sort.
   *
   * @return array
   *  An associative array of name => logger pairs.
   * @see Fortissimo::Logger
   */
  public function getLoggers() {
    $loggers = $this->getFacility(Registry::LOGGERS);

    foreach ($loggers as $logger) $logger->init();

    return $loggers;
  }

  /**
   * Get all caches.
   *
   * This will load all of the caches from the command configuration
   * (typically commands.php) and return them in an associative array of
   * the form array('name' => object), where object is a Fortissimo::RequestCache
   * of some sort.
   *
   * @return array
   *  An associative array of name => cache pairs.
   * @see Fortissimo::RequestCache
   */
  public function getCaches() {
    $caches = $this->getFacility(Registry::CACHES);
    foreach ($caches as $cache) $cache->init();
    return $caches;
  }

  public function getDatasources() {
    return $this->getFacility(Registry::DATASOURCES);
  }

  /**
   * Internal helper function.
   *
   * @param string $type
   *  The type of item to retrieve. Use the Config class constants.
   * @return array
   *  An associative array of the form <code>array('name' => object)</code>, where
   *  the object is an instance of the respective 'invoke' class.
   */
  protected function getFacility($type = Registry::LOGGERS) {
    $facilities = array();
    foreach ($this->config[$type] as $name => $facility) {
      $klass = $facility['class'];
      $params = isset($facility['params']) ? $this->getParams($facility['params']) : array();
      $facilities[$name] = new $klass($params, $name);
    }
    return $facilities;
  }

  /**
   * Get the parameters for a facility such as a logger or a cache.
   *
   * @param array $params
   *  Configuration for the given facility.
   * @return array
   *  An associative array of param name/values. @code<param name="foo">bar</param>@endcode
   *  becomes @code array('foo' => 'bar') @endcode.
   */
  protected function getParams(array $params) {
    $res = array();
    // Basically, for facility params we just collapse the array.
    foreach ($params as $name => $values) {
      $res[$name] = $values['value'];
    }
    return $res;
  }

  /**
   * Given a request name, retrieves a request queue.
   *
   * The queue (in the form of an array) contains information about what
   * commands should be run, and in what order.
   *
   * @param string $requestName
   *  The name of the request
   * @param boolean $allowInternalRequests
   *  If this is true, internal requests (@-requests, at-requests) will be allowed.
   * @return Fortissimo::Request
   *  A queue of commands that need to be executed. See {@link createCommandInstance()}.
   * @throws Fortissimo::RequestNotFoundException
   *  If no such request is found, or if the request is malformed, and exception is
   *  thrown. This exception should be considered fatal, and a 404 error should be
   *  returned. Note that (provisionally) a Fortissimo::RequestNotFoundException is also thrown if
   *  $allowInternalRequests if FALSE and the request name is for an internal request. This is
   *  basically done to prevent information leakage.
   */
  public function getRequest($requestName, $allowInternalRequests = FALSE) {

    // Protection against attempts at request hacking.
    if (!self::isLegalRequestName($requestName, $allowInternalRequests))  {
      throw new \Fortissimo\RequestNotFoundException('Illegal request name.');
    }

    if (empty($this->config[Registry::REQUESTS][$requestName])) {
      // This should be treated as a 404.
      throw new \Fortissimo\RequestNotFoundException(sprintf('Request %s not found', $requestName));
      //$request = $this->config[Registry::REQUESTS]['default'];
    }
    else {
      $request = $this->config[Registry::REQUESTS][$requestName];
    }

    $isCaching = isset($request['#caching']) && filter_var($request['#caching'], FILTER_VALIDATE_BOOLEAN);
    $isExplaining = isset($request['#explaining']) && filter_var($request['#explaining'], FILTER_VALIDATE_BOOLEAN);

    unset($request['#caching'], $request['#explaining']);

    // Once we have the request, find out what commands we need to execute.
    $commands = array();
    foreach ($request as $cmd => $cmdConfig) {
      $commands[] = $this->createCommandInstance($cmd, $cmdConfig);
    }

    $request = new \Fortissimo\Request($requestName, $commands);
    $request->setCaching($isCaching);
    $request->setExplain($isExplaining);

    return $request;
  }

  /**
   * Create a command instance.
   *
   * Retrieve command information from the configuration file and transform these
   * into an internal data structure.
   *
   * @param string $cmd
   *  Name of the command
   * @param array $config
   *  Command configuration
   * @return array
   *  An array with the following keys:
   *  - name: Name of the command
   *  - class: Name of the class
   *  - instance: An instance of the class
   *  - params: Parameter information. Note that the application must take this
   *    information and correctly populate the parameters at execution time.
   *    Parameter information is returned as an associative array of arrays:
   *    <?php $param['name'] => array('from' => 'src:name', 'value' => 'default value'); ?>
   *  - listeners: Information on event listeners and what they are listening for.
   * @throws Fortissimo::Exception
   *  In the event that a paramter does not have a name, an exception is thrown.
   */
  protected function createCommandInstance($cmd, $config) {
    $class = $config['class'];
    if (empty($class)) {
      throw new \Fortissimo\ConfigurationException('No class specified for ' . $cmd);
    }

    $cache = isset($config['caching']) && filter_var($config['caching'], FILTER_VALIDATE_BOOLEAN);
    $params = isset($config['params']) ? $config['params'] : array();
    $listeners = isset($config['listeners']) ? $config['listeners'] : array();

    $inst = new $class($cmd, $cache);
    return array(
      'isCaching' => $cache,
      'name' => $cmd,
      'class' => $class,
      'instance' => $inst,
      'params' => $params,
      'listeners' => $listeners,
    );
  }

  /**
   * Get the configuration information.
   *
   * @return array
   *  The configuration information
   */
  public function getConfig() {
    return $this->config;
  }

}
