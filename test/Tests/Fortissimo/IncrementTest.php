<?php
namespace Fortissimo\Tests;


require_once 'TestCase.php';

/**
 * @group command
 */
class IncrementTest extends TestCase {

  public function testDoCommand() {
    $r = $this->registry();

    $r->route('test')->does('\Fortissimo\Command\Util\Increment', 'inc');
    $r->route('test2')
      ->does('\Fortissimo\Command\Util\Increment', 'inc')
      ->using('startWith', 4)
      ->using('incrementBy', 5)
      ;
    $r->route('test3')
      ->does('\Fortissimo\Command\Util\Increment', 'inc')
      ->using('startWith', 4)
      ->using('incrementBy', -5)
      ;
    $r->route('test4')
      ->does('\Fortissimo\Command\Util\Increment', 'inc')
      ->using('startWith', 0)
      ->using('incrementBy', 0)
      ;

    $runner = $this->runner($r);
    $cxt = $runner->run('test');

    $this->assertEquals(1, $cxt->get('inc'));


    $cxt = $runner->run('test2');
    $this->assertEquals(9, $cxt->get('inc'));

    $cxt = $runner->run('test3');
    $this->assertEquals(-1, $cxt->get('inc'));

    $cxt = $runner->run('test4');
    $this->assertEquals(0, $cxt->get('inc'));
  }
}
