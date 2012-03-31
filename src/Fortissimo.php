<?php
/** @file
 * This file contains the Fortissimo core.
 * @see fortissimo_core
 */
/** @defgroup fortissimo_core Fortissimo Core
 * The Fortissimo core.
 *
 * Fortissimo.php contains the core classes necessary to bootstrap and run an
 * application that makes use of the Fortissimo framework. All of the necessary
 * classes are encapsulated in this single file so that bootstrapping time can be
 * kept to a minimum, with as little loading/autoloading overhead as possible.
 *
 * When this file is loaded, the include path is augmented to include the
 * includes/, core/, and phar/ directories.
 *
 * <b>Using Fortissimo</b>
 * Fortissimo is a framework for developing web applications. Unlike most
 * web frameworks, it is not based on the MVC pattern. Instead, it uses
 * the front controller pattern, coupled with a variation on the chain-of-command
 * pattern.
 *
 * To get started, look at the command.xml file. The front controller reads
 * this file to map requests to a sequence of commands. When building a
 * Fortissimo application, you will be building such mappings yourself.
 *
 * Each request will kick off a chain of zero or more commands, which are
 * executed in sequence. A command is simply a class that implements the
 * Fortissimo::Command class. Most commands will extend the abstract
 * Fortissimo::Command::Base class, which provides a baseline of
 * functionality.
 *
 * A command can take zero or more parameters. Parameters can be retrieved from
 * a wide variety of sources, the most common being GET and POST data, and
 * output from other commands. Since commands are highly configurable, they
 * are input-neutral: the parameters can come from any input. Fortissimo
 * itself handles the retrieval of parameters, and the source of each parameter
 * is configured not in code, but in the commands.xml file. Practically speaking,
 * what this means is that one command can recieve input from a user-configurable
 * source. The data can come from GET, POST, cookies, sessions, other commands,
 * command line args, or environment variables.
 *
 * To get started with Fortissimo, take a look at some of the unit testing
 * classes or read the online documentation. Then write a custom class implementing
 * the Fortissimo::Command::Base.
 *
 * The code in the Fortissimo project is released under an MIT-style license.
 *
 * License: http://opensource.org/licenses/mit.php An MIT-style License (See LICENSE.txt)
 * Copyright (c) 2009, 2010 Matt Butcher.
 *
 * @author M Butcher <matt@aleph-null.tv>
 * @see Fortissimo
 * @version %UNSTABLE-
 * @{
 */

/**
 * This constant contains the request start time.
 *
 * For optimal performance, use this instead of time()
 * to generate the NOW time.
 */
define('FORTISSIMO_REQ_TIME', time());


/**
 * The Fortissimo front controller.
 *
 * This class is used to bootstrap Fortissimo and oversee execution of a
 * Fortissimo request.
 *
 * Typically, the entry point for this class is Fortissimo::handleRequest(), which
 * takes a request name and executes all associated commands.
 *
 */
class Fortissimo {

  /**
   * Error codes that should be converted to exceptions and thrown.
   */
  const ERROR_TO_EXCEPTION = 771; // 257 will catch only errors; 771 is errors and warnings.

  /**
   * Fatal error.
   * Used by Fortissimo when logging failures.
   * Fatal errors are those that should not be caught by anything other than the request
   * handler. Or, to phrase it another way, these are errors that should rightly stop the
   * execution of the app. Typically, Fortissimo::InterruptException
   * exceptions represent this category.
   */
  const LOG_FATAL = 'Fatal Error';
  /**
   * Error that does not have to be fatal.
   * Used by Fortissimo when logging failures. A Recoverable
   * error means that something *could have caught* this, but nothing did.
   */
  const LOG_RECOVERABLE = 'Recoverable Error';
  /**
   * Error designed for the user to see.
   *
   * Errors like this are generally user-friendly, and are designed to give
   * feedback to the user. Example: Failed form submission.
   */
  const LOG_USER = 'User Error';

  protected $regReader = NULL;
  protected $initialConfig = NULL;
  protected $logManager = NULL;
  protected $cxt = NULL;
  protected $cacheManager = NULL;
  protected $datasourceManager = NULL;

