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

  /**
   * Execute a request (route).
   *
   * This executes the named request and returns
   * the final context.
   *
   * @param string $route
   *   The name of the request.
   * @retval object Fortissimo::ExecutionContext
   *   The final context, containing whatever modifications were
   *   made during running.
   * @throws Fortissimo::Runtime::Exception
   *   Thrown when the runtime cannot be initialized or executed.
   */
  public function run($route = 'default') {
    if (empty($this->registry)) {
      throw new \Fortissimo\Runtime\Exception('No registry found.');
    }

    $ff = new \Fortissimo($this->registry);
    $cxt = $this->initialContext();
    $ff->handleRequest($route, $cxt, $this->allowInternalRequests);

    return $cxt;
  }
}
