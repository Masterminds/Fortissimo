<?php
/**
 * Tracks context information over the lifecycle of a request's execution.
 *
 * An execution context is passed from command to command during the course of
 * a request's execution. State information is inserted into the context by
 * various commands. Certain commands may also take data out of the context, though
 * this operation is not without its risks. Finally, objects may use information
 * found in the context, either to perform some operation (writing data to
 * the client) or to modify the context data.
 *
 * The idea of the context is to provide three things during the course of the
 * request(s):
 * - Shared access to data being generated.
 * - Common access to the logging system (see FortissimoLoggerManager).
 * - Common access to the datasources (See FortissimoDatasourceManager).
 * - Access to the underlying cache engine (so commands can cache their own data).
 *   See FortissimoCacheManager.
 * - Access to the request mapper. See FortissimoRequestMapper.
 *
 * Thus, every command can utilize the loggers and datasources defined for the
 * application, and commands can pass data throughout the lifecycle of the request.
 *
 * Note that when one request forwards to another request, the context may be
 * transferred along with it. Thus, sometimes a context will span multiple
 * defined requests (though this will always be in the handling of one
 * client serving operation -- i.e., it will only span one HTTP request, even if
 * multiple Fortissimo requests are fired.)
 *
 * @see Fortissimo
 */
class FortissimoExecutionContext implements IteratorAggregate {

  // Why do we create a class that is basically a thin wrapper around an array?
  // Three reasons:
  // 1. It gives us the ability to control access to the objects in the context.
  // 2. It gives us the ability to add validation and other features
  // 3. It eliminates the need to do overt pass-by-reference of a context array,
  //   which is likely to cause confusion with less experienced developers.
  // However, we do provide the to/from array methods to allow developers to make
  // use of the richer array library without our re-inventing the wheel.

  protected $data = NULL;
  protected $logger = NULL;
  protected $datasources = NULL;
  protected $cacheManager = NULL;
  protected $requestMapper = NULL;
  /** Command cache. */
  protected $cache = array();
  protected $caching = FALSE;

  /**
   * Create a new context.
   *
   * @param array $initialContext
   *  An associative array of context pairs.
   * @param FortissimoLoggerManager $logger
   *  The logger.
   * @param FortissimoDatasourceManager $datasources
   *  The manager for all datasources declared for this request.
   * @param FortissimoCacheManager $cacheManager
   *  The manager for all caches. Commands may use this to store or retrieve cached content.
   * @param FortissimoRequestMapper $requestMapper
   *  The request mapper used on this request. A request mapper should know how to construct
   *  a URL to the app.
   */
  public function __construct($initialContext = array(), FortissimoLoggerManager $logger = NULL, FortissimoDatasourceManager $datasources = NULL, FortissimoCacheManager $cacheManager = NULL, FortissimoRequestMapper $requestMapper = NULL) {
    if ($initialContext instanceof FortissimoExecutionContext) {
      $this->data = $initialContext->toArray();
    }
    else {
      $this->data = $initialContext;
    }

    // Store logger and datasources managers if they are set.
    if (isset($logger)) $this->logger = $logger;
    if (isset($datasources)) $this->datasources = $datasources;
    if (isset($cacheManager)) $this->cacheManager = $cacheManager;
    if (isset($requestMapper)) $this->requestMapper = $requestMapper;
  }

  /**
   * Log a message.
   * The context should always have a hook into a logger of some sort. This method
   * passes log messages to the underlying logger.
   *
   * @param mixed $msg
   *  The message to log. This can be a string or an Exception.
   * @param string $category
   *  A category. Typically, this is a string like 'error', 'warning', etc. But
   *  applications can customize their categories according to the underlying
   *  logger.
   * @see FortissimoLoggerManager Manages logging facilities.
   * @see FortissimoLogger Describes a logger.
   */
  public function log($msg, $category) {
    if (isset($this->logger)) {
      $this->logger->log($msg, $category);
    }
  }

  /**
   * Retrieve a named datasource.
   *
   * If no name is passed, this will try to retrieve the default datasource.
   *
   * @param string $name
   *  The name of the datasource to retrieve. If no name is given, the default
   *  datasource will be used.
   * @return FortissimoDatasource
   *  The requested datasource, or NULL if none is found.
   */
  public function datasource($name = NULL) {
    return $this->datasources->datasource($name);
  }

  /**
   * Convenience function for {@link datasource()}.
   */
  public function ds($name = NULL) {
    return $this->datasource($name);
  }

  /**
   * Check if the context has an item with the given name.
   *
   * @param string $name
   *  The name of the item to check for.
   */
  public function has($name) {
    return isset($this->data[$name]);
  }

  /**
   * Get the size of the context.
   *
   * @return int
   *  Number of items in the context.
   */
  public function size() {
    return count($this->data);
  }

  /**
   * Add a new name/value pair to the context.
   *
   * This will replace an existing entry with the same name. To check before
   * writing, use {@link has()}.
   *
   * @param string $name
   *  The name of the item to add.
   * @param mixed $value
   *  Some value to add. This can be a primitive, an object, or a resource. Note
   *  that storing resources is not serializable.
   */
  public function add($name, $value) {
    $this->data[$name] = $value;
  }
  //public function put($name, $value) {$this->add($name, $value);}

  /**
   * Add all values in the array.
   *
   * This will replace any existing entries with the same name.
   *
   * @param array $array
   *  Array of values to merge into the context.
   */
  public function addAll($array) {
    $this->data = $array + $this->data;
  }

  /**
   * Get a value by name.
   *
   * This fetches an item out of the context and returns a reference to it. A
   * reference is returned so that one can modify the value. But this introduces a risk: You
   * can accidentally modify the context value if you are not careful.
   *
   * If you are working with a non-object and you want to use it by reference, use the following
   * syntax:
   * @code
   * $foo =& $context->get('foo');
   * @endcode
   *
   * @return mixed
   *  A reference to the value in the context, or NULL if $name was not found.
   */
  public function &get($name) {
    $var = NULL;
    if (isset($this->data[$name])) {
      $var =  &$this->data[$name];
    }
    return $var;
  }

  /**
   * Remove an item from the context.
   *
   * @param string $name
   *  The thing to remove.
   */
  public function remove($name) {
    if (isset($this->data[$name])) unset($this->data[$name]);
  }

  /**
   * Convert the context to an array.
   *
   * @return array
   *  Associative array of name/value pairs.
   */
  public function toArray() {
    return $this->data;
  }

