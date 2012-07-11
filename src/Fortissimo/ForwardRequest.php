<?php
/**
 * @file
 */
namespace Fortissimo;

/**
 * Forward a request to another request.
 *
 * This special type of interrupt can be thrown to redirect a request mid-stream
 * to another request. The context passed in will be used to pre-seed the context
 * of the next request.
 */
class ForwardRequest extends Interrupt {
  protected $destination;
  protected $cxt;
  protected $internal;

  /**
   * Construct a new forward request.
   *
   * The information in this forward request will be used to attempt to terminate
   * the current request, and continue processing by forwarding on to the
   * named request.
   *
   * @param string $requestName
   *  The name of the request that this should forward to.
   * @param Fortissimo::ExecutionContext $cxt
   *  The context. IF THIS IS PASSED IN, the next request will continue using this
   *  context. IF THIS IS NOT PASSED OR IS NULL, the next request will begin afresh
   *  with an empty context.
   */
  public function __construct($requestName, \Fortissimo\ExecutionContext $cxt = NULL, $allowInternal = TRUE) {
    $this->destination = $requestName;
    $this->cxt = $cxt;
    $this->internal = $allowInternal;
    parent::__construct('Request forward.');
  }

  /**
   * Get the name of the desired destination request.
   *
   * @return string
   *  A request name.
   */
  public function destination() {
    return $this->destination;
  }

  /**
   * Retrieve the context.
   *
   * @return object Fortissimo::ExecutionContext
   *  The context as it was at the point when the request was interrupted.
   */
  public function context() {
    return $this->cxt;
  }

  public function allowInternal() {
    return $this->internal;
  }
}
