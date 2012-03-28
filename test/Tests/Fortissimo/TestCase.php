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

    $load = __DIR__ . '/../../../src/';
    $path = $load . str_replace('\\', '/', $klass) . '.php';
    include_once $path;
  }


  public static function setUpBeforeClass() {
    spl_autoload_register('\Fortissimo\Tests\TestCase::autoloader');
  }
}