  /**
   * Replace the current context with the values in the given array.
   *
   * @param array $contextArray
   *  An array of new name/value pairs. The old context will be destroyed.
   */
  public function fromArray($contextArray) {
    $this->data = $contextArray;
  }

  /**
   * Get an iterator of the execution context.
   *
   * @return Iterator
   *  The iterator of each item in the execution context.
   */
  public function getIterator() {
    // Does this work?
    return new ArrayIterator($this->data);
  }

  /**
   * Expose the logger manager to commands.
   *
   * The logger manager is responsible for managing all of the underlying
   * loggers. This method provides access to the logger manager. For integrity
   * purposes, it is advised that loggers not be re-configured by commands.
   *
   * @return FortissimoLoggerManager
   *  The logger manager for the current server.
   */
  public function getLoggerManager() {
    return $this->logger;
  }

  /**
   * Get the datasource manager.
   *
   * The datasource manager is manages all of the datasources defined in
   * this Fortissimo instance (typically defined in commands.xml).
   *
   * Often, you will want to get datasources with the {@link datasource()} function
   * defined in this class. Sometimes, though, you may need more control over
   * the datasource. This method provides direct access to the manager, which
   * will give you a higher degree of control.
   *
   * @return FortissimoDatasourceManager
   *  An initialized datasource manager.
   */
  public function getDatasourceManager() {
    //throw new Exception('Not implemented.');
    return $this->datasourceManager;
  }

  /**
   * Get the FortissimoCacheManager for this request.
   *
   * Fortissimo provides facilities for providing any number of caches. All of the caches are
   * managed by a FortissimoCacheManager instance. This returns a handle to the manager, from
   * which tools can operate on caches.
   *
   * @return FortissimoCacheManager
   *  The current cache manager.
   */
  public function getCacheManager() {
    return $this->cacheManager;
  }

  /**
   * Get the FortissimoRequestMapper for this request.
   *
   * The Request Mapper maps requests to URLs and URLs to requests. It can be used
   * for constructing URLs to other parts of the app.
   */
  public function getRequestMapper() {
    return $this->requestMapper;
  }
}

/**
 * Manage caches.
 *
 * This manages top-level {@link FortissimoRequestCache}s. Just as with
 * {@link FortissimoLoggerManager}, a FortissimoCacheManager can manage
 * multiple caches. It will proceed from cache to cache in order until it
 * finds a hit. (Order is determined by the order returned from the
 * configuration object.)
 *
 * Front-line Fortissimo caching is optimized for string-based values. You can,
 * of course, serialize values and store them in the cache. However, the
 * serializing and de-serializing is left up to the implementor.
 *
 * Keys may be hashed for optimal storage in the database. Values may be optimized
 * for storage in the database. All details of caching algorithms, caching style
 * (e.g. time-based, LRU, etc.) is handled by the low-level caching classes.
 *
 * @see FortissimoRequestCache For details on caching.
 */
class FortissimoCacheManager {
  protected $caches = NULL;

  public function __construct($caches) {
    $this->caches = $caches;
  }

  public function setLogManager($manager) {
    foreach ($this->caches as $name => $obj) $obj->setLogManager($manager);
  }

  public function setDatasourceManager($manager) {
    foreach ($this->caches as $name => $obj) $obj->setDatasourceManager($manager);
  }

  /**
   * Given a name, retrieve the cache.
   *
   * @return FortissimoRequestCache
   * If there is a cache with this name, the cache object will be
   * returned. Otherwise, this will return NULL.
   */
  public function getCacheByName($name) {
    return $this->caches[$name];
  }

  /**
   * Get an array of cache names.
   *
   * This will generate a list of names for all of the caches
   * that are currently active. This name can be passed to getCacheByName()
   * to get a particular cache.
   *
   * @return array
   *  An indexed array of cache names.
   */
  public function getCacheNames() {
    return array_keys($this->caches);
  }

  /**
   * Get the default cache.
   */
  public function getDefaultCache() {
    foreach ($this->caches as $name => $cache) {
      if ($cache->isDefault()) return $cache;
    }
  }

  /**
   * Get a value from the caches.
   *
   * This will read sequentially through each defined cache until it
   * finds a match. If no match is found, this will return NULL. Otherwise, it
   * will return the value.
   */
  public function get($key) {
    foreach ($this->caches as $n => $cache) {
      $res = $cache->get($key);

      // Short-circuit if we find a value.
      if (isset($res)) return $res;
    }
  }

  /**
   * Store a value in a cache.
   *
   * This will write a value to a cache. If a cache name is given
   * as the third parameter, then that named cache will be used.
   *
   * If no cache is named, the value will be stored in the first available cache.
   *
   * If no cache is found, this will silently continue. If a name is given, but the
   * named cache is not found, the next available cache will be used.
   *
   * @param string $key
   *  The cache key
   * @param string $value
   *  The value to store
   * @param string $cache
   *  The name of the cache to store the value in. If not given, the cache
   *  manager will store the item wherever it is most convenient.
   * @param int $expires_after
   *  An integer indicating how many seconds this item should live in the cache. Some
   *  caching backends may choose to ignore this. Some (like pecl/memcache, pecl/memcached) may
   *  have an upper limit (30 days). Setting this to NULL will invoke the caching backend's
   *  default.
   */
  public function set($key, $value, $expires_after = NULL, $cache = NULL) {

    // If a named cache key is found, set:
    if (isset($cache) && isset($this->caches[$cache])) {
      return $this->caches[$cache]->set($key, $value, $expires_after);
    }

    // XXX: Right now, we just use the first item in the cache:
    /*
    $keys = array_keys($this->caches);
    if (count($keys) > 0) {
      return $this->caches[$keys[0]]->set($key, $value, $expires_after);
    }
    */
    $cache = $this->getDefaultCache();
    if (!empty($cache)) $cache->set($key, $value, $expires_after);
  }

  /**
   * Check whether the value is available in a cache.
   *
   * Note that in most cases, running {@link has()} before running
   * {@link get()} will result in the same access being run twice. For
   * performance reasons, you are probably better off calling just
   * {@link get()} if you are accessing a value.
   *
   * @param string $key
   *  The key to check for.
   * @return boolean
   *  TRUE if the key is found in the cache, false otherwise.
   */
  public function has($key) {
    foreach ($this->caches as $n => $cache) {
      $res = $cache->has($key);

      // Short-circuit if we find a value.
      if ($res) return $res;
    }
  }

