<?php
/**
 * @file
 */
namespace Fortissimo;
/**
 * Transform an error or warning into an exception.
 */
class ErrorException extends Exception {
  public static function initializeFromError($code, $str, $file, $line, $cxt) {
    //printf("\n\nCODE: %s %s\n\n", $code, $str);
    $class = __CLASS__;
    throw new $class($str, $code, $file, $line);
  }

  public function __construct($msg = '', $code = 0, $file = NULL, $line = NULL) {
    if (isset($file)) {
      $msg .= ' (' . $file;
      if (isset($line)) $msg .= ': ' . $line;
      $msg .= ')';
    }
    parent::__construct($msg, $code);
  }
}
