<?php
/**
 * @file
 *
 * The HTTP/HTTPS-optimized Runtime. It should work with SPDY, too.
 */
namespace Fortissimo\Runtime;

/**
 * The web runner.
 *
 * This is optimized for HTTP/HTTPS/SPDY transmission.
 *
 * The following characteristics differentiate this runner form others:
 *
 * - This will attempt to capture some Exceptions and re-route them
 *   to appropriate handlers.
 *   * Fortissimo::RequestNotFoundException is re-routed to a @404
 *     route if such a route exists.
 * - The following values are added to the initial context:
 *   * fullPath: The complate REQUEST_URI
 *   * basePath: The base real path
 *   * localPath: The portion of the path that does not correspond to a 
 *      file system. See Fortissimo::RequestMapper::localPath().
 */
class WebRunner extends Runner {

  public function run($route = 'default') {
    if (empty($this->registry)) {
      throw new \Fortissimo\Runtime\Exception('No registry found.');
    }

    $cxt = $this->initialContext();
    //$cxt->attachFortissimo($this->ff);
    $cxt->attachRegistry($this->registry);

    try {
      $this->ff->handleRequest($route, $cxt, $this->allowInternalRequests);
    }
    catch(\Fortissimo\RequestNotFoundException $nfe) {
      $cxt->log($nfe, \Fortissimo::LOG_USER);
      if ($this->ff->hasRequest($route, '@404')) {
        return $this->run('@404');
      }
      else {
        $this->generateErrorHeader('404 Not Found');
        print "File Not Found";
      }
    }
    return $cxt;
  }

  public function initialContext() {
    $cxt = $this->ff->createBasicContext();
    $this->addPathsToContext($cxt);
    return $cxt;
  }

  protected function generateErrorHeader($msg) {
    if (strpos($_SERVER['GATEWAY_INTERFACE'], 'CGI') !== FALSE) {
      header('Status: ' . $msg);
    }
    else {
      header('HTTP/1.1 ' . $msg);
    }
  }

  /**
   * Add paths to the context.
   *
   * This adds standard URI paths into the context.
   */
  protected function addPathsToContext($cxt) {
    $mapper = $cxt->getRequestMapper();

    $fullPath = $_SERVER['REQUEST_URI'];
    $basePath = $mapper->basePath($fullPath);
    $localPath = $mapper->localPath($fullPath, $basePath);
    $baseURL = $mapper->baseURL();

    $cxt->add('fullPath', $fullPath);
    $cxt->add('basePath', $basePath);
    $cxt->add('localPath', $localPath);
    $cxt->add('baseURL', $baseURL);
  }

}
