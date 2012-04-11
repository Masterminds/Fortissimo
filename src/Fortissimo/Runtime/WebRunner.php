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
      $cxt->log($nfe, Fortissimo::LOG_USER);
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
