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
      ->usesParam('text', 'The text to echo.')
      //->withFilter('string')
      ;
  }
  
  public function doCommand() {
    
    $type = $this->param('type', NULL);
    
    if (!empty($type)) {
      header('Content-Type: ' . $type);
    }
    
    print $this->parameters['text'];
  }
}