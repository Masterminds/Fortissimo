<?php
namespace Fortissimo\Tests;
require_once 'TestCase.php';

use Fortissimo\Runtime\CLIRunner;
use Fortissimo\Registry;

class CLIRunnerTest extends TestCase {

  public function testRun() {
    // Build the registry.
    $registry = $this->registry(__CLASS__);
    $registry->route('default')
      ->does('\Fortissimo\Command\EchoText', 'echo')
        ->using('text', 'TEST')
        ;



    // Run the commandline runner.
    global $argv;
    $runner = new CLIRunner($argv, STDOUT, STDIN);
    $runner->useRegistry($registry);

    ob_flush();
    ob_start();
    $runner->run('default');
    $out = ob_get_clean();

    $this->assertEquals('TEST', $out);


  }
}

