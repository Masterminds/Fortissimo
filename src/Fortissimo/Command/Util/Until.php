<?php
/**
 * @file
 */

namespace Fortissimo\Command\Util;

class Until extends \Fortissimo\Command\Base {
  public function expects() {
    return $this
      ->description('Functional looping.')
      ->usesParam('request', 'The request to loop over.')->whichIsRequired()
      ->usesParam('condition', 'A callable which will return FALSE when this loop should stop')->whichIsRequired()
      ->usesParam('allowInternal', 'Allow internal routes to be called.')->whichHasDefault(FALSE)
      ->andReturns('Nothing.')
      ;
  }

  public function doCommand() {
    $request = $this->param('request');
    $cb = $this->param('condition');
    $internal = $this->param('allowInternal', FALSE);

    while (call_user_func($cb, $this->context) !== FALSE) {
      $this->context->fortissimo()->handleRequest($request, $this->context, $internal);
    }

  }

}
