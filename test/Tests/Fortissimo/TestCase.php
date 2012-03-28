<?php

namespace Fortissimo\Tests;

require_once 'PHPUnit/Autoload.php';

class TestCase extends \PHPUnit_Framework_TestCase {

  /**
   * Minimalist classloader.
   *
   * This is designed to trigger an error any time a class
   * fails to load.
   */
  public static function autoloader($klass) {

    if (strpos($klass, 'Fortissimo') !== 0) return;

    if (strpos($klass, 'Fortissimo\\Tests') === 0) {
      $load = __DIR__ . '/';
      $parts = explode('\\', $klass);
      $parts = array_slice($parts, 2);
      $klass = implode('/', $parts);
    }
    else {
      $load = __DIR__ . '/../../../src/';
    }

    $path = $load . str_replace('\\', '/', $klass) . '.php';
    include_once $path;
  }

  public function runner($reg = NULL) {
    $runner = new TestRunner();
    if (!empty($reg)) {
      $runner->useRegistry($reg);
    }
    return $runner;
  }

  public function registry($name = 'test') {
    $reg = new \Fortissimo\Registry($name);
    $reg->logger('\Fortissimo\Tests\FatalErrorLogger', 'testlogger');

    return $reg;
  }


  public static function setUpBeforeClass() {
    spl_autoload_register('\Fortissimo\Tests\TestCase::autoloader');
  }
}
