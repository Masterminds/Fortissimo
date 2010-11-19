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
      ->usesParam('text', 'The text to echo.')
      //->withFilter('string')
      ;
  }
  
  public function doCommand() {
    print $this->parameters['text'];
  }
}