<?php
namespace Fortissimo\Tests;
require 'TestCase.php';

class AddToContextTest extends TestCase {
  public function testDoCommand() {
    $reg = $this->registry('test');
    $reg->route('test')
      ->does('\Fortissimo\Command\AddToContext', 'add')
      ->using('test1', 'foo')
      ->using('test2', 'bar')
      ;

    $runner = $this->runner($reg);

    $cxt = $runner->run('test');

    $this->assertEquals('foo', $cxt->get('test1'));
    $this->assertEquals('bar', $cxt->get('test2'));
  }
}
