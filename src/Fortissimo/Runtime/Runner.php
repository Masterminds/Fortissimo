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

  /**
   * The internal Fortissimo server.
   *
   * A new Fortissimo server is created each time a registry is set.
   * (A Fortissimo server cannot have more than one registry).
   */
  protected $ff;

  /**
   * Attach an initial context.
   *
   * This method is commonly extended by specific runners.
   *
   * @attention
   *   Implementation Note: You do not need to call
   *   Fortissimo::ExecutionContext::attachFortissimo() in this
   *   method. It is called in run().
   */
  public function initialContext() {
    $cxt = new \Fortissimo\ExecutionContext();
    return $cxt;
  }

  /**
   * Use the given registry.
   *
   * Each time a registry is set, a new internal Fortissimo
   * server is created -- specific to the registry.
   *
   * @param object $registry
   *   The Fortissimo::Registry for this app.
   * @retval object THIS
   */
  public function useRegistry($registry) {
    $this->registry = $registry;
    $this->ff = new \Fortissimo($registry);
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

    $cxt = $this->initialContext();
    $cxt->attachFortissimo($this->ff);
    $this->ff->handleRequest($route, $cxt, $this->allowInternalRequests);

    return $cxt;
  }
}
