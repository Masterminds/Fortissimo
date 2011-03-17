<?php
/**
 * Phing Task for running {@Fortissimo::explainRequest()} on all requests.
 *
 * This will print out help text on all requests currently registered for
 * Fortissimo.
 * @package Fortissimo
 * @subpackage PhingTask
 */
require_once 'src/Fortissimo.php';

class FortissimoExplainTask extends Task {
  public $taskname = 'fortissimoexplain';
  
  // public function __construct() {
  //     parent::__construct();
  //   }
  
  public function init() {}
  public function main() {
    
    array_unshift('./src', get_include_path());
    
    $path = implode(PATH_SEPARATOR, $paths);
    set_include_path($path);
    
    include 'src/config/commands.php';
    $config = Config::getConfiguration();
    foreach ($config[Config::REQUESTS] as $request) {
      $request['#explaining'] = TRUE;
    }
    Config::initialize($config);
    
    
    // Now we do it again, this time to invoke explain.
    $config = Config::getConfiguration();
    $ff = new Fortissimo();
    
    //print_r($config[Config::REQUESTS]);
    
    // Invoke explain on each item.
    foreach ($config[Config::REQUESTS] as $name => $junk) {
      $ff->handleRequest($name);
    }
  }
}