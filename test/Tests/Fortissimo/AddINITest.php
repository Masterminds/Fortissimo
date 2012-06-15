<?php
namespace Fortissimo\Tests;
require_once 'TestCase.php';

/**
 * @group command
 */
class AddINITest extends TestCase {
  public function testDoCommand() {
    $reg = $this->registry();

    $reg->route('ini')->does('\Fortissimo\Command\Context\AddINI', 'i')
      ->using('file', __DIR__ . '/../../test.ini')
      ;

    $runner = $this->runner($reg);
    $cxt = $runner->run('ini');

    $this->assertEquals('foo', $cxt->get('test.param'));
    $this->assertEquals('long text', $cxt->get('test2.param'));

    $reg->route('noini')->does('\Fortissimo\Command\Context\AddINI', 'i')
      ->using('file', 'DOES_NOT_EXIST.ini')
      ->using('optional', TRUE)
      ;
    $runner = $this->runner($reg);
    $cxt = $runner->run('noini');

    $this->assertEmpty($cxt->get('noini'));
  }
}
