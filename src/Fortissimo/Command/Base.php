<?php
/**
 * @file
 * Fortissimo::Command::Base class.
 */
namespace Fortissimo\Command;

/**
 * This is a base class that can be extended to add new commands.
 *
 * The class provides several basic services.
 *
 * First, it simplifies the
 * process of executing a command. The Fortissimo::Command::Base::doCommand()
 * method follows a very simple pattern.
 *
 * Second, it provides structure for describing a command. The abstract
 * Fortissimo::Command::Base::expects() method provides the facilities for
 * describing what parameters this command should use, how these parameters should
 * be filtered/validated/sanitized, and what each parameter is for.
 *
 * Third, using the data from Fortissimo::Command::Base::expects(), this
 * class provides a self-documenting tool, Fortissimo::Command::Base::explain(),
 * which uses the information about the parameter to provide human-radible
 * documentation about what this command does.
 *
 * When extending this class, there are two things that every extension must do:
 *
 * 1. It must provide information about what parameters it uses. This is done
 *  by implementing expects().
 * 2. It must provide logic for performing the command. This is done in
 *  doCommand().
 *
 */
abstract class Base implements \Fortissimo\Command, Explainable, Observable {

  protected $paramsCollection;

  /**
   * The array of event listeners attached to this command.
   */
  protected $listeners = NULL;

  /**
   * The name of this command.
   * Passed from the 'name' value of the command configuration file.
   */
  protected $name = NULL;
  /**
   * The request-wide execution context ({@link Fortissimo::ExecutionContext}).
   *
   * Use this to retrieve the results of other commands. Typically, you will not need
   * to add data to this. Returning data from the {@link doCommand()} method will
   * automatically insert it into the context.
   */
  protected $context = NULL;

  /**
   * Flag indicating whether this object is currently (supposed to be)
   * in caching mode.
   *
   * For caching to be enabled, both this flag (which comes from the command
   * config) and the {@link isCacheable()} method must be TRUE.
   *
   * @deprecated This has been replaced by Cacheable::isCaching(), which leaves cache control
   * the responsibility only of objects that are actually cacheable.
   */
  protected $caching = FALSE;
  /**
   * An associative array of parameters.
   *
   * These are the parameters passed into the command from the environment. The name
   * will correspond to the 'name' parameter in the command configuration file. The
   * value is retrieved depending on the 'from' (or default value) rules in the
   * configuration file.
   */
  protected $parameters = NULL;

  /**
   * Construct a new Fortissimo::Command::Base.
   *
   * This is automatically called by the framework during a Fortissimo::handleRequest()
   * sequence of events. Note, however, that it can be called explicitly by things trying
   * to execute commands outside of the normal chain.
   *
   * @param string $name
   *  The name of the command, available to extending classes as $this->name.
   * @param boolean $caching
   *  DEPRECATED: This is ignored. The original command caching mechanism has been replaced
   *  by the Cacheable interface and Cacheable::isCaching().
   */
  public function __construct($name, $caching = FALSE) {
    $this->name = $name;
    $this->caching = $caching;
  }

  /**
   * By default, a Fortissimo base command is cacheable.
   *
   * This has been deprecated in favor of the Cacheable interface. To test the
   * cacheability of an object, you should run this:
   * @code
   * <?php
   * $cmd instanceof Cacheable;
   * ?>
   * @endcode
   *
   * @return boolean
   *  Returns TRUE unless a subclass overrides this.
   * @deprecated
   *  This is no longer used, as it was replaced by the Cacheable interface.
   */
  public function isCacheable() {
    //return TRUE;
    return $this instanceof Cacheable;
  }

  /**
   * Get a parameter by name.
   *
   * Fetch a parameter by name. If no such parameter exists, the
   * $default value will be returned.
   *
   * @param string $name
   *  The name of the parameter to fetch.
   * @param mixed $default
   *  The default value to return if no such parameter is found.
   *  This is NULL by default.
   * @see context()
   */
  protected function param($name, $default = NULL) {
    return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
  }

  /**
   * EXPERIMENTAL: Convert a request and arguments to a URL.
   *
   * This is a convenience wrapper that fetches the Fortissimo::RequestMapper and
   * transforms a request to a URL.
   *
   * @param string $request
   *  The request name.
   * @param array $args
   *  Name/value params.
   * @return string
   *  URL, suitable for links.
   */
  protected function url($request, $args = array()) {
    return $this->context->getRequestMapper()->requestToUrl($request, $args);
  }

