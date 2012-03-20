<?php
/** @file
 * This file contains the FOIL logger.
 */
 
/**
 * The FOIL logger sends messages directly to STDOUT.
 *
 * Log messages will be emitted to STDOUT as soon as they are logged.
 *
 * Configuration Parameters:
 *  - html: If TRUE, HTML output will be generated. If FALSE, plain text.
 *  - categories: An indexed array or comma-separated list of categories that this logger should log.
 * @todo This class needs some cleanup.
 *
 * @ingroup Fortissimo
 */
class FortissimoOutputInjectionLogger extends FortissimoLogger {
  protected $filter;
  protected $isHTML = FALSE;
  
  public function init() {
    
    $this->isHTML = isset($this->params['html']) ? filter_var($this->params['html'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $this->filter = empty($this->params['html']) ? '%s %s %s' : '<div class="log-item %s"><strong>%s</strong> %s</div>';
  }
  public function log($message, $category, $details) {
    
    if ($this->isHTML) {
      $severity = strtr($category, ' ', '-');
      $message = strtr($message, array("\n" => '<br/>'));
      $filter = '<div class="log-item %s"><strong>%s</strong> %s <pre class="log-details">%s</pre></div>';
      printf($filter, $severity, $category, $message, $details);
    }
    else {
      printf('%s: %s -- %s', $category, $message, $details);
    }
  }
}