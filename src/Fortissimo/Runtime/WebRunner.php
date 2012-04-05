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
 */
class WebRunner extends Runner {

  public function intialContext() {
    $cxt = new \Fortissimo\ExecutionContext();
    return $cxt;
  }

  public function run($route = 'default') {
    $cxt = NULL;
    try {
      $cxt = parent::run($route);
    }
    catch(\Fortissimo\RequestNotFoundException $nfe) {
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

  protected function generateErrorHeader($msg) {
    if (strpos($_SERVER['GATEWAY_INTERFACE'], 'CGI') !== FALSE) {
      header('Status: ' . $msg);
    }
    else {
      header('HTTP/1.1 ' . $msg);
    }
  }

}
