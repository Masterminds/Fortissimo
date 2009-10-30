<?php
/**
 * Unit tests for the SimpleFortissimoCommand class.
 */

require_once 'PHPUnit/Framework.php';
require_once 'Fortissimo/skel/src/Fortissimo.php';

class BaseFortissimoCommandTest extends PHPUnit_Framework_TestCase {
  const config = './test/test_commands.xml';
  
  public function testExpects() {
    //$ff = new FortissimoHarness(self::config);
    $cmd = new SimpleCommandTest('test');
    $expectations = $cmd->expects();
    
    $this->assertEquals(3, count($expectations));
    $this->assertEquals(6, $expectations['testNumeric']['type']);
    $this->assertEquals('A test string.', $expectations['testString']['description']);
    $this->assertTrue($expectations['testNumeric2']['validate'] instanceof FortissimoValidator, 'Object is validator.');
  }
  
  public function testDoRequest() {
    $ff = new FortissimoHarness(self::config);
    $ff->handleRequest('testBaseFortissimoCommand1');
    
    $cxt = $ff->getContext();
    
    // Check that the command's value equals 7.
    $this->assertEquals(7, $cxt->get('simpleCommandTest1'));
  }
  
}

class SimpleValidatorTest implements FortissimoValidator {
  
  public function validate($name, $type, $value) {
    if (!is_float($value)) throw new FortissimoException(sprintf('Expected float, but got non-float %s.', $value));
    return $value * 2;
  }
}

class SimpleCommandTest extends BaseFortissimoCommand {
  
  public function expects() {
    return array(
      'testString' => array(
        'type' => self::string_type,
        'description' => 'A test string.'
      ),
      'testNumeric' => array(
        'type' => self::float_type,
        'description' => 'A test numeric value',
      ),
      'testNumeric2' => array(
        'type' => self::float_type,
        'description' => 'A test numeric value',
        'validate' => new SimpleValidatorTest(),
      ),
    );
  }
  
  public function doCommand() {
    $param = $this->parameters;
    if ($param['testString'] != 'String1') throw new Exception(sprintf('Expected String1, got %s', print_r($param, TRUE)));
    if ($param['testNumeric'] != 3.5) throw new Exception('Expected float 3.5');
    if ($param['testNumeric2'] != 7) throw new Exception('Expected float to be 7');
    
    return $param['testNumeric2'];
  }
}