  /**
   * Get an object from the context.
   *
   * Get an object from the context by name. The context is the
   * {@link Fortissimo::ExecutionContext} for the current request. When
   * a Fortissimo command extending {@link Fortissimo::Command::Base} returns,
   * its data goes into the context, so you can use this to fetch the results
   * of previous commands.
   *
   * @param string $name
   *  The name of the context object. Typically, this is the name value assigned
   *  to a command in the commands.xml file.
   * @param mixed $default
   *  The default value that will be returned if no such object is found in the
   *  context. EXPERT: This will return by reference if you use the &$foo syntax.
   * @see param()
   */
  protected function &context($name, $default = NULL) {
    $val = &$this->context->get($name);
    if (!isset($val)) {
      $val = NULL;
    }

    return $val;
  }

  /**
   * Helper function for handling cache lookups.
   *
   * @param string $key
   *  The key to use with the cache.
   */
  protected function executeWithCache($key) {

    $cacheManager = $this->context->getCacheManager();

    // Figure out which cache we're using.
    if (($be = $this->cacheBackend()) == NULL) {
      $cache = $cacheManager->getDefaultCache();
    }
    else {
      $cache = $cacheManager->getCacheByName($be);
    }

    // Bail here if we don't have a cache.
    if (empty($cache)) {
      return;
    }

    // Try to get from cache.
    if (($result = $cacheManager->get($key)) != NULL) {
      return $result;
    }

    // We have a cache miss, so we need to do the command and set the cache entry.
    $result = $this->doCommand();
    $cacheManager->set($key, $result, $this->cacheLifetime());

    // Return the result to execute().
    return $result;
  }

  public function execute($params, \Fortissimo\ExecutionContext $cxt) {
    $this->context = $cxt;
    $this->prepareParameters($params);
    $result = NULL;

    // If this looks like a cache can handle it, use a cache.
    if ($this instanceof Cacheable && $this->isCaching() && ($key = $this->cacheKey()) != NULL) {
      $result = $this->executeWithCache($key);
    }

    // If no result has been set, execute the command.
    if (is_null($result)) $result = $this->doCommand();

    // Add the results to the context.
    $this->context->add($this->name, $result);
  }

  /**
   * Handle a parameter validation/sanitization failure.
   *
   * By default, this simply throws an exception, but more advanced processing
   * can be handled here, if necessary.
   *
   * @throws Fortissimo::Exception
   *  If the filter fails, an exception is thrown. Note that
   *  FILTER_VALIDATE_BOOLEAN will not throw an exception if it fails. Instead,
   *  if converts values to FALSE. This is a limitation in the PHP
   *  filter library, where a failed filter always returns FALSE.
   *
   * @see validate()
   */
  public function handleIllegalParameter($name, $filter, $payload, $options) {
    $msg = "Filter %s failed for %s (options: %s)";
    throw new \Fortissimo\Exception(sprintf($msg, $filter, $name, print_r($options, TRUE)));
  }

  /**
   * Run a validator or sanitizer.
   *
   * This runs a validator function or a sanitizer function, and returns
   * the result.
   *
   * @param string $name
   *  The name of the parameter. This is used for error reporting.
   * @param string $filter
   *  The name (AS A STRING) of the filter to run. See
   *  Fortissimo::Command::BaseParameter::addFilter() for a list of names,
   *  or consult the PHP documentation for filters.
   * @param mixed $payload
   *  The value to be validated.
   * @param mixed $options
   *  Typically, this is either an array or an ORed list of constants.
   *  See the PHP documentation for possible options.
   * @see http://us.php.net/manual/en/book.filter.php
   * @see handleIllegalParameter() Called if this fails.
   */
  protected function validate ($name, $filter, $payload, $options = NULL) {

    // Specialized filter support to make it simple for classes to filter.
    if ($filter == 'this') {
      $filter = 'callback';

      $func = $options;

      $options = array(
        'options' => array($this, $func),
      );
    }
    // Convenience for the awkward filter_var callback syntax.
    elseif ($filter == 'callback' && is_callable($options)) {
      $options = array(
        'options' => $options,
      );
    }

    $filterID = filter_id($filter);
    $res = filter_var($payload, $filterID, $options);


    // Boolean validation returns FALSE if the bool is false, or if a fail occurs.
    // So we just pass through. Nothing more that can really be done about it.
    if ($res === FALSE && $filterID != FILTER_VALIDATE_BOOLEAN) {
      $this->handleIllegalParameter($name, $filter, $payload, $options);
    }

    return $res;
  }

