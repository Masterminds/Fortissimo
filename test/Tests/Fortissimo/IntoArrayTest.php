<?php
namespace Fortissimo\Tests;
require_once 'TestCase.php';

/**
 * @group command
 */
class IntoArrayTest extends TestCase {

  public function testDoCommand() {
    $reg = $this->registry();

    $reg->route('test1')
      ->does('\Fortissimo\Command\Context\AddToContext')
        ->using('foo', 'bar')
        ->using('foo2', 'bar2')
        ->using('foo3', 'bar3')
      ->does('\Fortissimo\Command\Context\IntoArray', 'res')
      ;

    $runner = $this->runner($reg);
    $cxt = $runner->run('test1');

    $res = $cxt->get('res');

    // 'test' plus the three foos.
    $this->assertEquals(4, count($res));
    $this->assertEquals('bar', $res['foo']);


    $reg->route('test2')
      ->does('\Fortissimo\Command\Context\AddToContext')
        ->using('foo', 'bar')
        ->using('foo2', 'bar2')
        ->using('foo3', 'bar3')
      ->does('\Fortissimo\Command\Context\IntoArray', 'res')
        ->using('names', array('foo2', 'foo3', 'NO_SUCH_ARG'))
      ;

    $runner = $this->runner($reg);
    $cxt = $runner->run('test2');

    fwrite(STDOUT, print_r($cxt->toArray(), TRUE));

    $res = $cxt->get('res');
    $this->assertEquals(2, count($res));

    // 'test' plus the three foos.
    $this->assertEquals('bar2', $res['foo2']);
    $this->assertFalse(array_key_exists('NO_SUCH_ARG', $res));
  }
}
