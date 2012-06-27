<?php
/**
 * Unit tests for the SimpleFortissimoCommand class.
 */

##require_once 'PHPUnit/Framework.php';
##require_once 'Fortissimo/skel/src/Fortissimo.php';
namespace Fortissimo\Tests;
require_once 'TestCase.php';

use \Fortissimo\Registry;

/**
 * @group deprecated.
 */
class BaseFortissimoCommandTest extends TestCase {
  const config = './test/test_commands.php';
  
  public function setUp() { Registry::initialize(); }
  
  public function testExpects() {
    //$ff = new FortissimoHarness(self::config);
    $cmd = new SimpleCommandTest('test');
    $expectations = $cmd->expects();
    
    $params = $expectations->params();
    $this->assertEquals(4, count($params), 'Command has four arguments');
    
    // Since params should be in order, we can shift them off the top:
    $testString = array_shift($params);
    $this->assertEquals('testString', $testString->getName());
    $this->assertEquals('A test string', $testString->getDescription());
    
    $testNumeric = array_shift($params);
    
    $this->assertEquals('testNumeric', $testNumeric->getName());
    
    // Count filters:
    $filters = $testNumeric->getFilters();
    $this->assertEquals(1, count($filters));
    $this->assertEquals('float', $filters[0]['type']);
    $this->assertNull($filters[0]['options']);
    
    // Manually execute a filter:
    $this->assertEquals(7.5, filter_var(7.5, filter_id($filters[0]['type']), NULL));
    
    // Test a failed filter:
    $this->assertFalse(filter_var('matt', filter_id($filters[0]['type']), NULL), 'String is not a float.');
    
    // Test callbacks
    $testNumeric2 = array_shift($params);
    $filters = $testNumeric2->getFilters();
    $this->assertEquals('callback', $filters[0]['type']);
    $this->assertTrue($filters[0]['options']['options'][0] instanceof SimpleValidatorTest, 'Option callback is a SimpleValidatorTest');
    
    $this->assertEquals(7, filter_var(3.5, FILTER_CALLBACK, $filters[0]['options']));
    
  }
  
  public function testDoRequest() {
    $ff = new FortissimoHarness(self::config);
    $ff->handleRequest('testBaseFortissimoCommand1');
    
    $cxt = $ff->getContext();
    
    // Check that the command's value equals 7.
    $this->assertEquals(7, $cxt->get('simpleCommandTest1'));
  }
  
}

class SimpleValidatorTest{
  
  //public function validate($name, $type, $value) {
  public function validate($value) {
    return $value * 2;
  }
}

class SimpleCommandTest extends \Fortissimo\Command\Base {
  
  public function expects() {
    
    return $this
      ->description('A test command')
      
      ->usesParam('testString', 'A test string')
      ->withFilter('string')
      
      ->usesParam('testNumeric', 'A test numeric value')
      ->withFilter('float')
      
      ->usesParam('testNumeric2', 'Another test numeric value')
      ->withFilter('callback', array('options' => array(new SimpleValidatorTest(), 'validate')))
      
      ->usesParam('testInternal', 'Test internal filters')
      ->whichHasDefault('FOO')
      ->withFilter('this', 'internalValidator')
    ;
  }
  
  public function doCommand() {
    $param = $this->parameters;
    if ($this->param('testString') != 'String1') throw new Exception(sprintf('Expected String1, got %s', print_r($param, TRUE)));
    if ($this->param('testNumeric') != 3.5) throw new Exception('Expected float 3.5');
    if ($this->param('testNumeric2') != 7) throw new Exception('Expected float to be 7');
    
    return $this->param('testNumeric2');
  }
  
  public function internalValidator($data) {
    return TRUE;
  }
}
