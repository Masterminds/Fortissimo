<?php
namespace Fortissimo\Tests;
$base = dirname(__DIR__);
require_once $base . '/TestCase.php';

/**
 * @group command
 */
class RunRunnerTest extends TestCase {

  public function testDoCommand() {

    $inner = new \Fortissimo\Registry('inner registry');
    $inner->route('test2')
      ->does('\Fortissimo\Command\Util\Head', 'head')
      ->using('list', array(1, 2, 3))
      ;

    $r = $this->registry();
    $r->route('test')
      ->does('\Fortissimo\Command\CLI\RunRunner', 'internal')
      ->using('route', 'test2')
      ->using('registry', $inner)
      ;

    $runner = $this->runner($r);

    $cxt = $runner->run('test');

    $expects = $cxt->get('head');

    $this->assertEquals(1, $expects);

  }
}
