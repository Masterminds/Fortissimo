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
    $this->assertEquals('bar', $cxt->get('test3.param'));

    // Test with sections
    $reg->route('ini')->does('\Fortissimo\Command\Context\AddINI', 'i')
      ->using('file', __DIR__ . '/../../test.ini')
      ->using('process_sections', TRUE)
      ;

    $runner = $this->runner($reg);
    $cxt = $runner->run('ini');

    $this->assertEquals('foo', $cxt->get('test.param'));
    $this->assertEquals('long text', $cxt->get('test2.param'));
    $this->assertEquals('foo', $cxt->get('test3.param'));
    $example = $cxt->get('example');
    $this->assertEquals('bar', $example['test3.param']);

    // Test with one named section
    $reg->route('ini')->does('\Fortissimo\Command\Context\AddINI', 'i')
      ->using('file', __DIR__ . '/../../test.ini')
      ->using('section', 'example')
      ;

    $runner = $this->runner($reg);
    $cxt = $runner->run('ini');

    $this->assertNull($cxt->get('test.param'));
    $this->assertEquals('bar', $cxt->get('test3.param'));
    // Test again with Optional.
    $reg->route('noini')->does('\Fortissimo\Command\Context\AddINI', 'i')
      ->using('file', 'DOES_NOT_EXIST.ini')
      ->using('optional', TRUE)
      ;
    $runner = $this->runner($reg);
    $cxt = $runner->run('noini');

    $this->assertEmpty($cxt->get('noini'));
  }
}
