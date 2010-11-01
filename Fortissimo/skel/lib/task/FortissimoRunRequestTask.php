<?php
/**
 * Phing task for running a request from the build.xml file.
 * @package Fortissimo
 * @subpackage PhingTask
 */
require_once 'src/Fortissimo.php';

class FortissimoRunRequestTask extends Task {
  // Docs state that this is required:
  public $taskname = 'fortissimorequest';
  
  // Request name.
  public $requestName = 'default';
  
  public function init() {}
  
  public function main() {
    chdir('./src'); // Change so that include path mirrors index.php'sx
    $conf = 'config/commands.php'; //qp('config/commands.php');
    $ff = new Fortissimo($conf);
    $ff->handleRequest($this->requestName);
  }
  
  public function setRequest($requestName) {
    $this->requestName = $requestName;
  }
  
}