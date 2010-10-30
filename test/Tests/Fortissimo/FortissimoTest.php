<?php
/**
 * Unit tests for the Fortissimo class.
 */

require_once 'PHPUnit/Framework.php';
require_once 'Fortissimo/skel/src/Fortissimo.php';

class FortissimoTest extends PHPUnit_Framework_TestCase {
  
  const config = './test/test_commands.xml';
  
  public function testConstructor() {
    $ff = new Fortissimo(self::config);
    
    $this->assertTrue($ff instanceof Fortissimo);
  }
  
  public function testFetchParams() {
    $_GET['foo'] = 'bar';
    $ff = new FortissimoHarness(self::config);
    
    $ff->setParams(array('foo' => 'bar'), 'get');
    
    $bar = $ff->fetchParam('get:foo');
    $this->assertEquals('bar', $bar, 'GET test');
    
    $bar = $ff->fetchParam('g:foo');
    $this->assertEquals('bar', $bar, 'GET test');
    
    $bar = $ff->fetchParam('GET:foo');
    $this->assertEquals('bar', $bar, 'GET test');
    
    $ff->setParams(array('foo' => 'bar'), 'post');
    $bar = $ff->fetchParam('post:foo');
    $this->assertEquals('bar', $bar, 'POST test');
    
    $ff->setParams(array('foo' => 'bar'), 'cookie');
    $bar = $ff->fetchParam('cookie:foo');
    $this->assertEquals('bar', $bar, 'Cookie test');
    
    $ff->setParams(array('foo2' => 'bar2'), 'get');
    $bar = $ff->fetchParam('request:foo2');
    $this->assertEquals('bar2', $bar, 'Reqest test');
    
    $ff->setParams(array('foo3' => 'bar2'), 'post');
    $bar = $ff->fetchParam('request:foo3');
    $this->assertEquals('bar2', $bar, 'Reqest test');
    
    $bar = $ff->fetchParam('request:noSuchThing');
    $this->assertNull($bar, 'Test miss');
    
    $bar = $ff->fetchParam('get:noSuchThing');
    $this->assertNull($bar, 'Test miss');
  }
  
  public function testHandleRequest() {
    $ff = new FortissimoHarness(self::config);
    $ff->handleRequest('testHandleRequest1');
    
    $cxt = $ff->getContext();
    
    $this->assertEquals('test', $cxt->get('mockCommand'));
    
    $ff->handleRequest('testHandleRequest2');
    $this->assertEquals('From Default', $ff->getContext()->get('mockCommand2'));
    
    $ff->handleRequest('testHandleRequest3');
    $this->assertEquals('From Default 2', $ff->getContext()->get('repeater'));
    
  }
  
  public function testLogger() {
    $ff = new FortissimoHarness(self::config);
    $ff->logException();
    
    $logger = $ff->loggerManager()->getLoggerByName('fail');
    $this->assertNotNull($logger, 'Logger exists.');
    $this->assertEquals(1, count($logger->getMessages()));
  }
    
  public function testRequestCache() {
    
    // First, test to make sure values can be read from cache.
    $config = qp(self::config);
    // Add cache to config:
    $config->append('<cache name="foo" invoke="MockAlwaysReturnFooCache"/>');
    $ff = new FortissimoHarness($config);
    ob_start();
    $ff->handleRequest('testRequestCache1');
    $res = ob_get_contents();
    ob_end_clean();
    
    $this->assertEquals('foo', $res);
    
    // Second, test to see if values can be written to cache.
    $config = qp(self::config);
    // Add cache to config:
    $config->append('<cache name="foo" invoke="MockAlwaysSetValueCache"/>');
    $ff = new FortissimoHarness($config);
    
    ob_start();
    $ff->handleRequest('testRequestCache2');
    $res = ob_get_contents();
    ob_end_clean();
    
    $cacheManager = $ff->cacheManager();
    
    $key = $ff->genCacheKey('testRequestCache2');
    $this->assertEquals('bar', $cacheManager->get($key), 'Has cached item.');
    // We also want to make sure that the output was passed on correctly.
    $this->assertEquals('bar', $res, 'Output was passed through correctly.');
    
    // Finally, make sure that a request still works if no cacher is configured.
    $ff = new FortissimoHarness(self::config);
    ob_start();
    $ff->handleRequest('testRequestCache2');
    $res = ob_get_contents();
    ob_end_clean();
    
    $this->assertEquals('bar', $res);
  }
  
