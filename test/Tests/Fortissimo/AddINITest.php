<?php
namespace Fortissimo\Tests;
require 'TestCase.php';

class AddINITest extends TestCase {
  public function testDoCommand() {
    $reg = $this->registry();

    $reg->route('ini')->does('\Fortissimo\Command\Context\AddINI', 'i')
      ->using('file', __DIR__ . '/../../test.ini')
      ;

    $runner = $this->runner($reg);
    $cxt = $runner->run('ini');

    $this->assertEquals('foo', $cxt->get('test.param'));
    $this->assertEquals('Long text', $cxt->get('test2.param'));
  }
}