  /** Tracks whether the current request is caching. */
  protected $isCachingRequest = FALSE;

  /**
   * Construct a new Fortissimo server.
   *
   * The server is lightweight, and optimized for PHP's single request model. For
   * advanced cases, one server can handle multiple requests, and performance will
   * scale linearly (dependent, of course, on the commands themselves). However,
   * since the typical PHP application handles only one request per invocation,
   * this controller will attempt to bootstrap very quickly with minimal loading.
   *
   * It should be illegal to eat bananas on a crowded train. They smell bad, and
   * people chomp on them, which makes a gross noise.
   *
   * @param string $configuration
   *  The full path to a configuration file. This is optional, as you can load
   *  configuration data externally.
   * @param array $configData
   *  Any additional configuration data can be added here. This information
   *  will be placed into the {@link Fortissimo::ExecutionContext} that is passed
   *  into each command. In this way, information passed here should be available
   *  to every command, as well as to the overarching framework.
   */
  public function __construct($configuration = NULL, $configData = array()) {

    $this->initialConfig = $configData;

    // Parse configuration file.
    $this->regReader = new \Fortissimo\RegistryReader($configuration);

    // Add additional files to the include path:
    $paths = $this->regReader->getIncludePaths();
    $this->addIncludePaths($paths);

    /*
     * Create log, cache, and datasource managers, then give each a handle to the others.
     */

    // Create the log manager.
    $this->logManager = new \Fortissimo\Logger\Manager($this->regReader->getLoggers());

    // Create the datasource manager.
    $this->datasourceManager = new \Fortissimo\Datasource\Manager($this->regReader->getDatasources());

    // Create cache manager.
    $this->cacheManager = new \Fortissimo\Cache\Manager($this->regReader->getCaches());

    // Set up the log manager
    $this->logManager->setDatasourceManager($this->datasourceManager);
    $this->logManager->setCacheManager($this->cacheManager);

    // Set up the datasource manager
    $this->datasourceManager->setLogManager($this->logManager);
    $this->datasourceManager->setCacheManager($this->cacheManager);

    // Set up the cache manager
    $this->cacheManager->setLogManager($this->logManager);
    $this->cacheManager->setDatasourceManager($this->datasourceManager);

    // Create a request mapper. We do this last so that it can access the other facilities.
    $mapperClass = $this->regReader->getRequestMapper();
    if (!is_string($mapperClass) && !is_object($mapperClass)) {
      throw new \Fortissimo\InterruptException('Could not find a valid command mapper.');
    }

    $this->requestMapper =
        new $mapperClass($this->logManager, $this->cacheManager, $this->datasourceManager);
  }

  /**
   * Add paths that will be used by the autoloader and include/require.
   *
   * Fortissimo uses the spl_autoload() family of functions to
   * automatically load classes. This method can be used to dynamically
   * append directories to the paths used for including class files.
   *
   * No duplicate checking is done internally. That means that this
   * multiple instances of the same path can be added to the include
   * path. There are no known problems associated with this behavior.
   *
   * @param array $paths
   *  An indexed array of path names. This will be appended to the PHP
   *  include path.
   * @see get_include_path()
   */
  public function addIncludePaths($paths) {

    // This is from the older autoloader design.
    // FIXME: Should this be removed?
    global $loader;
    if (!empty($loader)) {
      $loader->addIncludePaths($paths);
    }

    array_unshift($paths, get_include_path());

    $path = implode(PATH_SEPARATOR, $paths);
    set_include_path($path);
  }

  public function genCacheKey($requestName) {
    return 'request-' . $requestName;
  }

