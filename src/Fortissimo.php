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
 * FortissimoCommand class. Most commands will extend the abstract
 * BaseFortissimoCommand class, which provides a baseline of
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
 * the BaseFortissimoCommand.
 *
 * <b>The Fortissimo Autoloader</b>
 * Fortissimo uses the default SPL autoloader to find and load classes. For that
 * reason, if you follow the convention of storing classes inside of files
 * named with the class name, you will not need to use {@link include()} or any
 * other such functions.
 *
 * Current benchmarks indicate that the SPL autoloader is slightly faster than
 * using require/require_once, both with and without an opcode cache.
 *
 * You can augment the autoloader's default paths using <code>include</code>
 * elements in the command.xml file (or manually loading them in via the
 * config object).
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
 * The version number for this release of QueryPath.
 *
 * Stable versions are numbered in standard x.y.z format, where x is the major
 * version, y is the minor version, and z is the bug-fix/patch level.
 *
 * Developer releases are stamped dev-DATE, where DATE is the ISO-formatted date
 * of the build. You should not use these versions on production systems, nor
 * as a platform for application development, since they are considered unfrozen,
 * and hence subject to change.
 *
 * The special flag @ UNSTABLE @ means that a non-built version of the application
 * is being used. This should occur only for developers who are actively developing
 * Fortissimo. No production release should ever have this tag.
 */
define('FORTISSIMO_VERSION', '@UNSTABLE@');

// Set the include path to include Fortissimo directories.
$basePath = dirname(__FILE__);
$paths[] = get_include_path();
$paths[] = $basePath . '/includes';
$paths[] = $basePath . '/core';
$paths[] = $basePath . '/core/Fortissimo';
$paths[] = $basePath . '/phar';
$path = implode(PATH_SEPARATOR, $paths);
set_include_path($path);

// Prepare the autoloader.
spl_autoload_extensions('.php,.cmd.php,.inc');


// For performance, use the default loader.
// XXX: This does not work well because the default autoloader
// downcases all classnames before checking the FS. Thus, FooBar
// becomes foobar.
//spl_autoload_register();

// Keep this in global scope to allow modifications.
global $loader;
$loader = new FortissimoAutoloader();
spl_autoload_register(array($loader, 'load'));

/**
 * A broad autoloader that should load data from expected places.
 *
 * This autoloader is designed to load classes within the includes, core, and phar
 * directories inside of Fortissimo. Its include path can be augmented using the
 * {@link addIncludePaths()} member function. Internally, {@link Fortissimo} does this
 * as it is parsing the commands.xml file (See {@link Fortissimo::addIncludePaths}).
 *
 * This loader does the following:
 *  - Uses the class name for the base file name.
 *  - Checks in includes/, core/, and phar/ for the named file
 *  - Tests using three different extensions: .php. .cmd.php, and .inc
 *
 * So to load class Foo_Bar, it will check the following (in order):
 *  - includes/Foo_Bar.php
 *  - includes/Foo_Bar.cmd.php
 *  - includes/Foo_Bar.inc
 *  - core/Foo_Bar.php
 *  - core/Foo_Bar.cmd.php
 *  - core/Foo_Bar.inc
 *  - core/Fortissimo/Foo_Bar.php
 *  - core/Fortissimo/Foo_Bar.cmd.php
 *  - core/Fortissimo/Foo_Bar.inc
 *  - phar/Foo_Bar.php
 *  - phar/Foo_Bar.cmd.php
 *  - phar/Foo_Bar.inc
 *
 * Then it will search any other included paths using the same
 * algorithm as exhibited above. (We search includes/ first because
 * that is where implementors are supposed to put their classes! That means
 * that with a little trickery, you can override Fortissimo base commands simply
 * by putting your own copy in includes/)
 *
 * <b>Note that phar is experimental, and may be removed in future releases.</b>
 */
class FortissimoAutoloader {

  protected $extensions = array('.php', '.cmd.php', '.inc');
  protected $include_paths = array();

  public function __construct() {
    //$full_path = get_include_path();
    //$include_paths = explode(PATH_SEPARATOR, $full_path);
    $basePath = dirname(__FILE__);
    $this->include_paths[] = $basePath . '/includes';
    $this->include_paths[] = $basePath . '/core';
    $this->include_paths[] = $basePath . '/core/Fortissimo';
    $this->include_paths[] = $basePath . '/core/Fortissimo/Theme';
    $this->include_paths[] = $basePath . '/phar';
  }

