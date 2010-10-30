<?php
/** Test the FortissimoContextDump class. */
require_once 'PHPUnit/Framework.php';
require_once 'Fortissimo/skel/src/Fortissimo.php';

class FortissimoContextDumpTest extends PHPUnit_Framework_TestCase {

  public function setUp() {
    Config::initialize();
    Config::request('testDoCommand')
      ->doesCommand('mock')->whichInvokes('MockCommand')
      ->doesCommand('testDoCommand1')->whichInvokes('FortissimoContextDump')
    ;
  }
    
  public function testDoCommand() {
    $ff = new FortissimoHarness();
    

    
    ob_start();
    $ff->handleRequest('testDoCommand');
    $c = ob_get_contents();
    ob_end_clean();
    $c = trim($c);
    
    $this->assertTrue(strlen($c) > 15, 'Something should have been dumped.');
    $this->assertEquals('object', substr($c, 0, 6));
  }
}