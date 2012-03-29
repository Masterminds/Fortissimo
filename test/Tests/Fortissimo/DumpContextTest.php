<?php
namespace Fortissimo\Tests;
require 'TestCase.php';

class DumpContextTest extends TestCase {

  public function testDoCommand() {
    $reg = $this->registry('test');

    $reg->route('default')->does('\Fortissimo\Command\DumpContext', 'dump');

    $reg->route('test2')
      ->does('\Fortissimo\Command\DumpContext', 'dump')
      ->using('item', 'test')
      ;

    $runner = $this->runner($reg);

    ob_flush();
    ob_start();
    $cxt = $runner->run('default');
    $out = ob_get_clean();

    $this->assertRegExp('/ExecutionContext/',$out);


    ob_flush();
    ob_start();
    $cxt = $runner->run('test2');
    $out = ob_get_clean();

    $this->assertRegExp('/bool\(true\)/',$out);

  }

}