  public function testForwardRequest() {
    $ff = new FortissimoHarness(self::config);
    $ff->handleRequest('testForwardRequest1');
    
    $cxt = $ff->getContext();
    //$this->assertEquals(2, $cxt->size(), 'There should be two items in the context.');
    $this->assertTrue($cxt->has('mockCommand2'), 'Mock command is in context.');
    $this->assertTrue($cxt->has('forwarder'), 'Forwarder command is in context.');
  }
  
  public function testAutoloader() {
    $path = get_include_path();
    $paths = explode(PATH_SEPARATOR, $path);
    
    $this->assertTrue(in_array('test/Tests/Fortissimo/Stubs', $paths));
    
    //$class = new LoaderStub();
    //$this->assertTrue($class->isLoaded(), 'Verify that classes are autoloaded.');
  }

}

// //////////////////////////// //
// MOCKS
// //////////////////////////// //

class MockAlwaysReturnFooCache implements FortissimoRequestCache {
  public function init(){}
  public function set($k, $v) {}
  public function get($key) { return 'foo'; }
  public function delete($key) {}
  public function clear() {}
}

class MockAlwaysSetValueCache implements FortissimoRequestCache {
  public $cache = array();
  public function init(){}
  public function set($k, $v) {$this->cache[$k] = $v;}
  public function get($key) {return $this->cache[$key];}
  public function delete($key) {}
  public function clear() {}
}

class MockCommand implements FortissimoCommand {
  public $name = NULL;
  public function __construct($name) {
    $this->name = $name;
  }
  
  public function execute($paramArray, FortissimoExecutionContext $cxt) {
    $value = isset($paramArray['value']) ? $paramArray['value'] : 'test';
    $cxt->add($this->name, $value);
  }
  
  public function isCacheable() {return FALSE;}
}

class MockPrintBarCommand implements FortissimoCommand {
  public $name = NULL;
  public function __construct($name) {
    $this->name = $name;
  }
  
  public function execute($p, FortissimoExecutionContext $cxt) {
    print 'bar';
  }
  
  public function isCacheable() {return TRUE;}
}

class CommandRepeater implements FortissimoCommand {
  public $name = NULL;
  public function __construct($name) {
    $this->name = $name;
  }
  
  public function execute($paramArray, FortissimoExecutionContext $cxt) {
    $cxt->add($this->name, $paramArray['cmd']);
  }
  public function isCacheable() {return FALSE;}
  
}

class CommandForward implements FortissimoCommand {
  public $name;
  
  public function __construct($name) {
    $this->name = $name;
  }
  
  public function execute($paramArray, FortissimoExecutionContext $cxt) {
    $forwardTo = $paramArray['forward'];
    $cxt->add($this->name, __CLASS__);
    throw new FortissimoForwardRequest($forwardTo, $cxt);
  }
  public function isCacheable() {return FALSE;}
  
}


/**
 * Harness methods for testing specific parts of Fortissimo.
 */
class FortissimoHarness extends Fortissimo {
  
  public $pSources = array(
    'get' => array(),
    'post' => array(),
    'cookie' => array(),
    'session' => array(),
    'env' => array(),
    'server' => array(),
    'argv' => array(),
  );
  
  /**
   * Push an exception into the system as if it were real.
   */
  public function logException($e = NULL) {
    if (empty($e)) {
      $e = new Exception('Dummy exception');
    }
    $this->logManager->log($e, 'Exception');
  }
  
  public function getContext() {
    return $this->cxt;
  }
  
  public function fetchParam($param) {
    return $this->fetchParameterFromSource($param);
  }
  
  public function setParams($params = array(), $source = 'get') {
    $this->pSources[$source] = $params;
  } 
   
  protected function fetchParameterFromSource($from) {
    list($proto, $paramName) = explode(':', $from, 2);
    $proto = strtolower($proto);
    switch ($proto) {
      case 'g':
      case 'get':
        return isset($this->pSources['get'][$paramName]) ? $this->pSources['get'][$paramName] : NULL;
      case 'p':
      case 'post':
        return $this->pSources['post'][$paramName];
      case 'c':
      case 'cookie':
      case 'cookies':
        return $this->pSources['cookie'][$paramName];
      case 's':
      case 'session':
        return $this->pSources['session'][$paramName];
      case 'x':
      case 'cmd':
      case 'context':
        return $this->cxt->get($paramName);
      case 'e':
      case 'env':
      case 'environment':
        return $this->pSources['env'][$paramName];
      case 'server':
        return $this->pSources['server'][$paramName];
      case 'r':
      case 'request':
        return isset($this->pSources['get'][$paramName]) ? $this->pSources['get'][$paramName] : (isset($this->pSources['post'][$paramName]) ? $this->pSources['post'][$paramName] : NULL);
      case 'a':
      case 'arg':
      case 'argv':
        return $argv[(int)$paramName];
    }
  }
  
}