<?php
/** @file
 * This file contains the classes required for logging Fortissimo output to syslog.
 */

/**
 * Provides Syslog support for Fortissimo's loggers.
 *
 * About Categories:
 *
 * Fortissimo allows the ad-hoc definition of new logging categories. When logging to the system's
 * syslog, we need to convert those to one of the syslog logging priorities (LOG_WARNING, LOG_ERR, 
 * etc.). Fortissimo converts unknown categories to LOG_NOTICE unless a category2priority map 
 * is passed in.
 *
 * Params:
 *  - category2priority: An associative array mapping Fortissimo categories to Syslog priorities
 *  - categories: An indexed array or comma-separated list of categories that this logger should log.
 *  - verbose: A boolean flag. If TRUE, then details (like stack traces) will be logged, too.
 *  - ident: A string indicating what identity you want this logged into the logger with. By 
 *      default, it is 'Fortissimo'.
 *  - logOptions: an integer (composed typically of or'd flags) of options to pass to syslogger.
 *      For details, see http://us2.php.net/manual/en/function.openlog.php. Default: 0.
 *  - facility: one of the facility constants (LOG_USER, LOG_LOCAL3, LOG_SYSLOG, etc). By default,
 *      LOG_USER is used for maximal platform compatibility. For a list of facilities, see
 *      http://us2.php.net/manual/en/function.openlog.php
 *
 * @ingroup Fortissimo
 */
class FortissimoSyslogLogger extends FortissimoLogger {
  
  protected $catmap = array();
  protected $ident, $opts, $verbose, $facility;
  
  public function init() {
    
    // If there is a map, set it.
    if (isset($this->params['category2priority'])) {
      $this->catmap = $this->params['category2priority'];
    }
    
    // Set the app's identity.
    $this->ident = (isset($this->params['ident'])) ? $this->params['ident'] : 'Fortissimo';
    
    // See if verbose logging is on.
    $this->verbose = isset($this->params['verbose']) 
      && filter_var($this->params['verbose'], FILTER_VALIDATE_BOOLEAN);
    
    // Set log options:
    $this->opts = isset($this->params['logOptions']) ? $this->params['logOptions'] : 0;
    
    $this->facility = isset($this->params['facility']) ? (int)$this->params['facility'] : LOG_USER;
    
    openlog($this->ident, $this->opts, $this->facility);
  }
  
  public function log($msg, $category, $details) {
    
    $level = $this->getLogLevel($category);
    if ($this->verbose) {
      $msg .= ' ' . $details;
    }
    
    syslog($level, $msg);
  }
  
  /**
   * Get the Syslog logging level.
   * 
   * See http://us2.php.net/manual/en/function.syslog.php for info on the logging priorities.
   *
   * This implementation does not allow you to override the built-in levels. If you want to override
   * built-in log levels, you must subclass this and override getLogLevel().
   *
   * @param string $severity
   *  The Fortissimo category.
   * @return int
   *  The syslog LOG_* priority.
   */
  protected function getLogLevel($severity) {
    $level = LOG_NOTICE;
    switch ($severity) {
      case Fortissimo::LOG_FATAL:
      case Fortissimo::LOG_RECOVERABLE:
      // Used in unit tests.
      case 'Exception':
        $level = LOG_ERR;
        break;
      case Fortissimo::LOG_USER:
        $level = LOG_WARNING;
        break;
      default:
        $level = isset($this->catmap[$severity]) ? $this->catmap[$severity] : LOG_NOTICE;
    }
    return $level;
  }
}