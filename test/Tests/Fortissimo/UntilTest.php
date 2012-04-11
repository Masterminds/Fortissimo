<?php
namespace Fortissimo\Tests;


require_once 'TestCase.php';

/**
 * @group command
 */
class UntilTest extends TestCase {
  public function testDoCommand() {

    $r = $this->registry();
    $r->route('@inner')
      ->does('\Fortissimo\Command\Util\Increment', 'up')
      ->using('startWith', 0)->from('cxt:up')
      ;
    $r->route('outer')
      ->does('\Fortissimo\Command\Util\Until', 'till')
      ->using('request', '@inner')
      ->using('condition', function ($cxt) { $i = $cxt->get('up', 0); return $i == 3; })
      ->using('allowInternal', TRUE)
      ;

    $runner = $this->runner($r);
    $res = $runner->run('outer');

    $this->assertEquals(3, $res->get('up'));
  }

  /**
   * @expectedException \Fortissimo\RequestNotFoundException
   */
  public function testFailsOnInner() {
    $r = $this->registry(); //new \Fortissimo\Registry('test');
    $r->route('@inner')
      ->does('\Fortissimo\Command\Util\Increment', 'up')
      ->using('startWith', 0)->from('cxt:up')
      ;
    $r->route('outer')
      ->does('\Fortissimo\Command\Util\Until', 'till')
      ->using('request', '@inner')
      ->using('condition', function ($cxt) { $i = $cxt->get('up', 0); return $i == 3; })
      ;

    $runner = $this->runner($r);
    $res = $runner->run('outer');
  }

  public function testNestedRecursion() {
    $r = $this->registry();
    $r->route('@inner')
      ->does('\Fortissimo\Command\Util\Increment', 'up')
      ->using('startWith', 0)->from('cxt:up')
      ;
    $r->route('outer')
      ->does('\Fortissimo\Command\Util\Until', 'till')
      ->using('request', '@inner')
      ->using('condition', function ($cxt) { $i = $cxt->get('up', 0); return $i == 3; })
      ->using('allowInternal', TRUE)
      ->does('\Fortissimo\Command\Util\Increment', '2up')
      ->using('startWith', 0)->from('cxt:2up')
      ;
    $r->route('outerouter')
      ->does('\Fortissimo\Command\Util\Until', 'till')
      ->using('request', 'outer')
      ->using('condition', function ($cxt) { $i = $cxt->get('2up', 0); return $i == 3; })
      ->using('allowInternal', TRUE)
      ;
    $runner = $this->runner($r);
    $res = $runner->run('outerouter');

    $this->assertEquals(3, $res->get('2up'));
    $this->assertEquals(3, $res->get('up'));
  }
}

