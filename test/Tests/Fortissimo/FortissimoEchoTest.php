<?php
/** Test the FortissimoContextDump class. */
require_once 'PHPUnit/Framework.php';
require_once 'Fortissimo/skel/src/Fortissimo.php';

class FortissimoEchoTest extends PHPUnit_Framework_TestCase {

  public function setUp() {
    Config::initialize();
    Config::request('testDoCommand')
      ->doesCommand('echo')
        ->whichInvokes('FortissimoEcho')
        ->withParam('text')->whoseValueIs('Echo')
    ;
  }
  
    
  public function testDoCommand() {
    $ff = new FortissimoHarness();
    ob_start();
    $ff->handleRequest('testDoCommand');
    $c = ob_get_contents();
    ob_end_clean();
    $c = trim($c);
    
    $this->assertEquals('Echo', $c);
  }
}