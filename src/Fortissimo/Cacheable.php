<?php
/**
 * @file
 * Fortissimo::Cacheable class.
 */
namespace Fortissimo;

/**
 * Classes that implement this advertise that their output can be cached.
 *
 * Simply implementing this in no way results in the results being cached. There must be a
 * caching mechanism that receives the data and caches it.
 *
 * For example, Fortissimo::Command::Base is capable of understanding Cacheable objects. When
 * a BaseFortissimCommand::doCommand() result is returned, if a cache key can be generated
 * for it, then its results will be cached.
 *
 * To implement and configure caching:
 * - Make your Fortissimo::Command::Base class implement Cacheable
 * - Set up a cache with Config::cache()
 *
 * When the command is executed, its results will be stored in cache. The next time the command
 * is executed, it will first attempt to use the cached copy (unless that copy is gone or
 * expired). If a copy is found, it is returned, otherwise a new copy is generated.
 */
interface Cacheable {

  /**
   * Return a cache key.
   *
   * The key is assumed to uniquely describe a specific piece of data. Prefixes may be added
   * to the key according to the caching manager.
   *
   * If a Cacheable object returns a cache key from this function, the underlying system is
   * considered to be allowed to cache the object's output.
   *
   * Note that the exact data that is cached will be based not on this interface, but on the
   * caching mechanism used. For example, Fortissimo::Command::Base caches the output of the
   * Fortissimo::Command::Base::doCommand() method.
   *
   * @return string
   *  Cache key or NULL if (a) no key can be generated, or (b) this object should not be cached.
   *
   */
  public function cacheKey();

  /**
   * Indicates how long the item should be stored.
   *
   * Implementations of this method return an integer value that indicates how long an item
   * should live in the cache before it is expired.
   *
   * @return int
   *  The duration (in seconds) that this item should be cached. Note that different cache backends
   *  may interpret edge values (0, -1) differently. Returning NULL will result in Fortissimo
   *  using the default for the underlying cache mechanism.
   */
  public function cacheLifetime();

  /**
   * Indicates which cache to use.
   *
   * Fortissimo supports multiple caches, all of which are managed by the Fortissimo::Cache::Manager.
   * This method allows a Cacheable object to declare which cache it uses.
   *
   * Returning NULL will allow the default behavior to take effect.
   *
   * @return string
   *  The name of the cache. If this is NULL, then the default cache will be used.
   */
  public function cacheBackend();


  /**
   * Indicate whether or not the current command is caching.
   *
   * This provides a standard mechanism for indicating whether or not a particular
   * Cacheable instance is allowed to be cached. Implementors can, for example,
   * implement a configuration parameter that will enable or disable caching.
   *
   * Typically, when the request handing subsystem test an object to see if it is
   * able to be cached, the following should all be true:
   *
   * - Fortissimo ought to have a suitable cache provided (e.g. Config::cache())
   * - The command should implement Cacheable
   * - isCaching() should return TRUE
   * - cacheKey() should return a string value
   *
   * Note that this flag is checked after the command is initialized, but before the
   * command is executed. Any changes that the command makes to this value during
   * the command's execution will be ignored.
   *
   * @return boolean
   *  TRUE if this command is in caching mode, FALSE if this command object is
   *  disallowing cached output.
   */
  public function isCaching();
}
