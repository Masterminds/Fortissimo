<?php
/**
 * @file
 */
namespace Fortissimo;

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
 * App::useRequestMapper('ClassName');
 * ?>
 * @endcode
 *
 * For a user-oriented description, see App::useRequestMapper().
 */
class RequestMapper {

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
