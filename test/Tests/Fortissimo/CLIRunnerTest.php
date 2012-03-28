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
      ->does('\Fortissimo\Command\Echo', 'echo')
        ->using('text', 'TEST')
    ;


    // Run the commandline runner.
    global $argv;
    $runner = new CLIRunner($argv, STDOUT, STDIN);
    $runner->useRegistry($registry);
    fprintf(STDOUT, 'TEST');
    $runner->run('default');
    fprintf(STDOUT, 'DONE');

    $this->assertTrue(TRUE);

  }
}

