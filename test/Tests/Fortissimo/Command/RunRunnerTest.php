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

    $r->route('dummy')
      ->does('\Fortissimo\Command\Context\AddToContext')
        ->using('here', TRUE)
      ;
    $r->route('testNoReg')
      ->does('\Fortissimo\Command\CLI\RunRunner', 'internal')
      ->using('route', 'dummy')
      ;

    $runner = $this->runner($r);
    $cxt = $runner->run('testNoReg');

    $this->assertTrue($cxt->get('here'));

    /*
    $r->route('testInclude')
      ->does('\Fortissimo\Command\CLI\RunRunner', 'internal')
      ->using('registry', 'test/RunRunner_registry.php')
      ->using('route', 'foo')
      ;

    $runner = $this->runner($r);
    $cxt = $runner->run('testInclude');

    $this->assertEquals(2, $cxt->get('bar'));
     */
  }
}
