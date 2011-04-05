<?php
/**
 * Unit tests for the SimpleFortissimoCommand class.
 */

require_once 'PHPUnit/Framework.php';
require_once 'Fortissimo/skel/src/Fortissimo.php';

class ConfigTest extends PHPUnit_Framework_TestCase {
  
  public function setUp() {
    Config::initialize();
  }
  
  public function testGetConfiguration() {
    $cfg = Config::getConfiguration();
    $this->assertEquals(8, count($cfg));
  }
  
  public function testIncludePath() {
    Config::includePath('foo/bar');
    $cfg = Config::getConfiguration();
    $this->assertEquals(1, count($cfg[Config::PATHS]));
    
    Config::includePath('foo/bar2');
    $cfg = Config::getConfiguration();
    $this->assertEquals(2, count($cfg[Config::PATHS]));
  }
  
  public function testRequest() {
    $cfg = Config::request('foo')
      ->doesCommand('bing')
      ->whichInvokes('BingClass')
        ->withParam('bar')
        ->from('post:bar')
      ->doesCommand('bing2')
        ->withParam('another')
        ->whoseValueIs('turkey')
    ->getConfiguration();
    
    $this->assertEquals(1, count($cfg[Config::REQUESTS]));
    
    $entry = $cfg[Config::REQUESTS]['foo'];    
    $this->assertEquals('BingClass', $entry['bing']['class']);
    $this->assertEquals('post:bar', $entry['bing']['params']['bar']['from']);
    $this->assertEquals('turkey', $entry['bing2']['params']['another']['value']);
  }
  
  public function testGroup() {
    $cfg = Config::group('foo2')
      ->doesCommand('bing')
      ->whichInvokes('BingClass')
        ->withParam('bar')
        ->from('post:bar')
      ->doesCommand('bing2')
        ->withParam('another')
        ->whoseValueIs('turkey')
    ->getConfiguration();
    
    $this->assertEquals(1, count($cfg[Config::GROUPS]));
    
    $entry = $cfg[Config::GROUPS]['foo2'];
    $this->assertEquals('BingClass', $entry['bing']['class']);
    $this->assertEquals('post:bar', $entry['bing']['params']['bar']['from']);
    $this->assertEquals('turkey', $entry['bing2']['params']['another']['value']);
  }
  
  public function testGroupInRequest() {
    Config::group('groupInRequest')->doesCommand('blarg')->whichInvokes('BlargClass');
    $cfg = Config::request('myRequest')
      ->doesCommand('blork')->whichInvokes('BlorkClass')
      ->usesGroup('groupInRequest')
      ->doesCommand('last')->whichInvokes('LastClass')
      ->getConfiguration();
    
    $expects  = array('blork', 'blarg', 'last');
    $i = 0;
    foreach ($cfg[Config::REQUESTS]['myRequest'] as $command => $params) {
      $this->assertEquals($expects[$i++], $command);
    }
    
  }
  
  public function testDatasources() {
    // Define one.
    $cfg = Config::datasource('foo')->whichInvokes('bar');
    
    // Define a second one.
    $cfg = Config::datasource('db')
      ->whichInvokes('FortissimoMongoDatasource')
      ->withParam('server')
        ->whoseValueIs('mongodb://localhost:27017</param>')
      ->withParam('defaultDB')
        ->whoseValueIs('BONGO')
      // Only one database can be set as the default.
      ->withParam('isDefault')
        ->whoseValueIs(TRUE)
    ->getConfiguration();
    
    $this->assertEquals(2, count($cfg[Config::DATASOURCES]));

    $entry = $cfg[Config::DATASOURCES]['db'];
    $this->assertEquals('BONGO', $entry['params']['defaultDB']['value']);
    $this->assertEquals('FortissimoMongoDatasource', $entry['class']);
    $this->assertEquals('bar', $cfg[Config::DATASOURCES]['foo']['class']);
  }
  
  public function testLoggers() {
    $cfg = Config::logger('lumberjack')->whichInvokes('ImOkay')->getConfiguration();
    $this->assertEquals('ImOkay', $cfg[Config::LOGGERS]['lumberjack']['class']);
  }
  
  public function testCaches() {
    $cfg = Config::cache('memcache')->whichInvokes('Memcachier')->getConfiguration();
    $this->assertEquals('Memcachier', $cfg[Config::CACHES]['memcache']['class']);
  }
  
  public function testUseRequestMapper() {
    $cfg = Config::useRequestMapper('FortissimoRequestMapper')->getConfiguration();
    $this->assertEquals('FortissimoRequestMapper', $cfg[Config::REQUEST_MAPPER]);
  }
}

