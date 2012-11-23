<?php
/**
 * @file
 *
 * The datasource manager.
 */
namespace Fortissimo\Datasource;

/**
 * Manages data sources.
 *
 * Fortissimo provides facilities for declaring multiple data sources. A
 * datasource is some readable or writable backend like a database.
 *
 * This class manages multiple data sources, providing the execution context
 * with a simple way of retrieving datasources by name.
 */
class Manager {

  /**
   * The configuration to create datasouces.
   */
  protected $config = NULL;

  /**
   * A cache of instanciated datasources. Assuming that datasources are kept 
   * once built unless intentionally removed.
   * @var array
   */
  protected $datasourceCache = array();

  protected $cacheManager = NULL;
  protected $logManager = NULL;

  /**
   * Build a new datasource manager.
   *
   * @param array $config
   *  The configuration for this manager as an associative array of
   *  names => config.
   */
  public function __construct($config) {
    $this->config = &$config;
  }

  /**
   * Set the cache manager.
   *
   * @param \Fortissimo\Cache\Manager $manager
   *   A cache manager object.
   *
   * @return \Fortissimo\Datasource\Manager
   *   $this is returned because it is useful for chaining.
   */
  public function setCacheManager(\Fortissimo\Cache\Manager $manager) {
    $this->cacheManager = $manager;

    return $this;
  }

  /**
   * Get the cache manager.
   * 
   * @return \Fortissimo\Cache\Manager
   *   The cache manager object.
   */
  public function cacheManager() {
    return $this->cacheManager;
  }

  /**
   * Set the log manager
   *
   * @param \Fortissimo\Logger\Manager $manager
   *   The log manager.
   *
   * @return \Fortissimo\Datasource\Manager
   *   $this is returned because it is useful for chaining.
   */
  public function setLogManager(\Fortissimo\Logger\Manager $manager) {
    $this->logManager = $manager;

    return $this;
  }

  public function logManager() {
    return $this->logManager;
  }

  /**
   * Get a datasource.
   *
   * If a datasouce has not been initialized first initialize it.
   *
   * @param string $name
   *  The name of the datasource to return.
   *
   * @return mixed
   *  The datasource.
   */
  public function datasource($name) {
    
    // If the datasource does not already exist create it.
    if (!isset($this->datasourceCache[$name])) {
      $params = isset($this->config[$name]['params']) ? $this->getParams($this->config[$name]['params']) : array();
      $this->datasourceCache[$name] = $this->config[$name]['class']($params, $name, $this);
    }

    return $this->datasourceCache[$name];
  }

  /**
   * Initialize all datasources managed by this manager instance.
   *
   * By default, datasource initialization is delayed as long as possible so that
   * resources are not allocated needlessly. On some occasions, you may want to
   * initialize all of the datasources at once. Use this function to do so.
   *
   * Keep in mind that if there are a lot of datasources, this may consume many
   * system resources.
   *
   * @return \Fortissimo\Datasource\Manager
   *   $this is returned because it is useful for chaining.
   */
  public function initializeAllDatasources() {
    foreach ($this->confit as $name => $config) {
      if (!isset($this->datasourceCache[$name])) {
        $params = isset($this->config[$name]['params']) ? $this->getParams($this->config[$name]['params']) : array();
        $this->datasourceCache[$name] = $this->config[$name]['class']($params, $name, $this);
      }
    }

    return $this;
  }

  /**
   * Get all initialized datasources.
   *
   * This does not initialize resources automatically. If you need all datasources
   * to be initialized first, call initializeAllDatasources() before calling this.
   *
   * @return array
   *  Returns an associative array of datasource name=>object pairs.
   */
  public function datasources() {
    return $this->datasourceCache;
  }

  /**
   * Remove a Datasource from the cache.
   *
   * The config for the datasouce is still kept and a new one can be created from
   * the existing config. The existing datasouce is unset.
   * 
   * @param  string $name
   *   The name of the Datasource to unset.
   *
   * @return \Fortissimo\Datasource\Manager
   *   $this is returned because it is useful for chaining.
   */
  public function removeDatasource($name) {

    if (isset($this->datasourceCache[$name])) {
      unset($this->datasourceCache[$name]);
    }
    else {
      throw new \Fortissimo\Exception('Attempting to remove Datasource (' . $name .'). Datasource does not exist.');
    }

    return $this;
  }

  /**
   * Add a Datasource.
   *
   * The config for the datasouce is still kept and a new one can be created from
   * the existing config. The existing datasouce is unset.
   *
   * Note, this can be used to override the config for an existing datasource.
   *
   * @param callable $factory
   *   A factory function, anonymous function, or class with __invoke that can
   *   create the datasource.
   * @param string $name
   *   The name of the Datasource to unset.
   * @param array $params
   *   An array of key/value pairs to pass to the factory when it is called to
   *   create the datasource. (OPTIONAL)
   * 
   * @return \Fortissimo\Datasource\Manager
   *   $this is returned because it is useful for chaining.
   */
  public function addDatasource($factory, $name, $params = array()) {

    $this->config[$name] = array(
      'class' => $factory,
      'params' => $params,
    );

    return $this;
  }

  /**
   * Get the parameters for a datasource.
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
}
