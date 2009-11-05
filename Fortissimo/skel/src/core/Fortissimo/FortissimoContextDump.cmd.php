<?php
/**
 * This file contains the command for dumping context.
 */
 
class FortissimoContextDump extends BaseFortissimoCommand {
  
  public function expects() {
    return $this
      ->description('Dumps everything in the context to STDOUT.');
  }
  
  /**
   * Dump the context to STDOUT.
   */
  public function doCommand() {
    var_dump($this->context);
  }
}