  /**
   * Prepare all parameters.
   *
   * This fetches parameters from the server and performs any necessary
   * parameter filtering.
   *
   * @param array $params
   *  And array of {@link Fortissimo::Command::BaseParameter} objects which
   *  will be used to determine what parameters this object needs.
   *
   * @see Fortissimo::Command::Base::expects()
   * @see Fortissimo::Command::Base::describe()
   */
  protected function prepareParameters($params) {
    $this->parameters = array();

    // Gets the list of Fortissimo::Command::BaseParameter objects and loops
    // through them, loading the parameters into the object.
    $expecting = $this->expects();
    foreach ($expecting as $paramObj) {
      $name = $paramObj->getName();
      $filters = $paramObj->getFilters();
      if (!isset($params[$name])) {
        if ($paramObj->isRequired()) {
          throw new \Fortissimo\Exception(sprintf('Expected param %s in command %s', $name, $this->name));
        }
        $params[$name] = $paramObj->getDefault();

      }

      // The value.
      $payload = $params[$name];

      // Run all filters, in order.
      foreach ($filters as $filter) {
        $payload = $this->validate($name, $filter['type'], $payload, $filter['options']);
      }

      // Assign a sanitized/validated value.
      $this->parameters[$name] = $payload;
    }
  }

  /**
   * Set a description of this object.
   *
   * This function should be called from within {@link expects()}. Set a description
   * of what this command does. The resulting {@link Fortissimo::Command::BaseParameterCollection}
   * object that is returned should be used to set which parameters this command
   * expects to receive.
   *
   * @param string $string
   *  A description.
   * @return Fortissimo::Command::BaseParameterCollection
   *  An object for configuring this command.
   * @see Fortissimo::Command::Base::expects();
   * @see Fortissimo::Command::Base::explain();
   */
  public function description($string) {
    $this->paramsCollection = new \Fortissimo\Command\BaseParameterCollection($string);
    return $this->paramsCollection;
  }

  /**
   * Produces helpful information about this command.
   *
   * @return string
   *  A string with information about all of the paramters that this command uses,
   *  and what each does.
   */
  public function explain() {
    $expects = $this->expects();

    if (empty($expects)) {
      throw new \Fortissimo\Exception('No information for ' . get_class($this));
    }

    $format = "\t* %s (%s): %s\n";
    $buffer = "\tPARAMS:" . PHP_EOL;
    foreach ($expects as $paramObj) {
      $name = $paramObj->getName();
      $desc = $paramObj->getDescription();

      if ($paramObj->isRequired()) {
        $desc .= ' !REQUIRED!';
      }
      elseif(($val = $paramObj->getDefault()) != NULL) {
        if (is_object($val)) {
          $val = get_class($val);
        }
        elseif (is_array($val)) {
          $val = print_r($val, TRUE);
        }
        elseif (is_resource($val)) {
          $val = 'RESOURCE';
        }
        $desc .= ' [' . $val . ']';
      }

      $fltr = array();
      foreach ($paramObj->getFilters() as $filter) {
        // If callback, display the callback name. Otherwise display the filter.
        switch ($filter['type']) {
          case 'callback':
            $fltr[] = 'callback: ' . $filter['options']['options'];
            break;
          case 'regex':
            $fltr[] = 'regex: ' . $filter['options']['regex'];
            break;
          default:
            $fltr[] = $filter['type'];
        }
      }
      $filterString = implode(', ', $fltr);
      if (strlen($filterString) == 0) $filterString = 'no filters';
      $buffer .= sprintf($format, $name, $filterString, $desc);

    }

    // Gather information on which events this command fires.
    $events = array();
    foreach ($expects->events() as $event => $desc) {
      $events[] = sprintf("\t* %s: %s", $event, $desc);
    }

    if (!empty($events)) {
      $buffer .= "\tEVENTS:" . PHP_EOL . implode(PHP_EOL, $events) . PHP_EOL;
    }

    // We do this because __CLASS__ will return the abstract class.
    $klass = new ReflectionClass($this);

    $cmdFilter = 'CMD: %s (%s): %s';
    return sprintf($cmdFilter, $this->name, $klass->name, $this->paramsCollection->description())
      . PHP_EOL
      . $buffer
      //. PHP_EOL
      . "\tRETURNS: "
      . $expects->returnDescription()
      . PHP_EOL . PHP_EOL;
  }
  /**
   * Set the event handlers.
   *
   * This tells the Observable what listeners are registered for the given
   * object. The listeners array should be an associative array mapping
   * event names to an array of callables.
   *
   * @code
   * <?php
   * array(
   *   'load' => array(
   *      'function_name'
   *      function () {},
   *      array($object, 'methodName'),
   *      array('ClassNam', 'staticMethod').
   *    ),
   *   'another_event => array(
   *      'some_other_function',
   *    ),
   * );
   * ?>
   * @endcode
   *
   * @param array $listeners
   *  An associative array of event names and an array of eventhandlers.
   */
  public function setEventHandlers($listeners) {
    $this->listeners = $listeners;
  }

