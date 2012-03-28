<?php
namespace Fortissimo\Tests;
class FatalErrorLogger extends \Fortissimo\Logger\Base {

  protected $ignore = array();

  public function init() {
    if (!empty($this->params['ignore'])) {
      $this->ignore = $this->params['ignore'];
    }
  }

  public function log($msg, $severity, $details) {

    if (!in_array($severity, $this->ignore)) {
      throw new \Exception($msg . ' ' . $details);
    }

  }

}
