<?php
require_once 'PHPUnit/Framework.php';
require_once 'Fortissimo/skel/src/Fortissimo.php';

class FortissimoExceptionsTest extends PHPUnit_Framework_TestCase {
  const config = './test/test_commands.php';
  
  public function setUp() {
    Config::initialize();
    Config::request('foo')->doesCommand('test')->whichInvokes('ExceptionThrowingCommand');
    Config::request('div')->doesCommand('test')->whichInvokes('ErrorThrowingCommand');
    Config::logger('fail')->whichInvokes('FortissimoArrayInjectionLogger');
  }
  
  /**
   * @   expectedException FortissimoException
   */
  public function testException () {
    $ff = new FortissimoHarness(self::config);
    $ff->handleRequest('foo');
    $log = $ff->getContext()->getLoggerManager()->getLoggerbyName('fail');
    $msgs = $log->getMessages();
    
    $this->assertEquals(1, count($msgs));
  }
  
  public function testErrorToException() {
    $ff = new FortissimoHarness(self::config);
    $ff->handleRequest('div');
    $log = $ff->getContext()->getLoggerManager()->getLoggerbyName('fail');
    $msgs = $log->getMessages();
    
    $this->assertEquals(1, count($msgs));
  }
}

class ExceptionThrowingCommand extends BaseFortissimoCommand {
  
  public function expects() {
    return $this->description('Throws an exception.');
  }
  
  public function doCommand() {
    throw new Exception('By Design');
  }
}

class ErrorThrowingCommand extends ExceptionThrowingCommand {
  public function doCommand() {
    // I <3 Divde-by-zero
    1/0;
  }
}

