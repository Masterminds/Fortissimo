<?php
/**
 * Unit tests for the FortissimoExecutionContext class.
 */
namespace Fortissimo\Tests;

use \Fortissimo\ExecutionContext;

require_once 'TestCase.php';

class FortissimoExecutionContextTest extends TestCase {

  public function testConstructor() {
    $cxt = new ExecutionContext();
    $this->assertTrue($cxt instanceof FortissimoExecutionContext);
    
    $cxt = new ExecutionContext(array('foo' => 'bar'));
    $this->assertTrue($cxt instanceof FortissimoExecutionContext);
  }
  
  public function testSize() {
    $cxt = new ExecutionContext(array('foo' => 'bar'));
    $this->assertEquals(1, $cxt->size());
    
    foreach (range(1,10) as $v) $vals['n' . $v] = $v;
    
    $cxt = new ExecutionContext($vals);
    $this->assertEquals(10, $cxt->size());
  }
  
  public function testHas() {
    $cxt = new ExecutionContext(array('foo' => 'bar', 'narf' => 'bargle'));
    $this->assertTrue($cxt->has('narf'));
    $this->assertFalse($cxt->has('bargle'));
  }
  
  public function testGet() {
    $cxt = new ExecutionContext(array('foo' => 'bar'));
    $this->assertEquals('bar', $cxt->get('foo'));
    
    $this->assertNull($cxt->get('not here'));
  }
  
  public function testAdd() {
    $cxt = new ExecutionContext(array('foo' => 'bar'));
    $cxt->add('narf', 'bargle');
    $this->assertEquals('bargle', $cxt->get('narf'));
    
    $cxt->add('foo', 'baz');
    $this->assertEquals('baz', $cxt->get('foo'));
  }
  
  public function testRemove() {
    $cxt = new ExecutionContext(array('foo' => 'bar', 'narf' => 'bargle'));
    $cxt->remove('narf');
    $this->assertEquals(1, $cxt->size());
    $this->assertNull($cxt->get('narf'));
  }
  
  public function testToArray() {
    $initial = array('foo' => 'bar');
    $cxt = new ExecutionContext($initial);
    $this->assertEquals($initial, $cxt->toArray());
  }
  
  public function testFromArray() {
    $initial = array('foo' => 'bar', 'narf' => 'bargle');
    $cxt = new ExecutionContext(array('a' => 'b'));
    $cxt->fromArray($initial);
    $this->assertEquals(2, $cxt->size());
    $this->assertTrue($cxt->has('narf'));
    $this->assertEquals('bargle', $cxt->get('narf'));
  }
  
  public function testIterator() {
    $cxt = new ExecutionContext(array('foo' => 'bar', 'narf' => 'bargle'));
    $count = 0;
    foreach ($cxt as $item=>$val) {
      $this->assertNotNull($item);
      $this->assertNotNull($val);
      ++$count;
    }
    $this->assertEquals(2, $count);
  }
}
