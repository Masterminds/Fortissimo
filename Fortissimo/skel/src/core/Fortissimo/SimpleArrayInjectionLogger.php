<?php
/** @file
 * This file provides the SAIL logger, a very simple user-friendly log.
 */

/**
 * Provide a simple user-friendly (non-trace) error message.
 * 
 * Params:
 * - categories: An indexed array or comma-separated list of categories that this logger should log.
 * @see FortissimoArrayInjectionLogger
 *
 * @ingroup Fortissimo
 */
class SimpleArrayInjectionLogger extends FortissimoArrayInjectionLogger {
  public function log($message, $category, $details) {
    $severity = str_replace(' ', '-', $category);
    $filter = '<div class="log-item %s"><strong>%s</strong> %s</div>';
    switch ($category) {
      case 'Fatal Error':
        $msg = 'An unrecoverable error occurred. Your request could not be completed.';
      case 'Recoverable Error':
        $msg = 'An error occurred. Some data may be lost or incomplete.';
      default:
        $msg = 'An unexpected error occurred. Some data may be lost or incomplete.';
    }
    $this->logItems[] = sprintf($filter, $severity, 'Error', $msg);
  }
}