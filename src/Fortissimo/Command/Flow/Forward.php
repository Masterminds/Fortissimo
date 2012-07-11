<?php
/**
 * @file
 * Forward to another request.
 */
namespace Fortissimo\Command\Flow;
/**
 * Forward to another route.
 *
 * This forwards the current request to another route, bringing the 
 * context along with it.
 */
class Forward extends \Fortissimo\Command\Base {
  public function expects() {
    return $this
      ->description('Forward to another route.')
      ->usesParam('route', 'The name of the route.')->whichIsRequired()
      ->usesParam('allowInternal', 'Allow this to forward to an @-request.')
        ->whichHasDefault(FALSE)
        ->withFilter('boolean')
      ->andReturns('Nothing, but it will stop processing of the present route and begin a new route.')
      ;
  }
  public function doCommand() {
    $route = $this->param('route');
    $internal = $this->param('allowInternal');
    throw new \Fortissimo\ForwardRequest($route, $this->context, $internal);
  }
}