  /**
   * Explain all of the commands in a request.
   *
   * This will explain the request, and then attempt to explain
   * every command in the request. If the command is an {@link Explainable}
   * object, then {@link Explainable::explain()} will be called. Otherwise,
   * Fortissimo will use other methods, such as introspection, to attempt to
   * self-document.
   *
   * @param Fortissimo::Registry $request
   *  A request object.
   * @return string
   *  An explanation string in plain text.
   */
  public function explainRequest($request) {

    if (empty($request)) {
      throw new \Fortissimo\Exception('Request not found.');
    }

    $out = sprintf('REQUEST: %s', $request->getName()) . PHP_EOL;
    foreach($request as $name => $command) {
      // If this command as an explain() method, use it.
      if ($command['instance'] instanceof \Fortissimo\Explainable) {
        $out .= $command['instance']->explain();
      }
      else {
        $filter = 'CMD: %s (%s): Unexplainable command, unknown parameters.';

        $out .= sprintf($filter, $command['name'], $command['class']) . PHP_EOL;
      }
    }
    return $out . PHP_EOL;
  }

  /**
   * Handles a request.
   *
   * When a request comes in, this method is responsible for dispatching
   * the request to the necessary commands, executing commands in sequence.
   *
   * <b>Note:</b> Fortissimo has experimental support for request
   * caching. When request caching is enabled, the output of a request is
   * stored in a cache. Subsequent identical requests will be served out of
   * the cache, thereby avoiding all overhead associated with loading and
   * executing commands. (Request caching is different than command caching, see Cacheable, which
   * caches only the output of individual commands.)
   *
   * @param string $identifier
   *  A named identifier, typically a URI. By default (assuming ForitissimoRequestMapper has not
   *  been overridden) the $identifier should be a request name.
   * @param Fortissimo::ExecutionContext $initialCxt
   *  If an initialized context is necessary, it can be passed in here.
   * @param boolean $allowInternalRequests
   *  When this is TRUE, requests that are internal-only are allowed. Generally, this is TRUE under
   *  the following circumstances:
   *  - When a Fortissimo::Redirect is thrown, internal requests are allowed. This is so that
   *    you can declare internal requests that assume that certain tasks have already been
   *    performed.
   *  - Some clients can explicitly call handleRequest() with this flag set to TRUE. One example
   *    is `fort`, which will allow command-line execution of internal requests.
   */
  public function handleRequest($identifier = 'default', \Fortissimo\ExecutionContext $initialCxt = NULL, $allowInternalRequests = FALSE) {

    // Experimental: Convert errors (E_ERROR | E_USER_ERROR) to exceptions.
    set_error_handler(array('\Fortissimo\ErrorException', 'initializeFromError'), 257);

    // Load the request.
    try {
      // Use the mapper to determine what the real request name is.
      $requestName = $this->requestMapper->uriToRequest($identifier);
      $request = $this->regReader->getRequest($requestName, $allowInternalRequests);
    }
    catch (\Fortissimo\RequestNotFoundException $nfe) {
      // Need to handle this case.
      $this->logManager->log($nfe, self::LOG_USER);
      $requestName = $this->requestMapper->uriToRequest('404');

      if ($this->regReader->hasRequest($requestName, $allowInternalRequests)) {
        $request = $this->regReader->getRequest($requestName, $allowInternalRequests);
      }
      else {
        header('HTTP/1.0 404 Not Found');
        print '<h1>Not Found</h1>';
        return;
      }
    }

    $cacheKey = NULL; // This is set only if necessary.

    // If this request is in explain mode, explain and exit.
    if ($request->isExplaining()) {
      print $this->explainRequest($request);
      return;
    }
    // If this allows caching, check the cached output.
    elseif ($request->isCaching() && isset($this->cacheManager)) {
      // Handle caching.
      $cacheKey = $this->genCacheKey($requestName);
      $response = $this->cacheManager->get($cacheKey);

      // If a cached version is found, print that data and return.
      if (isset($response)) {
        print $response;
        return;
      }

      // If we get here, no cache hit was found, so we start buffering the
      // content to cache it.
      $this->startCaching();
    }

    // This allows pre-seeding of the context.
    if (isset($initialCxt)) {
      $this->cxt = $initialCxt;
    }
    // This sets up the default context.
    else {
      $this->cxt = new \Fortissimo\ExecutionContext(
        $this->initialConfig,
        $this->logManager,
        $this->datasourceManager,
        $this->cacheManager,
        $this->requestMapper
      );
    }

    // Loop through requests and execute each command. Most of the logic in this
    // loop deals with exception handling.
    foreach ($request as $command) {
      try {
        $this->execCommand($command);
      }
      // Kill the request and log an error.
      catch (\Fortissimo\InterruptException $ie) {
        $this->logManager->log($ie, self::LOG_FATAL);
        $this->stopCaching();
        return;
      }
      // Forward any requests.
      catch (\Fortissimo\ForwardRequest $forward) {
        // Not sure what to do about caching here.
        // For now we just stop caching.
        $this->stopCaching();

        // Forward the request to another handler. Note that we allow forwarding
        // to internal requests.
        $this->handleRequest($forward->destination(), $forward->context(), TRUE);
        return;
      }
      // Kill the request, no error.
      catch (\Fortissimo\Interrupt $i) {
        $this->stopCaching();
        return;
      }
      // Log the error, but continue to the next command.
      catch (\Fortissimo\Exception $e) {
        // Note that we don't cache if a recoverable error occurs.
        $this->stopCaching();
        $this->logManager->log($e, self::LOG_RECOVERABLE);
        continue;
      }
      catch (\Exception $e) {
        $this->stopCaching();
        // Assume that a non-caught exception is fatal.
        $this->logManager->log($e, self::LOG_FATAL);
        //print "Fatal error";
        return;
      }
    }

    // If output caching is on, place this entry into the cache.
    if ($request->isCaching() && isset($this->cacheManager)) {
      $contents = $this->stopCaching();
      // Add entry to cache.
      $this->cacheManager->set($cacheKey, $contents);

    }

    // Experimental: Restore error handler. (see set_error_handler()).
    restore_error_handler();
  }

