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
      ->description('Dumps everything in the context to STDOUT.')
      ->usesParam('html', 'Prints the dump in pretty HTML output. Default: False')
      ->withFilter('boolean')
      ->usesParam('item', 'Dump only this item, not the entire context.')
      ->withFilter('string')
      ;
  }
  
  /**
   * Dump the context to STDOUT.
   */
  public function doCommand() {
    $pretty = $this->param('html', FALSE);
    $item = $this->param('item', NULL);
    
    if (!empty($item)) {
      $format = '<div class="fortissimo-context-dump-header">Dumping Context Item "%s"</div>';
      printf($format, $item);
      $dump = $this->context->get($item);
    }
    else {
      $dump = $this->context;
    }
    
    if ($pretty) {
      print '<div class="fortissimo-context-dump"><pre>';
      var_dump($dump);
      print '</pre></div>';
    }
    else {
      var_dump($dump);
    }
    
  }
}