  /**
   * Add an array of paths to the include path used by the autoloader.
   *
   * @param array $paths
   *  Indexed array of paths.
   */
  public function addIncludePaths($paths) {
    $this->include_paths = array_merge($this->include_paths, $paths);
  }

  /**
   * Attempt to load the file containing the given class.
   *
   * @param string $class
   *  The name of the class to load.
   * @see spl_autoload_register()
   */
  public function load($class) {

    // Micro-optimization for Twig, which supplies
    // its own classloader.
    if (strpos($class, 'Twig_') === 0) return;

    // Namespace translation:
    $class = str_replace('\\', '/', $class);

    foreach ($this->include_paths as $dir) {
      $path = $dir . DIRECTORY_SEPARATOR . $class;
      foreach ($this->extensions as $ext) {
        if (file_exists($path . $ext)) {
          //print 'Found ' . $path . $ext . '<br/>';
          require $path . $ext;
          return;
        }
      }
    }
  }

}

/**
 * The Fortissimo front controller.
 *
 * This class is used to bootstrap Fortissimo and oversee execution of a
 * Fortissimo request. Unlike Rhizome, there is no split between the
 * front controller and the request handler. The front controller assumes that
 * the application will be run either as a CLI or as a web application. And it
 * is left to commands to execute behaviors specific to their execution
 * environment.
 *
 * Typically, the entry point for this class is {@link handleRequest()}, which
 * takes a request name and executes all associated commands.
 *
 * For more details, see {@link __construct()}.
 *
 * @see Fortissimo.php
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
   * execution of the app. Typically, FortissimoInterruptException
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

  protected $commandConfig = NULL;
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
   *  will be placed into the {@link FortissimoExecutionContext} that is passed
   *  into each command. In this way, information passed here should be available
   *  to every command, as well as to the overarching framework.
   */
  public function __construct($configuration = NULL, $configData = array()) {

    $this->initialConfig = $configData;

    // Parse configuration file.
    $this->commandConfig = new FortissimoConfig($configuration);

    // Add additional files to the include path:
    $paths = $this->commandConfig->getIncludePaths();
    $this->addIncludePaths($paths);

    /*
     * Create log, cache, and datasource managers, then give each a handle to the others.
     */

    // Create the log manager.
    $this->logManager = new FortissimoLoggerManager($this->commandConfig->getLoggers());

    // Create the datasource manager.
    $this->datasourceManager = new FortissimoDatasourceManager($this->commandConfig->getDatasources());

    // Create cache manager.
    $this->cacheManager = new FortissimoCacheManager($this->commandConfig->getCaches());

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
    $mapperClass = $this->commandConfig->getRequestMapper();
    if (!is_string($mapperClass) && !is_object($mapperClass)) {
      throw new FortissimoInterruptException('Could not find a valid command mapper.');
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
    global $loader;
    $loader->addIncludePaths($paths);

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
   * @param FortissimoRequest $request
   *  A request object.
   * @return string
   *  An explanation string in plain text.
   */
  public function explainRequest($request) {

    if (empty($request)) {
      throw new FortissimoException('Request not found.');
    }

    $out = sprintf('REQUEST: %s', $request->getName()) . PHP_EOL;
    foreach($request as $name => $command) {
      // If this command as an explain() method, use it.
      if ($command['instance'] instanceof Explainable) {
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
   * @param FortissimoExecutionContext $initialCxt
   *  If an initialized context is necessary, it can be passed in here.
   * @param boolean $allowInternalRequests
   *  When this is TRUE, requests that are internal-only are allowed. Generally, this is TRUE under
   *  the following circumstances:
   *  - When a FortissimoRedirect is thrown, internal requests are allowed. This is so that
   *    you can declare internal requests that assume that certain tasks have already been
   *    performed.
   *  - Some clients can explicitly call handleRequest() with this flag set to TRUE. One example
   *    is `fort`, which will allow command-line execution of internal requests.
   */
  public function handleRequest($identifier = 'default', FortissimoExecutionContext $initialCxt = NULL, $allowInternalRequests = FALSE) {

    // Experimental: Convert errors (E_ERROR | E_USER_ERROR) to exceptions.
    set_error_handler(array('FortissimoErrorException', 'initializeFromError'), 257);

    // Load the request.
    try {
      // Use the mapper to determine what the real request name is.
      $requestName = $this->requestMapper->uriToRequest($identifier);
      $request = $this->commandConfig->getRequest($requestName, $allowInternalRequests);
    }
    catch (FortissimoRequestNotFoundException $nfe) {
      // Need to handle this case.
      $this->logManager->log($nfe, self::LOG_USER);
      $requestName = $this->requestMapper->uriToRequest('404');

      if ($this->commandConfig->hasRequest($requestName, $allowInternalRequests)) {
        $request = $this->commandConfig->getRequest($requestName, $allowInternalRequests);
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
      $this->cxt = new FortissimoExecutionContext(
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
      catch (FortissimoInterruptException $ie) {
        $this->logManager->log($ie, self::LOG_FATAL);
        $this->stopCaching();
        return;
      }
      // Forward any requests.
      catch (FortissimoForwardRequest $forward) {
        // Not sure what to do about caching here.
        // For now we just stop caching.
        $this->stopCaching();

        // Forward the request to another handler. Note that we allow forwarding
        // to internal requests.
        $this->handleRequest($forward->destination(), $forward->context(), TRUE);
        return;
      }
      // Kill the request, no error.
      catch (FortissimoInterrupt $i) {
        $this->stopCaching();
        return;
      }
      // Log the error, but continue to the next command.
      catch (FortissimoException $e) {
        // Note that we don't cache if a recoverable error occurs.
        $this->stopCaching();
        $this->logManager->log($e, self::LOG_RECOVERABLE);
        continue;
      }
      catch (Exception $e) {
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
   * @return FortissimoLoggerManager
   *  The logger manager overseeing logs for this server.
   * @see FortissimoLogger
   * @see FortissimoLoggerManager
   * @see FortissimoOutputInjectionLogger
   */
  public function loggerManager() {
    return $this->logManager;
  }

  /**
   * Get the caching manager for this server.
   *
   * @return FortissimoCacheManager
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
   *  An associative array, as described in FortissimoConfig::createCommandInstance.
   * @throws FortissimoException
   *  Thrown if the command failed, but execution should continue.
   * @throws FortissimoInterrupt
   *  Thrown if the command wants to interrupt the normal flow of execution and
   *  immediately return to the client.
   */
  protected function execCommand($commandArray) {
    // We should already have a command object in the array.
    $inst = $commandArray['instance'];

    $params = $this->fetchParameters($commandArray, $this->cxt);
    //print $commandArray['name'] . ' is ' . ($inst instanceof Observable ? 'Observable' : 'Not observable') . PHP_EOL;
    if ($inst instanceof Observable && !empty($commandArray['listeners'])) {
      $this->setEventHandlers($inst, $commandArray['listeners']);
    }

    //set_error_handler(array('FortissimoErrorException', 'initializeFromError'), 257);
    set_error_handler(array('FortissimoErrorException', 'initializeFromError'), self::ERROR_TO_EXCEPTION);
    try {
      $inst->execute($params, $this->cxt);
    }
    // Only catch a FortissimoException. Allow FortissimoInterupt to go on.
    catch (FortissimoException $e) {
      restore_error_handler();
      $this->logManager->log($e, 'Recoverable Error');
    }
    catch (Exception $fatal) {
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
   *  in FortissimoConfig::createCommandInstance().
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


/**
 * A Fortissimo request.
 *
 * This class represents a single request.
 */
class FortissimoRequest implements IteratorAggregate {

  protected $commandQueue = NULL;
  protected $isCaching = FALSE;
  protected $isExplaining = FALSE;
  protected $requestName;

  public function __construct($requestName, $commands) {
    $this->requestName = $requestName;
    $this->commandQueue = $commands;
  }

  public function getName() {
    return $this->requestName;
  }

  /**
   * Get the array of commands.
   *
   * @return array
   *  An array of commands.
   */
  public function getCommands() {
    return $this->commandQueue;
  }

  /**
   * Set the flag indicating whether or not this is caching.
   */
  public function setCaching($boolean) {
    $this->isCaching = $boolean;
  }

  /**
   * Set explain mode.
   *
   * By default a command is NOT in explain mode.
   * @param boolean $boolean
   *  Set to TRUE to turn on explain mode.
   */
  public function setExplain($boolean) {
    $this->isExplaining = $boolean;
  }

  /**
   * Determine whether this request is in 'explain' mode.
   *
   * When a request is explaining, Fortissimo will output detailed
   * information about each command, such as what parameters it expects
   * and what its purpose is.
   *
   * @return boolean
   *  TRUE if this request is in explain mode, false otherwise.
   */
  public function isExplaining() {
    return $this->isExplaining;
  }

  /**
   * Determine whether this request can be served from cache.
   *
   * Request output can sometimes be cached. This flag indicates
   * whether the given request can be served from a cache instead
   * of requiring the entire request to be executed.
   *
   * @return boolean
   *  Returns TRUE if this can be served from cache, or
   *  FALSE if this should not be served from cache.
   * @see FortissimoRequestCache
   */
  public function isCaching() {
    return $this->isCaching;
  }

  /**
   * Get an iterator of this object.
   *
   * @return Iterator
   */
  public function getIterator() {
    return new ArrayIterator($this->commandQueue);
  }
}

/**
 * A Fortissimo command.
 *
 * The main work unit in Fortissimo is the FortissimoCommand. A FortissimoCommand is
 * expected to conduct a single unit of work -- retrieving a datum, running a
 * calculation, doing a database lookup, etc. Data from a command (if any) can then
 * be stored in the {@link FortissimoExecutionContext} that is passed along the
 * chain of commands.
 *
 * Each command has a request-unique <b>name</b> (only one command in each request
 * can have a given name), a set of zero or more <b>params</b>, passed as an array,
 * and a <b>{@link FortissimoExecutionContext} object</b>. This last object contains
 * the results (if any) of previously executed commands, and is the depository for
 * any data that the present command needs to pass along.
 *
 * Typically, the last command in a request will format the data found in the context
 * and send it to the client for display.
 */
interface FortissimoCommand {
  /**
   * Create an instance of a command.
   *
   * @param string $name
   *  Every instance of a command has a name. When a command adds information
   *  to the context, it (by convention) stores this information keyed by name.
   *  Other commands (perhaps other instances of the same class) can then interact
   *  with this command by name.
   * @param boolean $caching
   *  If this is set to TRUE, the command is assumed to be a caching command,
   *  which means (a) its output can be cached, and (b) it can be served
   *  from a cache. It is completely up to the implementation of this interface
   *  to provide (or not to provide) a link to the caching service. See
   *  {@link BaseFortissimoCommand} for an example of a caching service. There is
   *  no requirement that caching be supported by a command.
   */
  public function __construct($name/*, $caching = FALSE*/);

  /**
   * Execute the command.
   *
   * Typically, when a command is executed, it does the following:
   *  - uses the parameters passed as an array.
   *  - performs one or more operations
   *  - stores zero or more pieces of data in the context, typically keyed by this
   *    object's $name.
   *
   * Commands do not return values. Any data they produce can be placed into
   * the {@link FortissimoExcecutionContext} object. On the occasion of an error,
   * the command can either throw a {@link FortissimoException} (or any subclass
   * thereof), in which case the application will attempt to handle the error. Or it
   * may throw a {@link FortissimoInterrupt}, which will interrupt the flow of the
   * application, causing the application to forgo running the remaining commands.
   *
   * @param array $paramArray
   *  An associative array of name/value parameters. A value may be of any data
   *  type, including a classed object or a resource.
   * @param FortissimoExecutionContext $cxt
   *  The execution context. This can be modified by the command. Typically,
   *  though, it is only written to. Reading from the context may have the
   *  effect of making the command less portable.
   * @throws FortissimoInterrupt
   *  Thrown when the command should be treated as the last command. The entire
   *  request will be terminated if this is thrown.
   * @throws FortissimoException
   *  Thrown if the command experiences a general execution error. This may not
   *  result in the termination of the request. Other commands may be processed after
   *  this.
   */
  public function execute($paramArray, FortissimoExecutionContext $cxt);

  /**
   * Indicates whether the command's additions to the context are cacheable.
   *
   * For command-level caching to work, Fortissimo needs to be able to determine
   * what commands can be cached. If this method returns TRUE, Fortissimo assumes
   * that the objects the command places into the context can be cached using
   * PHP's {@link serialize()} function.
   *
   * Just because an item <i>can</i> be cached does not mean that it will. The
   * determination over whether a command's results are cached lies in the
   * the configuration.
   *
   * @return boolean
   *  Boolean TRUE of the object canbe cached, FALSE otherwise.
   */
  //public function isCacheable();
}

/**
 * Container for parameter descriptions.
 *
 * This collection contains parameters. It is used by anything that extends
 * BaseFortissimoCommand to store parameter information for use
 * in BaseFortissimoCommand::explain() and
 * BaseFortissimoCommand::expects(). A builder for these is found
 * in BaseFortissimoCommand::description(), which provides a semi-fluent
 * interface for defining expectations.
 * @see BaseFortissimoCommand
 * @see BaseFortissimoCommandParameter
 */
class BaseFortissimoCommandParameterCollection implements IteratorAggregate {
  protected $params = array();
  protected $events = array();
  protected $description = '';
  protected $paramCounter = -1;
  protected $returns = 'Nothing';

  public function __construct($description) {$this->description = $description;}

  public function usesParam($name, $description) {
    $param = new BaseFortissimoCommandParameter($name, $description);
    $this->params[++$this->paramCounter] = $param;

    return $this;
  }
  /**
   * Add a filter to this parameter.
   *
   * A parameter can have any number of filters. Filters are used to
   * either clean (sanitize) a value or check (validate) a value. In the first
   * case, the system will attempt to remove bad data. In the second case, the
   * system will merely check to see if the data is acceptable.
   *
   * Fortissimo supports all of the filters supplied by PHP. For a complete
   * list, including valid options, see
   * http://us.php.net/manual/en/book.filter.php.
   *
   * Filters each have options, and the options can augment filter behavior, sometimes
   * in remarkable ways. See http://us.php.net/manual/en/filter.filters.php for
   * complete documentation on all filters and all of their options.
   *
   * @param string $filter
   *  One of the predefined filter types supported by PHP. You can obtain the list
   *  from the PHP builtin function filter_list(). Here are values currently
   *  documented:
   *  - int: Checks whether a value is an integer.
   *  - boolean: Checks whether a value is a boolean.
   *  - float: Checks whether a value is an integer (optionally, in a range).
   *  - validate_regexp: Check whether a parameter's value matches a given regular expression.
   *  - validate_url: Checks whether a URL is valid.
   *  - validate_email: Checks whether a value is a valid email address.
   *  - validate_ip: Checks whether a value is a valid IP address.
   *  - string: Sanitizes a string, strips tags, can encode or strip special characters.
   *  - stripped: Same as 'string'
   *  - encoded: URL-encodes a string
   *  - special_chars: XML/HTML entity-encodes special characters.
   *  - unsafe_raw: Does nothing (can optionally encode/strip special chars)
   *  - email: Removes non-Email characters
   *  - url: Removes non-URL characters
   *  - number_int: Removes anything that is not a digit or a sign (+ or -).
   *  - number_float: Removes anything except digits, signs, . , e and E.
   *  - magic_quotes: Run addslashes().
   *  - callback: Use the given callback to filter.
   *  - this: A convenience for 'callback' with the options array('options'=>array($this, 'func'))
   * @param mixed $options
   *  This can be either an array or an OR'd list of flags, as specified in the
   *  PHP documentation.
   */
  public function withFilter($filter, $options = NULL) {
    $this->params[$this->paramCounter]->addFilter($filter, $options);
    return $this;
  }

  public function description() {
    return $this->description;
  }

  /**
   * Provide a description of what value or values are returned.
   *
   * @param string $description
   *  A description of what the invoking command returns from its
   *  {@link BaseFortissimoCommand::doCommand()} method.
   */
  public function andReturns($description) {
    $this->returns = $description;
    return $this;
  }

  public function whichIsRequired() {
    $this->params[$this->paramCounter]->setRequired(TRUE);
    return $this;
  }

  public function whichHasDefault($default) {
    $this->params[$this->paramCounter]->setDefault($default);
    return $this;
  }

  /**
   * Declares an event for this command.
   *
   * This indicates (though does not enforce) that this command may
   * at some point in execution fire an event with the given event name.
   *
   * Event listeners can bind to this command's event and be notified when the
   * event fires.
   *
   * @param string $name
   *  The name of the event. Example: 'load'.
   * @param string $description
   *  A description of the event.
   * @return
   *  This object.
   */
  public function declaresEvent($name, $description) {
    $this->events[$name] = $description;
    return $this;
  }

  /**
   * Set all events for this object.
   *
   * The $events array must follow this form:
   *
   * @code
   * <?php
   * array(
   *  'event_name' => 'Long description help text',
   *  'other_event' => 'Description of conditions under which other_event is called.',
   * );
   * ?>
   * @endcode
   */
  public function setEvents(array $events) {
    $this->events = $events;
    return $this;
  }

  public function events() { return $this->events; }

  public function returnDescription() {
    return $this->returns;
  }

  public function setParams($array) {
    $this->params = $array;
  }

  public function params() {
    return $this->params;
  }

  public function getIterator() {
    return new ArrayIterator($this->params);
  }
}

/**
 * Describe a parameter.
 *
 * Describe a parameter for a command.
 *
 * @see BaseFortissimoCommand
 * @see BaseFortissimoCommand::expects()
 * @see BaseFortissimoCommandParameterCollection
 */
class BaseFortissimoCommandParameter {
  protected $filters = array();

  protected $name, $description, $defaultValue;
  protected $required = FALSE;

  /**
   * Create a new parameter with a name, and optionally a description.
   *
   * @param string $name
   *  The name of the parameter. This is used to fetch the parameter
   *  from the server.
   * @param string $description
   *  A human-readible description of what this parameter is used for.
   *  This is used to automatically generate assistance.
   */
  public function __construct($name, $description = '') {
    $this->name = $name;
    $this->description = $description;
  }

  /**
   * Add a filter to this parameter.
   *
   * A parameter can have any number of filters. Filters are used to
   * either clean (sanitize) a value or check (validate) a value. In the first
   * case, the system will attempt to remove bad data. In the second case, the
   * system will merely check to see if the data is acceptable.
   *
   * Fortissimo supports all of the filters supplied by PHP. For a complete
   * list, including valide options, see
   * {@link http://us.php.net/manual/en/book.filter.php}.
   *
   * Filters each have options, and the options can augment filter behavior, sometimes
   * in remarkable ways. See {@link http://us.php.net/manual/en/filter.filters.php} for
   * complete documentation on all filters and all of their options.
   *
   * @param string $filter
   *  One of the predefined filter types supported by PHP. You can obtain the list
   *  from the PHP builtin function {@link filter_list()}. Here are values currently
   *  documented:
   *  - int: Checks whether a value is an integer.
   *  - boolean: Checks whether a value is a boolean.
   *  - float: Checks whether a value is an integer (optionally, in a range).
   *  - validate_regexp: Check whether a parameter's value matches a given regular expression.
   *  - validate_url: Checks whether a URL is valid.
   *  - validate_email: Checks whether a value is a valid email address.
   *  - validate_ip: Checks whether a value is a valid IP address.
   *  - string: Sanitizes a string, strips tags, can encode or strip special characters.
   *  - stripped: Same as 'string'
   *  - encoded: URL-encodes a string
   *  - special_chars: XML/HTML entity-encodes special characters.
   *  - unsafe_raw: Does nothing (can optionally encode/strip special chars)
   *  - email: Removes non-Email characters
   *  - url: Removes non-URL characters
   *  - number_int: Removes anything that is not a digit or a sign (+ or -).
   *  - number_float: Removes anything except digits, signs, . , e and E.
   *  - magic_quotes: Run {@link addslashes()}.
   *  - callback: Use the given callback to filter.
   * @param mixed $options
   *  This can be either an array or an OR'd list of flags, as specified in the
   *  PHP documentation.
   * @return BaseFortissimoCommandParameter
   *  Returns this object to facilitate chaining.
   */
  public function addFilter($filter, $options = NULL) {
    $this->filters[] = array('type' => $filter, 'options' => $options);
    return $this;
  }

  /**
   * Set all filters for this object.
   * Validators must be in the form:
   * <?php
   * array(
   *   array('type' => FILTER_SOME_CONST, 'options' => array('some'=>'param')),
   *   array('type' => FILTER_SOME_CONST, 'options' => array('some'=>'param'))
   * );
   * ?>
   * @param array $filters
   *  An indexed array of validator specifications.
   * @return BaseFortissimoCommandParameter
   *  Returns this object to facilitate chaining.
   */
  public function setFilters($filters) {
    $this->filters = $filters;
    return $this;
  }



  public function setRequired($required) {
    $this->required = $required;
  }

  public function isRequired() {return $this->required;}

  /**
   * Set the default value.
   */
  public function setDefault($val) {
    $this->defaultValue = $val;
  }

  /**
   * Get the default value.
   */
  public function getDefault() {
    return $this->defaultValue;
  }

  /**
   * Get the list of filters.
   * @return array
   *  An array of the form specified in setFilters().
   */
  public function getFilters() { return $this->filters; }
  public function getName() { return $this->name; }
  public function getDescription() { return $this->description; }
}

/**
 * Any class that implements Explainable must return a string that describes,
 * in human readable language, what it does.
 *
 * @see BaseFortissimoCommand
 */
interface Explainable {
  /**
   * Provides a string explaining what this class does.
   *
   * @return string
   *  A string explaining the role of the class.
   */
  public function explain();
}