  /**
   * Start caching a request.
   *
   * @see stopCaching()
   */
  protected function startCaching() {
    $this->isCachingRequest = TRUE;
    ob_start();
  }

  /**
   * Stop caching this request.
   *
   * @return string
   *  The data collected in the cache buffer.
   * @see startCaching()
   */
  protected function stopCaching() {
    if ($this->isCachingRequest) {
      $contents = ob_get_contents();

      // Turn off output buffering & send to client.
      ob_end_flush();

      return $contents;
    }
  }

  /**
   * Retrieve the associated logger manager.
   *
   * The logger manager proxies data to the underlying logging facilities
   * as defined in the command configuration.
   *
   * @return Fortissimo::Logger::Manager
   *  The logger manager overseeing logs for this server.
   * @see Fortissimo::Logger
   * @see Fortissimo::OutputInjectionLogger
   */
  public function loggerManager() {
    return $this->logManager;
  }

  /**
   * Get the caching manager for this server.
   *
   * @return Fortissimo::Cache::Manager
   *  The cache manager for this server.
   */
  public function cacheManager() {
    return $this->cacheManager;
  }

  /**
   * Given a command, prepare it to receive events.
   */
  protected function setEventHandlers($command, $listeners) {
    $command->setEventHandlers($listeners);
  }

  /**
   * Execute a single command.
   *
   * This takes a command array, which describes a command, and then does the following:
   *
   * - Find out what params the command expects and get them.
   * - Prepare any event handlers that listen for events on this command
   * - Execute the command
   * - Handle any errors that arise
   *
   * @param array $commandArray
   *  An associative array, as described in Fortissimo::Config::createCommandInstance.
   * @throws Fortissimo::Exception
   *  Thrown if the command failed, but execution should continue.
   * @throws Fortissimo::Interrupt
   *  Thrown if the command wants to interrupt the normal flow of execution and
   *  immediately return to the client.
   */
  protected function execCommand($commandArray) {
    // We should already have a command object in the array.
    $inst = $commandArray['instance'];


    $params = $this->fetchParameters($commandArray, $this->cxt);
    //print $commandArray['name'] . ' is ' . ($inst instanceof Observable ? 'Observable' : 'Not observable') . PHP_EOL;
    if ($inst instanceof \Fortissimo\Observable && !empty($commandArray['listeners'])) {
      $this->setEventHandlers($inst, $commandArray['listeners']);
    }

    //set_error_handler(array('Fortissimo::ErrorException', 'initializeFromError'), 257);
    set_error_handler(array('\Fortissimo\ErrorException', 'initializeFromError'), self::ERROR_TO_EXCEPTION);
    try {
      $inst->execute($params, $this->cxt);
    }
    // Only catch a Fortissimo::Exception. Allow Fortissimo::Interupt to go on.
    catch (\Fortissimo\Exception $e) {
      restore_error_handler();
      $this->logManager->log($e, 'Recoverable Error');
    }
    catch (\Exception $fatal) {
      restore_error_handler();
      throw $fatal;
    }
    restore_error_handler();
  }

