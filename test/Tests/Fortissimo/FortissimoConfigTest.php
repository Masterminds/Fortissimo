<?php
/**
 * Unit tests for the FortissimoConfig class.
 */

require_once 'PHPUnit/Framework.php';
require_once 'Fortissimo/skel/src/Fortissimo.php';

class FortissimoConfigTest extends PHPUnit_Framework_TestCase {
  
  const command = './test/test_commands.xml';
  
  public function testConstructor() {
    
    $fc = new FortissimoConfig(self::command);
    
    $this->assertTrue($fc instanceof FortissimoConfig);
  }
  
  /**
   * @expectedException QueryPathException
   */
  public function testConstructorParseError() {
    $fc = new FortissimoConfig('<?xml version="1.0"?><foo><bar></foo>');
  }
  
  public function testGetConfig() {
    $fc = new FortissimoConfig(self::command);
    $qp = $fc->getConfig();
    $this->assertTrue($qp instanceof QueryPath, 'Returned a QueryPath.');
    $this->assertEquals(1, $qp->size(), 'Has one root element.');
  }
  
  public function testIsLegalRequestName() {
    
    $good = array('a', '1', 'a1', 'a-1', '1_a', 'abcdefghijklmnop1234567-_', '-_', 'ABC');
    foreach ($good as $a) {
      $this->assertTrue(FortissimoConfig::isLegalRequestName($a), "$a is a legal name");
    }
    
    $bad = array('', ' ', '/', '|', '\\', '+', 'ø', 'å', '&', 'a*b', 'abc=def', 'url:plus', 'url?plus');
    foreach ($bad as $a) {
      $this->assertFalse(FortissimoConfig::isLegalRequestName($a), "$a is an illegal name");
    }
  }
  
  public function testHasRequest() {
    $requestName = 'item';
    $fc = new FortissimoConfig(self::command);
    
    $this->assertTrue($fc->hasRequest($requestName), "Has a request named $request.");
  }
  
  public function testGetRequest() {
    $requestName = 'dummy';
    $fc = new FortissimoConfig(self::command);
    
    $req = $fc->getRequest($requestName);
    
    $this->assertTrue(is_array($req), 'Request is an array.');
  }
  
}

class AbstractCommandMock implements FortissimoCommand {
  protected $name = NULL;
  protected $cxt = NULL;
  protected $params = NULL;
  
  public function __construct($name) {
    $this->name = $name;
  }
  
  public function execute($paramArray, FortissimoExecutionContext $cxt) {
    if ($paramArray['retval'])
      $cxt->put($this->name, $paramArray['retval']);
  }
}

class CommandMockOne extends AbstractCommandMock {
}
class CommandMockTwo extends AbstractCommandMock {
}
class CommandMockThree extends AbstractCommandMock {
}