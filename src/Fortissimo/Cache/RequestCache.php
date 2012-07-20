<?php
/**
 * @file
 *
 * The interface for a request cache.
 */
namespace Fortissimo\Cache;

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
interface RequestCache {

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
