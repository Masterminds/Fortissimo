<?php
namespace Fortissimo\Tests;
class TestRunner extends \Fortissimo\Runtime\Runner {

  public function initialContext() {
    return new \Fortissimo\ExecutionContext(array('test' => TRUE));
  }
}
