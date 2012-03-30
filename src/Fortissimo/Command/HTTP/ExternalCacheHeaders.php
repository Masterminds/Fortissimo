<?php
/** @file
 *
 * This provides cache control headers for external clients, proxies, and 
 * caching servers. It uses the standard headers defined by HTTP 1.1, along
 * with minimalistic support for legacy caches.
 *
 * ExternalCacheHeaders is a BaseFortissimoCommand class.
 *
 * Created by Matt Butcher on 2011-03-03.
 */
namespace Fortissimo\Command\HTTP;
/**
 * Provide cache control headers for Fortissimo.
 *
 * External caches, including Varnish, Akamai, and local browser caches, inspect specific
 * HTTP headers to determine whether or not to cache things. This module provides support
 * for generating headers.
 *
 * @author Matt Butcher
 */
class ExternalCacheHeaders extends \Fortissimo\Command\Base {

  /**
   * The date format for an Expires header.
   */
  const EXPIRES_DATE_FORMAT = 'D, d M Y H:i:s \G\M\T';

  public function expects() {
    return $this
      ->description('Generate HTTP headers for cache control.')
      ->usesParam('no_cache', 'Explicitly set headers to prevent caching.')
      ->withFilter('boolean')
      ->whichHasDefault(FALSE)

      ->usesParam('ttl', 'Time to live (in seconds). Defaults to two minutes.')
      ->withFilter('number_int')
      ->whichHasDefault(120)

      //->usesParam('name', 'desc')
      //->withFilter('string')
      //->whichIsRequired()
      //->whichHasDefault('some value')
      ->andReturns('An integer timestamp indicating when this object expires.')
    ;
  }

  public function doCommand() {

    // If no_cache is TRUE, we set the nocache headers and return.
    if ($this->isNotCacheable() || $this->param('no_cache', FALSE)) {
      return $this->noCacheHeaders();
    }
    return $this->cacheHeaders();
  }

  /**
   * Generate cache headers.
   *
   * This generates headers for pages that should  be cached.
   *
   * @return integer
   *  The timestamp when this item should expire.
   */
  public function cacheHeaders() {
    // Otherwise we set the cache headers.
    $ttl = $this->param('ttl', 120);
    $exp_time = $_SERVER['REQUEST_TIME'] + $ttl;
    $exp_date = gmdate(self::EXPIRES_DATE_FORMAT,$exp_time);

    header(sprintf('Cache-Control: max-age=%d, must-revalidate, public', $ttl));
    header(sprintf('Expires: %s', $exp_date));

    return $exp_time;
  }

  /**
   * Sets headers to tell caches not to cache.
   *
   * This is called when the no_cache param is set to TRUE.
   *
   * @return integer
   *   Always returns 0, since the object should always be treated as expired.
   */
  public function noCacheHeaders() {
    $headers = array(
      'Pragma: no-cache',
      'Age: 0',
      'Cache-Control: no-cache, must-revalidate, maxage=0' 
    );
    foreach($headers as $header) {
      header($header, TRUE);
    }

    return 0;
  }

  /**
   * Indicate if this request cannot be cached.
   *
   * This tests whether anything in the nature of the request itself prevents it 
   * from being cached.
   *
   * Certain HTTP requests cannot be cached. For example, POST, DELETE, and PUT 
   * requests can never be cached.
   *
   * @return boolean
   *  TRUE if this request cannot be cached, false otherwise.
   */
  public function isNotCacheable() {
    $cacheable_methods = array(
      'GET' => 1,
      'HEAD' => 1,
    );

    return empty($cacheable_methods[$_SERVER['REQUEST_METHOD']]);

  }

}