  /**
   * Retrieve the parameters for a command.
   *
   * This does the following:
   *
   * - Find out what parameters a command expects.
   * - Look at the Config::from() calls on an object and retrieve data as necessary. This uses fetchParameterFromSource() to retrieve the data.
   * - Fill in default values from Config::whoseValueIs() calls
   * - Return the mapping of parameter names to (newly fetched) values.
   *
   * @param array $commandArray
   *  Associative array of information about a command, as described
   *  in Fortissimo::RegistryReader::createCommandInstance().
   */
  protected function fetchParameters($commandArray) {
    $params = array();
    foreach ($commandArray['params'] as $name => $config) {


      // If there is a FROM source, fetch the data from the designated source(s).
      if (!empty($config['from'])) {
        // Handle cases like this: 'from="get:preferMe post:onlyIfNotInGet"'
        $fromItems = explode(' ', $config['from']);
        $value = NULL;

        // Stop as soon as a parameter is fetched and is not NULL.
        foreach ($fromItems as $from) {
          $value = $this->fetchParameterFromSource($from);
          if (isset($value)) {
            $params[$name] = $value;
            break;
          }
        }
      }

      // Set the default value if necessary.
      if (!isset($params[$name]) && isset($config['value'])) $params[$name] = $config['value'];
    }
    return $params;
  }

  /**
   * Parse a parameter specification and retrieve the appropriate data.
   *
   * @param string $from
   *  A parameter specification of the form [source]:[name]. Examples:
   *  - get:myParam
   *  - post:username
   *  - cookie:session_id
   *  - session:last_page
   *  - cmd:lastCmd
   *  - env:cwd
   *  - file:uploadedFile
   *  Known sources:
   *  - get
   *  - post
   *  - cookie
   *  - session
   *  - cmd (retrieved from the execution context.)
   *  - env
   *  - server
   *  - request
   *  - argv (From $argv, assumes that the format of from is argv:N, where N is an integer)
   *  - files
   * @return string
   *  The value or NULL.
   * @todo argv should support slices of the ARGV array so shell globs can be handled.
   */
  protected function fetchParameterFromSource($from) {
    list($proto, $paramName) = explode(':', $from, 2);
    $proto = strtolower($proto);
    switch ($proto) {
      case 'g':
      case 'get':
        // This null check is for E_STRICT.
        return isset($_GET[$paramName]) ? $_GET[$paramName] : NULL;
      case 'p':
      case 'post':
        return isset($_POST[$paramName]) ? $_POST[$paramName] : NULL;
      case 'c':
      case 'cookie':
      case 'cookies':
        return isset($_COOKIE[$paramName]) ? $_COOKIE[$paramName] : NULL;
      case 's':
      case 'session':
        return isset($_SESSION[$paramName]) ? $_SESSION[$paramName] : NULL;
      case 'x':
      case 'cmd':
      case 'cxt':
      case 'context':
        return $this->cxt->get($paramName);
      case 'e':
      case 'env':
      case 'environment':
        return isset($_ENV[$paramName]) ? $_ENV[$paramName] : NULL;
      case 'server':
        return isset($_SERVER[$paramName]) ? $_SERVER[$paramName] : NULL;
      case 'r':
      case 'request':
        return isset($_REQUEST[$paramName]) ? $_REQUEST[$paramName] : NULL;
      case 'a':
      case 'arg':
      case 'argv':
        global $argv;
        $i = (int)$paramName;
        return isset($argv[$i]) ? $argv[$i] : NULL;
      case 'f':
      case 'file':
      case 'files':
        return isset($_FILES[$paramName]) ? $_FILES[$paramName] : NULL;
    }
  }
}
