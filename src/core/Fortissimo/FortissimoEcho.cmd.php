<?php
/**
 * Provides a basic echo command.
 *
 * @ingroup Fortissimo
 */

/**
 * This command prints 
 */ 
class FortissimoEcho extends BaseFortissimoCommand {
  
  public function expects() {
    return $this
      ->description('Echo the contents of the "text" parameter to standard output.')
      ->usesParam('type', 'The MIME type of the message, e.g. text/plain, text/html, application/javascript')
      ->withFilter('string')
      ->usesParam('headers', 'Other HTTP headers to set. This should be an indexed array of header strings, e.g. array("Location: http://example.com").')
      ->usesParam('text', 'The text to echo.')
      //->withFilter('string')
      ;
  }
  
  public function doCommand() {
    
    $type = $this->param('type', NULL);
    $headers = $this->param('headers', array());
    
    if (!empty($type)) {
      header('Content-Type: ' . $type);
    }
    
    foreach ($headers as $header) {
      header($header);
    }
    
    print $this->parameters['text'];
  }
}