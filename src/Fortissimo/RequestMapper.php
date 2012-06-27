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
 * $registry->useRequestMapper('ClassName');
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
   * The base path to the application.
   *
   * The base path is calculated as the URI up to
   * the currently executing script. This method can
   * adjust to Apache mod_rewrite.
   *
   * @param string $uri
   *   The URI. If this is not supplied, $_SERVER['REQUEST_URI']
   *   is used.
   * @retval string
   *   The base path.
   */
  public function basePath($uri = NULL) {
    if (!isset($uri)) {
      $uri = $_SERVER['REQUEST_URI'];
    }

    $fullPath = parse_url($uri, PHP_URL_PATH);
    $script = dirname($_SERVER['SCRIPT_NAME']);
    $basedir = substr($fullPath, 0, strlen($script));
    // printf("Full: %s, Script: %s, Base: %s\n", $fullPath, $script, $basedir);

    return $basedir;
  }

  /**
   * Get the local path.
   *
   * This returns the path relative to the application's
   * base path.
   *
   * Generally, path is calculated from the request URI.
   *
   * Typically, this is useful in cases where the URL does not match the 
   * script name, such as cases where mod_rewrite was used.
   *
   * @param string $uri
   *   The URI. If not given, $_SERVER['REQUEST_URI'] is used.
   * @param string $basepath
   *   The base path to the app. If not supplied, this is
   *   computed from basePath().
   * @retval string
   *   The computed local path. This will be any part of the path
   *   that appears after the base path.
   */
  public function localPath($uri = NULL, $basepath = NULL) {
    if (!isset($uri)) {
      $uri = $_SERVER['REQUEST_URI'];
    }

    if (!isset($basepath)) {
      $basepath = $this->basePath($uri);
    }

    return substr($uri, strlen($basepath));

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
    //$uri = empty($_SERVER['REQUEST_URI']) ? '/' : $_SERVER['REQUEST_URI'];
    $uri = $this->basePath();
    $host = $this->hostname();
    $scheme = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';

    $default_port = $this->isHTTPS() ? 443 : 80;

    if ($_SERVER['SERVER_PORT'] != $default_port) {
      $host .= ':' . $_SERVER['SERVER_PORT'];
    }

    return $scheme . $host . $uri;
  }

  /**
   * Tries to determine if the request is over SSL.
   *
   * Checks the HTTPS flag in $_SERVER and looks for the 
   * X-Forwarded-Proto proxy header.
   *
   * @retval boolean
   *   TRUE if this is HTTPS, FALSE otherwise.
   */
  public function isHTTPS() {
    // Local support.
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') {
      return TRUE;
    }
    // Proxy support.
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https') {
      return TRUE;
    }
    return FALSE;

  }
}
