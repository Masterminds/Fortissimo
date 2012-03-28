<?php
/** @file
 * Provides a simple user-facing output injection logger.
 */

/**
 * Provide a simple user-friendly (non-trace) error message.
 *
 * This is similar to FortissimoOutputInjectionLogger in design, but with 
 * focus on sending trivial error messages to users (as opposed to technical
 * log message intended for developers).
 *
 * Params:
 * - categories: An indexed array or comma-separated list of categories that this logger should log.
 *
 * @ingroup Fortissimo
 */
class SimpleOutputInjectionLogger extends FortissimoOutputInjectionLogger {
  
  public function log($message, $category, $details) {
    $severity = strtr($category, ' ', '-');
    $filter = '<div class="log-item %s"><strong>%s</strong> %s</div>';
    switch ($category) {
      case 'Fatal Error':
        $msg = 'An unrecoverable error occurred. Your request could not be completed.';
      case 'Recoverable Error':
        $msg = 'An error occurred. Some data may be lost or incomplete.';
      default:
        $msg = 'An unexpected error occurred. Some data may be lost or incomplete.';
    }
    printf($filter, $severity, 'Error', $msg);
  }
}