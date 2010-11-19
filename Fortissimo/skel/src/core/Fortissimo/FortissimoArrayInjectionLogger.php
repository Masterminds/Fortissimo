<?php
/** @file
 * This file contains the code for the FAIL logger.
 */

/**
 * The FAIL logger maintains an array of messages to be retrieved later.
 * 
 * Log entries can be injected into the output by retrieving a list
 * of log messages with {@link getMessages()}, and then displaying them,
 * or by simply calling {@link printMessages()}.
 *
 * A FAIL logger declaration looks like this:
 *
 * @code
 * Config::logger('fail')
 *   ->whichInvokes('FortissimoArrayInjectionLogger')
 *   ->withParam('html')
 *     ->whoseValueIs(TRUE)
 * @endcode
 *
 * Parameters:
 *
 * - html: If TRUE, the logger generates HTML. If false, it generates plain text.
 * - categories: An indexed array or comma-separated list of categories that this logger should log.
 *
 * @ingroup Fortissimo
 */
class FortissimoArrayInjectionLogger extends FortissimoLogger {
  protected $logItems = array();
  protected $filter;
  
  public function init() {
    $this->filter = empty($this->params['html']) ? '%s: %s' : '<div class="log-item %s">%s<pre class="log-details">%s</pre></div>';
  }
  
  /**
   * Fetch the array of messages.
   *
   * Returns an array of log items already formatted for display.
   *
   * @return array
   *  An indexed array of strings, each of which represents a log message.
   */
  public function getMessages() {
    return $this->logItems;
  }
  
  /**
   * Prints all collected log messages.
   */
  public function printMessages() {
    print implode('', $this->logItems);
  }
  
  public function log($message, $category, $details) {
    $severity = str_replace(' ', '-', $category);
    $this->logItems[] = sprintf($this->filter, $severity, $message, $details);
  }
}