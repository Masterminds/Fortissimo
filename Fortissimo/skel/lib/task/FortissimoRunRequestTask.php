<?php
/** @file
 * Phing task for running a request from the build.xml file.
 */
 
require_once 'src/Fortissimo.php';

/**
 * Phing task for running Fortissimo requests from the build.xml file.
 *
 * It expects to be passed the name of the request to fire, otherwise it
 * executes 'default'.
 */
class FortissimoRunRequestTask extends Task {
  // Docs state that this is required:
  public $taskname = 'fortissimorequest';
  
  // Request name.
  public $requestName = 'default';
  
  public function init() {}
  
  public function main() {
    chdir('./src'); // Change so that include path mirrors index.php
    $conf = 'config/commands.php';
    $ff = new Fortissimo($conf);
    $ff->handleRequest($this->requestName);
  }
  
  public function setRequest($requestName) {
    $this->requestName = $requestName;
  }
  
}