  /**
   * Check which cache (if any) contains the given key.
   *
   * If you are just trying to retrieve a cache value, use {@link get()}.
   * You should use this only if you are trying to determine which underlying
   * cache holds the given value.
   *
   * @param string $key
   *   The key to search for.
   * @return string
   *  The name of the cache that contains this key. If the key is
   *  not found in any cache, NULL will be returned.
   */
  public function whichCacheHas($key) {
    foreach ($this->caches as $n => $cache) {
      $res = $cache->has($key);

      // Short-circuit if we find a value.
      if ($res) return $n;
    }
  }
}

/**
 * Manages data sources.
 *
 * Fortissimo provides facilities for declaring multiple data sources. A
 * datasource is some readable or writable backend like a database.
 *
 * This class manages multiple data sources, providing the execution context
 * with a simple way of retrieving datasources by name.
 */
class FortissimoDatasourceManager {

  protected $datasources = NULL;
  protected $initMap = array();


  /**
   * Build a new datasource manager.
   *
   * @param array $config
   *  The configuration for this manager as an associative array of
   *  names=>instances.
   */
  public function __construct($config) {
    $this->datasources = &$config;
  }

  public function setCacheManager(FortissimoCacheManager $manager) {
    foreach ($this->datasources as $name => $obj) $obj->setCacheManager($manager);
  }

  public function setLogManager(FortissimoLoggerManager $manager) {
    foreach ($this->datasources as $name => $obj) $obj->setLogManager($manager);
  }


  /**
   * Get a datasource by its string name.
   *
   * @param string $name
   *  The name of the datasource to get.
   * @return FortissimoDatasource
   *  The requested source, or NULL if no such source exists.
   */
  public function getDatasourceByName($name) {
    return $this->datasources[$name];
  }

  /**
   * Scan the datasources and return the first one marked default.
   *
   * Note that this does not make sure that datasources have been initialized.
   * @return FortissimoDatasource
   *  An initialized FortissimoDatasource, or NULL if no default is found.
   */
  protected function getDefaultDatasource() {
    foreach ($this->datasources as $k => $o) if ($o->isDefault()) return $o;
  }

  /**
   * Get a datasource.
   *
   * If a name is given, retrieve the named datasource. Otherwise, return
   * the default. If no suitable datasource is found, return NULL.
   *
   * @param string $name
   *  The name of the datasource to return.
   * @return FortissimoDatasource
   *  The datasource.
   */
  public function datasource($name = NULL) {
    $ds = NULL;
    if (empty($name)) {
      $ds = $this->getDefaultDatasource();
    }
    else {
      $ds = $this->getDatasourceByName($name);
    }

    // We initialize lazily so that datasources do not
    // have resources allocated until necessary.
    if (!empty($ds) && !isset($this->initMap[$name])) {
      $ds->init();
      $this->initMap[$name] = TRUE;
    }
    return $ds;
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
   */
  public function initializeAllDatasources() {
    foreach ($this->datasources as $name => $ds) {
      if (!isset($this->initMap[$name])) {
        $ds->init();
        $this->initMap[$name] = TRUE;
      }
    }
  }

  /**
   * Get all datasources.
   *
   * This does not initialize resources automatically. If you need all datasources
   * to be initialized first, call initializeAllDatasources() before calling this.
   *
   * @return array
   *  Returns an associative array of datasource name=>object pairs.
   */
  public function datasources() {
    return $this->datasources;
  }
}

/**
 * Manage loggers for a server.
 *
 * A {@link Fortissimo} instance may have zero or more loggers. Loggers
 * perform the standard task of handling messages that need recording for
 * review by administrators.
 *
 * The logger manager manages the various logging instances, delegating logging
 * tasks.
 *
 */
class FortissimoLoggerManager {

  protected $loggers = NULL;

  /**
   * Build a new logger manager.
   *
   * @param QueryPath $config
   *  The configuration object. Typically, this is from commands.xml.
   */
  //public function __construct(QueryPath $config) {
  public function __construct($config) {
    // Initialize array of loggers.
    $this->loggers = &$config;
  }

  public function setCacheManager(FortissimoCacheManager $manager) {
    foreach ($this->loggers as $name => $obj) $obj->setCacheManager($manager);
  }

  public function setDatasourceManager(FortissimoDatasourceManager $manager) {
    foreach ($this->loggers as $name => $obj) $obj->setDatasourceManager($manager);
  }


  /**
   * Get a logger.
   *
   * @param string $name
   *  The name of the logger, as indicated in the configuration.
   * @return FortissimoLogger
   *  The logger corresponding to the name, or NULL if no such logger is found.
   */
  public function getLoggerByName($name) {
    return $this->loggers[$name];
  }

  /**
   * Get all buffered log messages.
   *
   * Some, but by no means all, loggers buffer messages for later retrieval.
   * This method provides a way of retrieving all buffered messages from all
   * buffering loggers. Messages are simply concatenated together from all of
   * the available loggers.
   *
   * To fetch the log messages of just one logger instead of all of them, use
   * {@link getLoggerByName()}, and then call that logger's {@link FortissimoLogger::getMessages()}
   * method.
   *
   * @return array
   *  An indexed array of messages.
   */
  public function getMessages() {
    $buffer = array();
    foreach ($this->loggers as $name => $logger) {
      $buffer += $logger->getMessages();
    }
    return $buffer;
  }

  /**
   * Log messages.
   *
   * @param mixed $msg
   *  A string or an Exception.
   * @param string $category
   *  A string indicating what type of message is
   *  being logged. Standard values for this are:
   *  - error
   *  - warning
   *  - info
   *  - debug
   *  Your application may use whatever values are
   *  fit. However, underlying loggers may interpret
   *  these differently.
   * @param string $details
   *   Additional information. When $msg is an exception,
   *   this will automatically be populated with stack trace
   *   information UNLESS explicit string information is passed
   *   here.
   */
  public function log($msg, $category, $details = '') {
    foreach ($this->loggers as $name => $logger) {
      $logger->rawLog($msg, $category);
    }
  }
}

abstract class FortissimoCache {
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

  public function setDatasourceManager(FortissimoDatasourceManager $manager) {
    $this->datasourceManager = $manager;
  }

