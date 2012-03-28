<?php
namespace Fortissimo\Tests;
require 'TestCase.php';

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

    $opts = array('--flag1', 'test1', '--flag2', '--flag3', 'test3', 'data');
    $r
    ->route('test1')
      ->does('\Fortissimo\Command\CLI\ParseOptions', 'opts')
        ->using('optionSpec', $spec)
        ->using('options', $opts)
        ->using('help', 'OH HAI')

    ->route('test2')
      ->does('\Fortissimo\Command\ParseOptions', 'opts')
        ->using('optionSpec', $spec)
        ->using('options', $opts)
        ->using('offset', 1)
    ;


    $runner = $this->runner($r);

    $cxt = $runner->run('test1');

    $this->assertNotEmpty($cxt);
  }

}
