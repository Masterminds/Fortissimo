<?php
/**
 * @file
 *
 * Generic runner.
 *
 */
namespace Fortissimo\Runtime;

/**
 * The generic Fortissimo runner.
 *
 * This is an untuned runner. It will run Fortissimo, but it is not
 * particularly tuned to a web or CLI environment. It is useful for
 * embedding, or for handling basic tasks.
 *
 * The Fortissimo::Runtime::CLIRunner is tuned for running command line apps,
 * and the Fortissimo::Runtime::WebRunner is tuned for running web
 * services.
 *
 * When extending Runner, the typical points of entry are as follows:
 *
 * - Runner::initialContext() -- Specify the context that the server will
 *   begin with. This is by far the most commonly overridden method.
 * - Runner::useRegistry() -- Set the Fortissimo::Registry that should
 *   be used by this runner. Rarely overridden.
 * - Runner::run() -- Pass a request into Fortissimo.
 */
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
    //$cxt->attachFortissimo($this->ff);
    $cxt->attachRegistry($this->registry);
    $this->ff->handleRequest($route, $cxt, $this->allowInternalRequests);

    return $cxt;
  }
}
