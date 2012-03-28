<?php
namespace Fortissimo\Tests;

require 'TestCase.php';

use Fortissimo\Runtime\CLIRunner;
use Fortissimo\Registry;

class CLIRunnerTest extends TestCase {

  public function testRun() {
    // Build the registry.
    $registry = new Registry('load-objects');

    $registry->route('default')
      ->does('\Fortissimo\Command\EchoText', 'echo')
        ->using('text', 'TEST')
        ;

    $registry->logger('\Fortissimo\Logger\OutputInjectionLogger');


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

