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
    
    // Add 'src/' to the include path.
    $path = './src' . PATH_SEPARATOR . get_include_path();
    set_include_path($path);
    
    // Load the configuration.
    include 'config/commands.php';
    $config = Config::getConfiguration();
    
    foreach ($config[Config::REQUESTS] as $reqName => $payload) {
      $config[Config::REQUESTS][$reqName]['#explaining'] = TRUE;
    }
    
    // We might have to reset relative paths.
    $pathCount = count($config[Config::PATHS]);
    for($i = 0; $i < $pathCount; ++$i) {
      
      // We're looking for relative paths that exist in src/.
      if (strpos($config[Config::PATHS][$i], '/') !== 0 
          && is_dir('src/' . $config[Config::PATHS][$i])) {
        // Add a prefix.
        $config[Config::PATHS][$i] = 'src/' . $config[Config::PATHS][$i];
      }
      
    }
    
    Config::initialize($config);
    
    // Now we do it again, this time to invoke explain.
    $config = Config::getConfiguration();
    $ff = new Fortissimo();
    
    // Invoke explain on each item.
    foreach ($config[Config::REQUESTS] as $name => $junk) {
      //print "Doing $name" . PHP_EOL;
      $ff->handleRequest($name);
    }
  }
}