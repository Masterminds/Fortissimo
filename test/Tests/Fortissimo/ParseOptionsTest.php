<?php
namespace Fortissimo\Tests;
require_once 'TestCase.php';

/**
 * @group command
 */
class ParseOptionsTest extends TestCase {

  public function spec() {
    return array(
      '--flag1' => array(
        'help' => 'This is the help for flag 1',
        'value' => TRUE,
      ),
      '--flag2' => array(
        'help' => 'This is the help for flag 2',
        'value' => FALSE,
      ),
      '--flag3' => array(
        'help' => 'This is the help for flag 3',
        'value' => TRUE,
      ),
    );
  }

  public function testDoCommand() {
    $r = $this->registry(__CLASS__);

    $spec = $this->spec();

    $opts = array('command','--flag1', 'test1', '--flag2', '--flag3', 'test3', 'data');
    $r
    ->route('test1')
      ->does('\Fortissimo\Command\CLI\ParseOptions', 'opts')
        ->using('optionSpec', $spec)
        ->using('options', $opts)
        ->using('help', 'OH HAI')

    ->route('test2')
      ->does('\Fortissimo\Command\CLI\ParseOptions', 'opts')
        ->using('optionSpec', $spec)
        ->using('options', $opts)
        ->using('offset', 2)
    ;


    $runner = $this->runner($r);

    $cxt = $runner->run('test1');

    $this->assertNotEmpty($cxt);
    $this->assertEquals('command', $cxt->get('opts-command'));
    $this->assertEquals('test1', $cxt->get('flag1'));
    $this->assertTrue($cxt->get('flag2'));
    $extra = $cxt->get('opts-extra');
    $this->assertEquals('data', $extra[0]);

    $cxt = $runner->run('test2');
    $this->assertEquals('test1', $cxt->get('opts-command'));
    $this->assertFalse($cxt->has('flag1'));

  }

}
