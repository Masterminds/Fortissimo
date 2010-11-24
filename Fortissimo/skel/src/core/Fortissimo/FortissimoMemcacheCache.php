<?php
/** @file
 * This file contains the definition of a memcache backend for a FortissimoRequestCache.
 */
 
/**
 * Provide access to one or more memcache servers acting as a caching cluster.
 * 
 * This uses pecl/memcache for access to Memcached servers. This does not connect to servers
 * until absolutely necessary. It is designed to be used as a FortissimoRequestCache instance.
 *
 * The purpose of this cache is to cache small, lightweight objects. It does not implement
 * GhettoLocking or any other form of protection to prevent cache stampedes if the server can't
 * quickly generate a replacement.
 *
 * Params:
 *  - server: a server (host:port) or servers (array('host:port', 'host:port')).
 *  - persistent: Whether or not the connections should persist across requests. (Default: FALSE).
 *  - compress: Wheter or not to (zlib) compress records during insertion. This should be turned
 *      on when your records may be larger than 1M.
 *  - isDefault: As with all caches, you can mark this cache as the default by setting the 
 *    isDefault param to TRUE. Only one cache can be set to be the default.
 *
 *  Example: 
 *  
 * @code
 * <?php
 * Config::cache('memcache')
 *   ->whichInvokes('FortissimoMemcacheCache')
 *   ->withParam('servers')
 *     ->whoseValueIs(array('example.com:11211', 'example.com:11212'))
 *   ->withParam('persistent')
 *     ->whoseValueIs(FALSE)
 *   ->withParam('compress')
 *     ->whoseValueIs(FALSE)
 *
 *   ->withParam('isDefault')
 *     ->whoseValueIs(TRUE)
 * ;
 * ?>
 * @endcode
 *
 * @ingroup Fortissimo
 */
class FortissimoMemcacheCache extends FortissimoCache {
  protected $memcache = NULL;
  protected $compress = FALSE;
  
  /**
   * Initialize access to a memcached cluster.
   *
   */
  public function init() {
    
    if (empty($this->params['server'])) {
       throw FortissimoInterruptException('No memcache server was specified, but init was attempted.');
    }
    
    $this->memcache = new Memcache();
    $this->compress = isset($this->params['compress']) 
      && filter_var($this->params['compress'], FILTER_VALIDATE_BOOLEAN);
    
    $servers = $this->params['server'];
    $persist = isset($this->params['persistent']) 
      && filter_var($this->params['persistent'], FILTER_VALIDATE_BOOLEAN);
    
    if (is_string($servers)) {
      $servers = array($servers);
    }
    
    foreach ($servers as $server) {
      list($name, $port) = explode(':', $server, 2);
      $this->memcache->addServer($name, $port, $persist);
    }
  }
  
  public function clear() {
    $this->memcache->flush();
  }
  
  public function delete($key) {
    $this->memcache->delete($key);
  }
  public function set($key, $value, $expires_after = NULL) {
    $value = serialize($value);
    $this->memcache->set($key, $value, $this->compress, $expires_after);
  }
  public function get($key) {
    $value = $this->memcache->get($key, $this->compress);
    return unserialize($value);
  }
}