<?php
/**
 * @file
 * The cache manager.
 */
namespace Fortissimo\Cache;

/**
 * Manage caches.
 *
 * This manages top-level Fortissimo::RequestCache. Just as with
 * Fortissimo::LoggerManager, a Fortissimo::Cache::Manager can manage
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
class Manager {
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
