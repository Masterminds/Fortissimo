<?php
/**
 * @file
 */
namespace Fortissimo\Cache;

/**
 * Base class for all cache implementations.
 *
 * The CacheManager manages these.
 */
abstract class Base {
  /**
   * The parameters for this data source
   */
  protected $params = NULL;
  protected $default = FALSE;
  protected $name = NULL;
  protected $datasourceManager = NULL;
  protected $logManager = NULL;

  /**
   * Construct a new datasource.
   *
   * @param array $params
   *  The parameters passed in from the configuration.
   * @param string $name
   *  The name of the facility.
   */
  public function __construct($params = array(), $name = 'unknown_cache') {
    $this->params = $params;
    $this->name = $name;
    $this->default = isset($params['isDefault']) && filter_var($params['isDefault'], FILTER_VALIDATE_BOOLEAN);
  }

  public function setDatasourceManager(\Fortissimo\Datasource\Manager $manager) {
    $this->datasourceManager = $manager;
  }

  public function setLogManager(\Fortissimo\Logger\Manager $manager) {
    $this->logManager = $manager;
  }

  public function getName() {
    return $this->name;
  }

  /**
   * Determine whether this is the default cache.
   *
   * Note that this may be called *before* init().
   *
   * @return boolean
   *  Returns TRUE if this is the default. Typically the default status is
   *  assigned in the commands.xml file.
   */
  public function isDefault() {
    return $this->default;
  }

  /**
   * Perform any necessary initialization.
   */
  public abstract function init();

  /**
   * Add an item to the cache.
   *
   * @param string $key
   *  A short (<255 character) string that will be used as the key. This is short
   *  so that database-based caches can optimize for varchar fields instead of
   *  text fields.
   * @param string $value
   *  The string that will be stored as the value.
   * @param integer $expires_after
   *  The number of seconds that should be considered the max age of the cached item. The
   *  details of how this is interpreted are cache dependent.
   */
  public abstract function set($key, $value, $expires_after = NULL);
  /**
   * Clear the entire cache.
   */
  public abstract function clear();
  /**
   * Delete an item from the cache.
   *
   * @param string $key
   *  The key to remove from the cache.
   */
  public abstract function delete($key);
  /**
   * Retrieve an item from the cache.
   *
   * @param string $key
   *  The key to return.
   * @return mixed
   *  The string found in the cache, or NULL if nothing was found.
   */
  public abstract function get($key);
}