  /**
   * Trigger a particular event.
   *
   * @param string $eventName
   *   The name of the event.
   * @param array $data
   *   Any data that is to be passed into the event.
   * @return
   *   An optional return value, as determined by the particular event.
   */
  public function fireEvent($eventName, $data = NULL) {
    if (empty($this->listeners) || empty($this->listeners[$eventName])) return;

    $results = array();
    foreach ($this->listeners[$eventName] as $callable) {
      if (!is_callable($callable)) {
        throw new \Fortissimo\InterruptException('Attempting to call uncallable item ' . (string)$callable);
      }
      $results[] = call_user_func($callable, $data);
    }
    return $results;
  }

  /**
   * Information about what parameters the command expects.
   *
   * A command is intended to perform a single discrete task. To perform
   * this task, a command may require one or more input parameters. This
   * method is used to accomplish two things:
   *
   * - Declare what parameters it uses
   * - Explain what it does
   *
   * The first of these tasks is straightforward: A command uses parameters
   * for input. This method gives the command developer the tools to declare
   * which paramaters are used. Additionally, generic sanitization and validation
   * can automatically be done by the base command. Developers can declare which
   * filters should be run on data so that the parameters prepared before the
   * command is executed.
   *
   * The second task is to explain what this command does with these parameters.
   * This is done to provide real-time documentation to developers. A command
   * has the ability to report (using {@link explain()}) what it does, which
   * expedites the development of a Fortissimo application.
   *
   * An implementation of this function should look something like this:
   *
   * @code
   * <?php
   * public function expects() {
   *  return $this
   *   ->description('This command sends a name to an email address.')
   *   ->usesParam('name', 'The name to echo back')
   *    ->withFilter('string')
   *   ->usesParam('email', 'An email address to echo data to.')
   *    ->withFilter('email')
   *    ->withFilter('validate_email')
   *   ->andReturns('A copy of the sent message.');
   * }
   * ?>
   * @endcode
   *
   * For good examples of expects(), see the following classes:
   *
   *  - Fortissimo::Echo: Basic example
   *  - Fortissimo::Redirect: Example using filters and regular expressions
   *  - Fortissimo::Template: Sophisticated example with lots of parameters
   *
   * The above describes a command that expects two parameters: name and email.
   * The name command is validated with the string sanitizer, which makes sure
   * that the string doesn't have markup in it.
   * The email command is first sanitized against the email sanitizer, then it
   * is checked against the email validator to make sure that it is a legitimate
   * email address.
   *
   * @return Fortissimo::Command::BaseParameterCollection
   *  Returns a collection of parameters. This can be easily obtained by
   *  calling {@link description()} on the present object. View the example above
   *  or the {@link SimpleCommandTest} class to see basic examples of how to
   *  return the appropriate data from this method.
   *
   * @see SimpleCommandTest::expects() An example can be found in the unit tests.
   * @see http://us.php.net/manual/en/book.filter.php The PHP Filter system.
   */
  abstract public function expects();

  /**
   * Do the command.
   *
   * Performs the work for this command.
   *
   * Every class that extends this base class should implement doCommand(),
   * executing the command's logic, and returning the value or values that should
   * be placed into the execution context.
   *
   * This object provides access to the following variables of interest:
   *  - $name: The name of the command.
   *  - $parameters: The name/value list of parameters. These are learned
   *    and validated based on the contents of the expects() method.
   *  - $context: The Fortissimo::ExecutionContext object for this request.
   * @return mixed
   *  A value to be placed into the execution environment. The value can be retrieved
   *  using <code>$cxt->get($name)</code>, where <code>$name</code> is the value of this
   *  object's $name variable.
   * @throws Fortissimo::Exception
   *  Thrown when an error occurs, but the application should continue.
   * @throws Fortissimo::Interrupt
   *  Thrown when this command should terminate the request. This is a NON-ERROR condition.
   * @throws Fortissimo::InterruptException
   *  Thrown when a fatal error occurs and the request should terminate.
   */
  abstract public function doCommand();
}