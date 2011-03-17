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
    // $conf = qp('src/config/commands.php');
    // $requests = $conf->branch('request');
    // 
    // // Turn explain on.
    // foreach ($requests as $req) $req->attr('explain', 'true');
    
    include 'src/config/commands.php';
    $config = Config::getConfiguration();
    foreach ($config[Config::REQUESTS] as $request) {
      $request['#explaining'] = TRUE;
    }
    Config::set($config);
    
    
    $ff = new Fortissimo();
    
    // Invoke explain on each item.
    foreach ($requests as $req) {
      $ff->handleRequest($req->attr('name'));
    }
  }
}