  public function setLogManager(FortissimoLoggerManager $manager) {
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

/**
 * A cache for command or request output.
 *
 * This provides a caching facility for the output of entire requests, or for the
 * output of commands. This cache is used for high-level data caching from within
 * the application.
 *
 * The Fortissimo configuration, typically found in commands.xml, must specify which
 * requests and commands are cacheable.
 *
 * The underlying caching implementation determines how things are cached, for how
 * long, and what the expiration conditions are. It will also determine where data
 * is cached.
 *
 * External caches, like Varnish or Squid, tend not to use this mechanism. Internal
 * mechanisms like APC or custom database caches would use this mechanism. Memcached
 * would also use this mechanism, if appropriate.
 *
 * @deprecated
 */
interface FortissimoRequestCache {

  /**
   * Perform any necessary initialization.
   */
  public function init();

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
  public function set($key, $value, $expires_after = NULL);
  /**
   * Clear the entire cache.
   */
  public function clear();
  /**
   * Delete an item from the cache.
   *
   * @param string $key
   *  The key to remove from the cache.
   */
  public function delete($key);
  /**
   * Retrieve an item from the cache.
   *
   * @param string $key
   *  The key to return.
   * @return mixed
   *  The string found in the cache, or NULL if nothing was found.
   */
  public function get($key);
}

/**
 * A datasource.
 *
 * Fortissimo provides a very general (and loose) abstraction for datasources.
 * The idea is to make it possible for all datasources -- from files to RDBs to
 * NoSQL databases to LDAPS -- to be defined in a central place (along with
 * requests) so that they can easily be configured and also leveraged by the
 * command configuration.
 *
 * The generality of this class makes it less than ideal for doing strict checks
 * on capabilities, but, then, that's what inheritance if for, isn't it.
 *
 * Each data source type should extend this basic class. This base class contains
 * the absolute minimal amount of information that Fortissimo needs in order to
 * load the datasources and instruct them to initialize themselves.
 *
 * From there, it's up to implementors to build useful datasource wrappers that
 * can be leveraged from within commands.
 */
abstract class FortissimoDatasource {
  /**
   * The parameters for this data source
   */
  protected $params = NULL;
  protected $default = FALSE;
  protected $name = NULL;
  protected $logManager = NULL;
  protected $cacheManager = NULL;

  /**
   * Construct a new datasource.
   *
   * @param array $params
   *  An associative array of params from the configuration.
   * @param string $name
   *  The name of the facility.
   */
  public function __construct($params = array(), $name = 'unknown_datasource') {
    $this->params = $params;
    $this->name = $name;
    $this->default = isset($params['isDefault']) && filter_var($params['isDefault'], FILTER_VALIDATE_BOOLEAN);
  }

  public function setCacheManager(FortissimoCacheManager $manager) {
    $this->cacheManager = $manager;
  }

  public function setLogManager(FortissimoLoggerManager $manager) {
    $this->logManager = $manager;
  }

  /**
   * Get this datasource's name, as set in the configuration.
   *
   * @return string
   *  The name of this datasource.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Determine whether this is the default datasource.
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
   * This is called once before the datasource is first used.
   *
   * While there is no guarantee that this will be called only when necessary, it
   * is lazier than the constructor, so initialization of connections may be better
   * left to this function than to overridden constructors.
   */
  public abstract function init();

  /**
   * Retrieve the underlying datasource object.
   *
   * Ideally, this returns the underlying data source. In some circumstances,
   * it may return NULL.
   *
   * @return mixed
   *  The underlying datasource. Example: a PDO object or a Mongo object.
   */
  public abstract function get();
}

/**
 * A logger responsible for logging messages to a particular destination.
 *
 * The FortissimoLogger abstract class does recognize one parameter.
 *
 *  - 'categories': An array or comma-separated list of categories that this logger listens for.
 *     If no categories are set, this logs ALL categories.
 *
 * Category logic is encapsulated in the method FortissimoLogger::isLoggingThisCategory().
 *
 *
 */
abstract class FortissimoLogger {

  /**
   * The parameters for this logger.
   */
  protected $params = NULL;
  protected $facilities = NULL;
  protected $name = NULL;
  protected $datasourceManager = NULL;
  protected $cacheManager = NULL;

  /**
   * Construct a new logger instance.
   *
   * @param array $params
   *   An associative array of name/value pairs.
   * @param string $name
   *   The name of this logger.
   */
  public function __construct($params = array(), $name = 'unknown_logger') {
    $this->params = $params;
    $this->name = $name;

    // Add support for facility declarations.
    if (isset($params['categories'])) {
      $fac = $params['categories'];
      if (!is_array($fac)) {
        $fac = explode(',', $fac);
      }
      // Assoc arrays provide faster lookups on keys.
      $this->facilities = array_combine($fac, $fac);
    }

  }

  public function setDatasourceManager(FortissimoDatasourceManager $manager) {
    $this->datasourceManager = $manager;
  }

  public function setCacheManager(FortissimoCacheManager $manager) {
    $this->logManager = $manager;
  }


  /**
   * Get the name of this logger.
   *
   * @return string
   *  The name of this logger.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Return log messages.
   *
   * Some, but not all, loggers buffer messages for retrieval later. This
   * method should be used to retrieve messages from such loggers.
   *
   * @return array
   *  An indexed array of log message strings. By default, this returns an
   *  empty array.
   */
  public function getMessages() {
    return array();
  }

  /**
   * Check whether this category is being logged.
   *
   * In general, this check is run from rawLog(), and so does not need to be
   * directly called elsewhere.
   *
   * @param string $category
   *  The category to check.
   * @return boolean
   *  TRUE if this is logging for the given category, false otherwise.
   */
  public function isLoggingThisCategory($category) {
    return empty($this->facilities) || isset($this->facilities[$category]);
  }

  /**
   * Handle raw log requests.
   *
   * This handles the transformation of objects (Exceptions)
   * into loggable strings.
   *
   * @param mixed $message
   *  Typically, this is an Exception, some other object, or a string.
   *  This method normalizes the $message, converting it to a string
   *  before handing it off to the {@link log()} function.
   * @param string $category
   *  This message is passed on to the logger.
   * @param string $details
   *  A detail for the given message. If $message is an Exception, then
   *  details will be automatically filled with stack trace information.
   */
  public function rawLog($message, $category = 'General Error', $details = '') {

    // If we shouldn't log this category, skip this step.
    if (!$this->isLoggingThisCategory($category)) return;

    if ($message instanceof Exception) {
      $buffer = $message->getMessage();

      if (empty($details)) {
        $details = get_class($message) . PHP_EOL;
        $details .= $message->getMessage() . PHP_EOL;
        $details .= $message->getTraceAsString();
      }

    }
    elseif (is_object($message)) {
      $buffer = $mesage->toString();
    }
    else {
      $buffer = $message;
    }
    $this->log($buffer, $category, $details);
    return;
  }

  /**
   * Initialize the logger.
   *
   * This will happen once per server construction (typically
   * once per request), and it will occur before the command is executed.
   */
  public abstract function init();

  /**
   * Log a message.
   *
   * @param string $msg
   *  The message to log.
   * @param string $severity
   *  The log message category. Typical values are
   *  - warning
   *  - error
   *  - info
   *  - debug
   * @param string $details
   *  Further text information about the logged event.
   */
  public abstract function log($msg, $severity, $details);

}

/**
 * Indicates that a condition has been met that necessitates interrupting the command execution chain.
 *
 * This exception is not necessarily intended to indicate that something went
 * wrong, but only htat a condition has been satisfied that warrants the interrupting
 * of the current chain of execution.
 *
 * Note that commands that throw this exception are responsible for responding
 * to the user agent. Otherwise, no output will be generated.
 *
 * Examples of cases where this might be desirable:
 * - Application should redirect (302, 304, etc.) user to another page.
 * - User needs to be prompted to log in, using HTTP auth, before continuing.
 */
class FortissimoInterrupt extends Exception {}
/**
 * Indicates that a fatal error has occured.
 *
 * This is the Fortissimo exception with the strongest implications. It indicates
 * that not only has an error occured, but it is of such a magnitude that it
 * precludes the ability to continue processing. These should be used sparingly,
 * as they prevent the chain of commands from completing.
 *
 * Examples:
 * - A fatal error has occurred, and a 500-level error should be returned to the user.
 * - Access is denied to the user.
 * - A request name cannot be found.
 */
class FortissimoInterruptException extends Exception {}
/**
 * General Fortissimo exception.
 *
 * This should be thrown when Fortissimo encounters an exception that should be
 * logged and stored, but should not interrupt the execution of a command.
 */
class FortissimoException extends Exception {}

/**
 * Transform an error or warning into an exception.
 */
class FortissimoErrorException extends FortissimoException {
  public static function initializeFromError($code, $str, $file, $line, $cxt) {
    //printf("\n\nCODE: %s %s\n\n", $code, $str);
    $class = __CLASS__;
    throw new $class($str, $code, $file, $line);
  }

  public function __construct($msg = '', $code = 0, $file = NULL, $line = NULL) {
    if (isset($file)) {
      $msg .= ' (' . $file;
      if (isset($line)) $msg .= ': ' . $line;
      $msg .= ')';
    }
    parent::__construct($msg, $code);
  }
}
/**
 * Configuration error.
 */
class FortissimoConfigurationException extends FortissimoException {}
/**
 * Request was not found.
 */
class FortissimoRequestNotFoundException extends FortissimoException {}

/**
 * Forward a request to another request.
 *
 * This special type of interrupt can be thrown to redirect a request mid-stream
 * to another request. The context passed in will be used to pre-seed the context
 * of the next request.
 */
class FortissimoForwardRequest extends FortissimoInterrupt {
  protected $destination;
  protected $cxt;

  /**
   * Construct a new forward request.
   *
   * The information in this forward request will be used to attempt to terminate
   * the current request, and continue processing by forwarding on to the
   * named request.
   *
   * @param string $requestName
   *  The name of the request that this should forward to.
   * @param FortissimoExecutionContext $cxt
   *  The context. IF THIS IS PASSED IN, the next request will continue using this
   *  context. IF THIS IS NOT PASSED OR IS NULL, the next request will begin afresh
   *  with an empty context.
   */
  public function __construct($requestName, FortissimoExecutionContext $cxt = NULL) {
    $this->destination = $requestName;
    $this->cxt = $cxt;
    parent::__construct('Request forward.');
  }

  /**
   * Get the name of the desired destination request.
   *
   * @return string
   *  A request name.
   */
  public function destination() {
    return $this->destination;
  }

  /**
   * Retrieve the context.
   *
   * @return FortissimoExecutionContext
   *  The context as it was at the point when the request was interrupted.
   */
  public function context() {
    return $this->cxt;
  }
}

/**
 * This class is used for building configurations.
 *
 *
 * Typical usage looks something like this:
 *
 * @code
 * <?php
 * Config::request('foo')
 *  ->doesCommand('command1')
 *  ->whichInvokes('MyCommandClass')
 *    ->withParam('arg1')
 *    ->whoseValueIs('Some default value')
 *  ->doesCommand('command2')
 *  ->whichInvokes('SomeOtherCommandClass')
 *    ->withParam('anArgument')
 *    ->from('get:q') // <-- Use $_GET['q']
 * ?>
 * @endcode
 *
 * This class is used to add requests, loggers, datasources, and cache handlers to
 * a Fortissimo application. Typically, it is used in commands.php.
 *
 * - Config::request(): Add a new request with a chain of commands.
 * - Config::includePath(): Add a new path that will be used by the autoloader.
 * - Config::group(): Add a new group that can be referenced from within a request.
 * - Config::datasource(): Add a new datasource, such as a database or document store.
 * - Config::logger(): Add a new logging facility.
 * - Config::cache(): Add a new cache.
 *
 * In Fortissimo, the data that Config creates may be used only at the beginning of a
 * request. Be careful of race conditions or other anomalies that might occur if you
 * attempt to use Config after Fortissimo has been bootstrapped.
 */
class Config {

  private static $instance = NULL;

  private $config = NULL;
  private $currentCategory = NULL;
  private $currentRequest = NULL;
  private $currentName = NULL;

  const REQUESTS = 'requests';
  const GROUPS = 'groups';
  const PATHS = 'paths';
  const DATASOURCES = 'datasources';
  const CACHES = 'caches';
  const LOGGERS = 'loggers';
  const REQUEST_MAPPER = 'requestMapper';
  const LISTENERS = 'listeners';

  public static function request($name, $description = '') {
    return self::set(self::REQUESTS, $name);
  }
  /**
   * Add an include path.
   *
   * This will be added to the class loader's registry.
   *
   * @param string $path
   *  The string path to add.
   * @return Config
   *  This object.
   */
  public static function includePath($path) {
    $i = self::inst();
    $i->config[self::PATHS][] = $path;
    $i->currentCategory = self::PATHS;
    $i->currentName = NULL;
    return $i;
  }

  /**
   * Request mappers determine how input is mapped to internal request names.
   *
   * Fortissimo provides a default request mapper that assumes that the incoming identifier
   * string is actually a request name. Thus http://example.com/?ff=foo is treated as if
   * it was trying to execute the request named 'foo'.
   *
   * For some common website features (like Search Engine Friendly URLs, aka SEFs), a more
   * robust mapper would be desirable. This allows developers to write a custom mapper and
   * use that instead.
   *
   * Example:
   *
   * @code
   * <?php
   * Config::useRequestMapper('MyMapperClass');
   * ?>
   * @endcode
   *
   * For implementation details, see FortissimoRequestMapper and Fortissimo::handleRequest().
   */
  public static function useRequestMapper($class) {
    $i = self::inst();
    $i->config[self::REQUEST_MAPPER] = $class;
    $i->currentCategory = self::REQUEST_MAPPER;
    $i->currentName = NULL;
    return $i;
  }
  /**
   * Declare a new group of commands.
   *
   * Entire groups of commands can be added to a request.
   *
   * @code
   * <?php
   * Config::group('myGroup')
   *  ->doesCommand('a')->whichInvokes('MyA')
   * ;
   *
   * Config::request('myRequest')
   *  ->doesCommand('b')->whichInvokes('MyB')
   *  ->usesGroup('myGroup')
   * ;
   * ?>
   * @endcode
   *
   * The above is equivalent to declaring a request with two commands ('a' and 'b').
   * You can re-use a group in multiple request, but you cannot use the same group
   * multiple times in the same request.
   *
   * @param string $name
   *  The name of the group.
   */
  public static function group($name) {
    return self::set(self::GROUPS, $name);
  }

  /**
   * Declare an event listener that will bind to ALL requests.
   *
   * @code
   * <?php
   * Config::listener('FooClass', 'load', function ($e) {});
   *
   * // ...
   *
   * // The above will automatically bind to this.
   * Config::request('foo')->hasCommand('bar')->whichInvokes('FooClass');
   * ?>
   * @endcode
   *
   * @param string $klass
   *  The name of the class to bind to.
   * @param string $event
   *  The name of the event to listen for.
   * @param callable $callable
   *  The callback to execute when $klass fires $event.
   * @return
   *  The config instance.
   */
  public static function listener($klass, $event, $callable) {
    $i = self::inst();
    //$i->config[self::LISTENERS] = arra();
    $i->currentCategory = self::LISTENERS;
    $i->currentName = NULL;

    // Now register the callable.
    $i->config[self::LISTENERS][$klass][$event][] = $callable;

    return $i;
  }

  /**
   * Declare a new datasource.
   *
   * @param string $name
   *  The name of the datasource to add. The name can be referenced by other parts
   *  of the application.
   * @return Config
   *  This object.
   */
  public static function datasource($name) {
    return self::set(self::DATASOURCES, $name);
  }
  /**
   * Declare a new logger.
   *
   * Fortissimo can use numerous loggers. You can declare
   * one or more loggers in your configuration.
   *
   * @param string $name
   *  The name of the logger. This is for other parts of the application
   *  to reference.
   * @return Config
   *  The object.
   */
  public static function logger($name) {
    return self::set(self::LOGGERS, $name);
  }
  /**
   * Declare a new cache.
   *
   * @param string $name
   *  The name of the cache. Caches can be referenced by name in other parts of
   *  the application.
   * @return Config
   *  The object.
   */
  public static function cache($name) {
    return self::set(self::CACHES, $name);
  }

  private static function set($cat, $name) {
    $i = self::inst();
    $i->currentCategory = $cat;
    $i->currentName = $name;
    $i->config[$cat][$name] = array();
    return $i;
  }

  /**
   * Create a new configuration from the given array.
   *
   * EXPERTS: This provides a mechanism for passing in an array instead
   * of executing a fluent chain. It overwrites the current configuration,
   * and so should be used with extreme caution.
   *
   * @param array $config
   *  A complete configuration array.
   * @return Config
   *  This object.
   */
  public static function initialize(array $config = NULL) {
    self::$instance = new Config();
    if (!is_null($config)) self::$instance->config = $config;
    return self::$instance;
  }

  /**
   * Get the complete configuration array.
   *
   * This returns a datastructure that represents the configuration for the system.
   *
   * @return array
   *  The configuration.
   */
  public static function getConfiguration() {
    return self::inst()->config;
  }

  /**
   * Get an instance of the configuration Config object.
   *
   * This controls access to the singleton.
   *
   * @return Config
   *  An instance of the Config object.
   */
  private static function inst() {
    if (empty(self::$instance)) {
      self::$instance = new Config();
    }
    return self::$instance;
  }

  /**
   * Config is a singleton.
   */
  private function __construct() {
    $this->config = array(
      self::REQUESTS => array(),
      self::LOGGERS => array(),
      self::CACHES => array(),
      self::PATHS => array(),
      self::GROUPS => array(),
      self::DATASOURCES => array(),
      self::LISTENERS => array(),
      self::REQUEST_MAPPER => NULL,
    );
  }
  public function usesGroup($name) {
    if ($this->currentCategory = self::REQUESTS) {
      // In PHP, ths will copy the array. But any objects in the array
      // will not be cloned. Don't know if that is a problem.
      $group = $this->config[self::GROUPS][$name];
      $cat = $this->currentCategory;
      $name = $this->currentName;
      $this->config[$cat][$name] += $group;
      /*
      foreach ($group as $command => $desc) {
        $this->config[$cat][$name][$command] = $desc;
      }
      */
    }
    return $this;
  }
  public function doesCommand($name) {
    $this->commandName = $name;
    $this->config[$this->currentCategory][$this->currentName][$this->commandName] = array();
    return $this;
  }
  public function whichInvokes($className) {
    switch ($this->currentCategory) {
      case self::LOGGERS:
      case self::CACHES:
      case self::DATASOURCES:
        $this->config[$this->currentCategory][$this->currentName]['class'] = $className;
        break;
      case self::REQUESTS:
      case self::GROUPS:
        $this->config[$this->currentCategory][$this->currentName][$this->commandName]['class'] = $className;

        // We need to bind global listeners to each request that invokes the class.
        if (!empty($this->config[self::LISTENERS][$className])) {
          foreach($this->config[self::LISTENERS][$className] as $event => $callable) {
            $this->config[$this->currentCategory][$this->currentName][$this->commandName]['listeners'][$event] = $callable;
          }
        }
        break;
      default:
        $msg = 'Tried to add a class to ' . $this->currentCategory;
        throw new FortissimoConfigurationException($msg);
    }
    return $this;
  }
  /**
   * Set a parameter for a class.
   */
  public function withParam($param) {
    $this->currentParam = $param;
    $cat = $this->currentCategory;
    $name = $this->currentName;
    switch ($this->currentCategory) {
      case self::LOGGERS:
      case self::CACHES:
      case self::DATASOURCES:
        $this->config[$cat][$name]['params'][$param] = NULL;
        break;
      case self::REQUESTS:
      case self::GROUPS:
        $this->config[$cat][$name][$this->commandName]['params'][$param] = NULL;
        break;
      default:
        $msg = 'Tried to add a param to ' . $this->currentCategory;
        throw new FortissimoConfigurationException($msg);
    }
    return $this;
  }
  public function bind($eventName, $callable) {
    $cat = $this->currentCategory;
    $name = $this->currentName;
    switch ($cat) {
      case self::GROUPS:
      case self::REQUESTS:
        $this->config[$cat][$name][$this->commandName]['listeners'][$eventName][] = $callable;
        break;
      default:
        $msg = 'Tried to add an event listener to ' . $this->currentCategory;
        throw new FortissimoConfigurationException($msg);
    }
    return $this;
  }
  /**
   * Sets a default value for a param.
   */
  public function whoseValueIs($value) {
    $param = $this->currentParam;
    $cat = $this->currentCategory;
    $name = $this->currentName;
    switch ($this->currentCategory) {
      case self::LOGGERS:
      case self::CACHES:
      case self::DATASOURCES:
        $this->config[$cat][$name]['params'][$param]['value'] = $value;
        break;
      case self::REQUESTS:
      case self::GROUPS:
        $this->config[$cat][$name][$this->commandName]['params'][$param]['value'] = $value;
        break;
      default:
        $msg = 'Tried to add a param value to ' . $this->currentCategory;
        throw new FortissimoConfigurationException($msg);
    }
    return $this;
  }
  /**
   * Indicates where Fortissimo should retrieve this param's value from.
   *
   * For examples, see Fortissimo::fetchParameterFromSource().
   *
   * @param string $source
   *  A string indicating where Fortissimo should look for parameter values.
   */
  public function from($source) {
    $param = $this->currentParam;
    $cat = $this->currentCategory;
    $name = $this->currentName;
    switch ($this->currentCategory) {
      case self::LOGGERS:
      case self::CACHES:
      case self::DATASOURCES:
        $this->config[$cat][$name]['params'][$param]['from'] = $source;
        break;
      case self::REQUESTS:
      case self::GROUPS:
        $this->config[$cat][$name][$this->commandName]['params'][$param]['from'] = $source;
        break;
      default:
        $msg = 'Tried to add a param value to ' . $this->currentCategory;
        throw new FortissimoConfigurationException($msg);
    }
    return $this;
  }
  /*
  public function andCachesInto($name) {
    switch ($this->currentCategory) {
      case self::REQUESTS:
      case self::GROUPS:
        $this->config[$cat][$name][$this->commandName]['cache'] = $name;
        break;
      default:
        $msg = 'Tried to add a cache handler to ' . $this->currentCategory;
        throw new FortissimoConfigurationException($msg);
    }
  }
  */
  /**
   * Turn on or off explaining for a request.
   */
  public function isExplaining($boolean = FALSE) {
    if ($this->currentCategory == self::REQUESTS) {
      $cat = $this->currentCategory;
      $name = $this->currentName;
      $this->config[$cat][$name]['#explaining'] = $boolean;
    }
    return $this;
  }
  /**
   * Turn on or off caching for a request or command.
   *
   * Command-based caching caches the results of just a specific command. It makes it
   * possible to have certain parts of a request be cached while not caching the entire
   * request.
   *
   * @code
   * <?php
   * Config::request('foo')
   *   ->doesCommand('bar')
   *   ->whichInvokes('MyBarClass')
   *   ->whichUses('baz')->whoseValueIs('lurp')
   *   ->isCaching(TRUE);
   * ?>
   *
   * Request-based caching (EXPERIMENTAL) caches the output of an entire request.
   *
   * @code
   * <?php
   * Config::request('foo')->isCaching(TRUE);
   * ?>
   * @endcode
   *
   * @param boolean $boolean
   *  TRUE to turn on caching, FALSE to disable caching.
   */
  public function isCaching($boolean = TRUE) {
    if ($this->currentCategory == self::REQUESTS) {
      $cat = $this->currentCategory;
      $name = $this->currentName;
      $this->config[$cat][$name]['#caching'] = $boolean;
/*
      if (!empty($this->commandName)) {
        $this->config[$cat][$name][$this->commandName]['cache'] = $boolean;
      }
      else {
        $this->config[$cat][$name]['#caching'] = $boolean;
      }

    }
    // Add caching in group commands.
    elseif ($this->currentCategory == self::GROUPS && !empty($this->commandName)) {
      $this->config[$cat][$name][$this->commandName]['cache'] = $boolean;
*/
    }
    return $this;

  }
}
/**
 * The request mapper receives some part of a string or URI and maps it to a Fortissimo request.
 *
 * Mapping happens immediately before request handling (see Fortissimo::handleRequest()).
 * Typically, datasources and loggers are available by this point.
 *
 * Custom request mappers can be created by extending this one and then configuring commands.php
 * accordingly.
 *
 * @code
 * <?php
 * Config::useRequestMapper('ClassName');
 * ?>
 * @endcode
 *
 * For a user-oriented description, see Config::useRequestMapper().
 */
class FortissimoRequestMapper {

  protected $loggerManager;
  protected $cacheManager;
  protected $datasourceManager;

  /**
   * Construct a new request mapper.
   *
   *
   *
   * @param FortissimoLoggerManager $loggerManager
   *  The logger manager.
   * @param FortissimoCacheManager $cacheManager
   *  The cache manager.
   * @param FortissimoDatasourceManager $datasourceManager
   *  The datasource manager
   */
  public function __construct($loggerManager, $cacheManager, $datasourceManager) {
    $this->loggerManager = $loggerManager;
    $this->cacheManager = $cacheManager;
    $this->datasourceManager = $datasourceManager;
  }


  /**
   * Map a given string to a request name.
   *
   * @param string $uri
   *  For web apps, this is a URI passed from index.php. A commandline app may pass the request
   *  name directly.
   * @return string
   *  The name of the request to execute.
   */
  public function uriToRequest($uri) {
    return $uri;
  }

  /**
   * Map a request into a URI (usually a URL).
   *
   * This takes a request name and transforms it into an absolute URL.
   */
  public function requestToUri($request = 'default', $params = array(), $fragment = NULL) {
    // base
    $baseURL = $this->baseURL();
    $fragment = empty($fragment) ? '' : '#' . $fragment;

    $buffer = $baseURL . $fragment;


    // FIXME: Need to respect Apache rewrite rules.
    if ($request != 'default') $params['ff'] = $request;

    if (!empty($params)) {
      // XXX: Do & need to be recoded as &amp; here?
      $qstr = http_build_query($params);
      $buffer .= '?' . $qstr;
    }

    return $buffer;
  }

  /**
   * The canonical host name to be used in Fortissimo.
   *
   * By default, this is fetched from the $_SERVER variables as follows:
   * - If a Host: header is passed, this attempts to use that ($_SERVER['HTTP_HOST'])
   * - If no host header is passed, server name ($_SERVER['SERVER_NAME']) is used.
   *
   * This can be used in URLs and other references.
   *
   * @return string
   *  The hostname.
   */
  public function hostname() {
    return !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
  }

  /**
   * Get the base URL for this instance.
   *
   * @return string
   *  A string of the form 'http[s]://hostname[:port]/[base_uri]'
   */
  public function baseURL() {
    $uri = empty($_SERVER['REQUEST_URI']) ? '/' : $_SERVER['REQUEST_URI'];
    $host = $this->hostname();
    $scheme = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';

    $default_port = empty($_SERVER['HTTPS']) ? 80 : 443;

    if ($_SERVER['SERVER_PORT'] != $default_port) {
      $host .= ':' . $_SERVER['SERVER_PORT'];
    }

    return $scheme . $host . $uri;
  }
}

// End defgroup.
/** @} */
/** @defgroup Fortissimo Fortissimo
 * Features that are included in Fortissimo by default.
 */
/** @page Fortissimo
  * Fortissimo is a PHP application-building framework designed for performance, ease of
  * development, and flexibility.
  *
  * Instead of using the MVC pattern, Fortissimo uses a pattern much more suited to web
  * development: Chain of Command.
  *
  * In a "chain of command" (CoC) pattern, we map a <em>request</em> to a series of
  * <em>commands</em>. Each command is executed in sequence, and each command can build off of the
  * results of the previous commands.
  *
  * If you are new to Fortissimo, you should get to know the following:
  *
  * - commands.php: The configuration file.
  * - BaseFortissimoCommand: The base command that most of your classes will extend.
  *
  * Take a look at the built-in Fortissimo commands in src/core/Fortissimo. In particular,
  * the FortissimoPHPInfo command is a good starting point, as it shows how to build a command
  * with parameters, and it simply outputs phpinfo().
  *
  * Learn more:
  * - Read QUICKSTART.mdown to get started right away
  * - Read the README.mdown in the documentation
  * - Take a look at Fortissimo's unit tests
  *
  * @section getting_started Getting Started
  *
  * To start a new project, see the documentation in the README file. It explains how to run
  * the command-line project generator, which will stub out your entire application for you.
  *
  * Once you have a base application, you should edit commands.php. While you can configure
  * several things there (loggers, caches, include paths, etc.), the main purpose of this file
  * is to provide a location to map a request to a chain of commands.
  *
  * For the most part, developing a Fortissimo application should consist of only a few main tasks:
  * define your requests in commands.php, and create commands by writing new classes that
  * extend BaseFortissimoCommand.
  *
  * Your commands should go in src/includes/. As long as the classname and file name are the same,
  * Fortissimo's autoloader will automatically find your commands and load them when necessary.
  *
  * @section default_facilities_explained Default Facilities
  *
  * Fortissimo provides several facilities that you can make use of:
  *
  * - Datasources: Fortissimo provides a facility for declaring and working with various data
  *  storage systems such as relational SQL databases and NoSQL databases like MongoDB or even
  *  Memcached. Fortissimo comes with support for Mongo DB (FortissimoMongoDatasource) and
  *  PDO-based SQL drivers (FortissimoPDODatasource). Writing custom datasources is usally trivial.
  * - Loggers: Fortissimo has a pluggable logging system with built-in loggers for printing
  *  straight to output (FortissimoOutputInjectionLogger), an array for later retrieval
  *  (FortissimoArrayInjectionLogger), or to a system logger (FortissimoSyslogLogger).
  * - Caches: Fortissimo supports a very robust notion of caches: Requests can be cached, and
  *  any command can declare itself cacheable. Thus, individual commands can cache data. In
  *  addition, the caching layer is exposed to commands, which can cache arbitrary data. Extending
  *  the caching system is trivial. A PECL/Memcache implementation is provided in
  *  FortissimoMemcacheCache.
  * - Request Mapper: With the popularity of search-engine-friendly (SEF) URLs, Fortissimo provides
  *  a generic method by which application developers can write their own URL mappers. The
  *  default FortissimoRequestMapper provides basic support for mapping a URL to a request. You
  *  can extend this to perform more advanced URL handling, including looking up path aliases
  *  in a datasource.
  * - Include Paths: By default, Fortissimo searches the includes/ directory for your source code.
  *  Sometimes you will want it to search elsewhere. Use include paths to add new locations for
  *  Fortissimo to search. This is done with Config::includePath().
  *
  * @section fortissimo_license License
  Fortissimo
  Matt Butcher <mbutcher@aleph-null.tv>
  Copyright (C) 2009, 2010 Matt Butcher

  Permission is hereby granted, free of charge, to any person obtaining a copy
  of this software and associated documentation files (the "Software"), to deal
  in the Software without restriction, including without limitation the rights
  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
  copies of the Software, and to permit persons to whom the Software is
  furnished to do so, subject to the following conditions:

  The above copyright notice and this permission notice shall be included in
  all copies or substantial portions of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
  THE SOFTWARE.
  *
