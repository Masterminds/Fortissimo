<?php
/**
 * This file contains the command for dumping context.
 * @package Fortissimo
 * @subpackage Command
 */

/**
 * This command dumps the contents of the context to standard out.
 *
 * It performs this operation by running {@link var_dump()} on the
 * contents of the {@link FortissimoExecutionContext}.
 *
 * This is useful (occasionally) for debugging.
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