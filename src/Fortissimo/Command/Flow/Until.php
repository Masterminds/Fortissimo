<?php
/**
 * @file
 */
namespace Fortissimo\Command\Flow;

/**
 * The Until looping command.
 *
 * This command executes a request (a route) repeatedly until the
 * condition in 'condition' obtains. It pre-evaluates. That is, it
 * runs the condition check BEFORE running the request.
 *
 * Params:
 * - request: The name of a route to run. This route will inherit the
 *   current context.
 * - condition: A callback, such as a closure. This will be given
 *   the context (Fortissimo::ExecutionContext) as a parameter. When it returns
 *   TRUE, the loop will stop.
 * - allowInternal: Boolean flag. If TRUE, it will allow '@' routes (e.g. @404)
 *   to be executed. If FALSE, only public routes can be executed through this
 *   loop.
 */
class Until extends \Fortissimo\Command\Base {
  public function expects() {
    return $this
      ->description('Functional looping.')
      ->usesParam('request', 'The request to loop over.')->whichIsRequired()
      ->usesParam('condition', 'A callable which will return TRUE when this loop should stop')->whichIsRequired()
      ->usesParam('allowInternal', 'Allow internal routes to be called.')->whichHasDefault(FALSE)
      ->andReturns('Nothing.')
      ;
  }

  public function doCommand() {
    $request = $this->param('request');
    $cb = $this->param('condition');
    $internal = $this->param('allowInternal', FALSE);

    while (call_user_func($cb, $this->context) !== TRUE) {
      $this->context->fortissimo()->handleRequest($request, $this->context, $internal);
    }

  }

}
