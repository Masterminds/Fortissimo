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
      // Parse arguments
      // Show help if necessary
      // Parse INI file
      // Execute command
    ;


    // Run the commandline runner.
    global $argv;
    $runner = new CLIRunner($argv, STDOUT, STDIN);
    $runner->useRegistry($registry);
    $runner->run('default');

  }
}

