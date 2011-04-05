<?php
/**
 * Unit tests for the FortissimoConfig class.
 */

require_once 'PHPUnit/Framework.php';
require_once 'Fortissimo/skel/src/Fortissimo.php';

class FortissimoConfigTest extends PHPUnit_Framework_TestCase {
  
  const command = './test/test_commands.php';
  
  public function testConstructor() {
    
    $fc = new FortissimoConfig(self::command);
    
    $this->assertTrue($fc instanceof FortissimoConfig);
  }
  
  public function testGetConfig() {
    $fc = new FortissimoConfig(self::command);
    $array = $fc->getConfig();
    $this->assertTrue(is_array($array), 'Returned a configuration array.');
    $this->assertEquals(8, count($array), 'Has eight categories.');
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
    
    $this->assertTrue($req instanceof FortissimoRequest, 'Request is a fortissimo  request.');
    $this->assertTrue($req instanceof IteratorAggregate, 'Request is iterable.');
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
  
  public function isCacheable() {return FALSE;}
}

class CommandMockOne extends AbstractCommandMock {
}
class CommandMockTwo extends AbstractCommandMock {
}
class CommandMockThree extends AbstractCommandMock {
}