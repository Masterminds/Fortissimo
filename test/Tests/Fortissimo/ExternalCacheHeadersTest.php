<?php
namespace Fortissimo\Tests;
require 'TestCase.php';

class ExternalCacheHeadersTest extends TestCase {

  public function testDoCommand() {
    $reg = $this->registry('test');
    $reg->route('cache')
      ->does('\Fortissimo\Command\HTTP\ExternalCacheHeaders', 'headers')
      ->using('ttl', 1234)
    ->route('nocache')
      ->does('\Fortissimo\Command\HTTP\ExternalCacheHeaders', 'headers')
      ->using('no_cache', TRUE)
      ;

    $runner = $this->runner($reg);
/* FIXME: Need to work around the unit testing framework here -- header() doesn't work.
    $cxt = $runner->run('cache');
    $headers = headers_list();

    $this->assertGreaterThan(2, count($headers));
*/

  }
}
