<?php
/**
 * @file
 *
 * Generic abstract runner.
 */
namespace Fortissimo\Runtime;

class Runner {

  protected $registry;

  /**
   * Whether or not Fortissimo should allow internal requests.
   *
   * An internal request (`@foo`) is considered special, and
   * cannot typically be executed directly.
   */
  protected $allowInternalRequests = FALSE;

  public function initialContext() {
    $cxt = new \Fortissimo\ExecutionContext();

    return $cxt;
  }

  /**
   * Use the given registry.
   *
   * @param object $registry
   *   The Fortissimo::Registry for this app.
   * @retval object THIS
   */
  public function useRegistry($registry) {
    $this->registry = $registry;
    return $this;
  }

  public function run($route = 'default') {
    $ff = new \Fortissimo($this->registry);
    $cxt = $this->initialContext();
    $ff->handleRequest($route, $cxt, $this->allowInternalRequests);
  }
}
