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

/**
 * Classes that implement this advertise that they support event listening support.
 *
 * Commands in Fortissimo may optionally support an events model in which the
 * command fires events that other classes may then respond to.
 *
 * The core event system is part of Fortissimo proper, but commands may or may
 * not choose to declare (or answer) any events. Commands that extend
 * BaseFortissimoCommand can very easily declare and answer events. Those that do
 * not will need to provide their own event management, adhering to this interface.
 */
interface Observable {
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
  public function setEventHandlers($listeners);

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
  public function fireEvent($eventName, $data = NULL);
}

/**
 * Classes that implement this advertise that their output can be cached.
 *
 * Simply implementing this in no way results in the results being cached. There must be a
 * caching mechanism that receives the data and caches it.
 *
 * For example, BaseFortissimoCommand is capable of understanding Cacheable objects. When
 * a BaseFortissimCommand::doCommand() result is returned, if a cache key can be generated
 * for it, then its results will be cached.
 *
 * To implement and configure caching:
 * - Make your BaseFortissimoCommand class implement Cacheable
 * - Set up a cache with Config::cache()
 *
 * When the command is executed, its results will be stored in cache. The next time the command
 * is executed, it will first attempt to use the cached copy (unless that copy is gone or
 * expired). If a copy is found, it is returned, otherwise a new copy is generated.
 */
interface Cacheable {

  /**
   * Return a cache key.
   *
   * The key is assumed to uniquely describe a specific piece of data. Prefixes may be added
   * to the key according to the caching manager.
   *
   * If a Cacheable object returns a cache key from this function, the underlying system is
   * considered to be allowed to cache the object's output.
   *
   * Note that the exact data that is cached will be based not on this interface, but on the
   * caching mechanism used. For example, BaseFortissimoCommand caches the output of the
   * BaseFortissimoCommand::doCommand() method.
   *
   * @return string
   *  Cache key or NULL if (a) no key can be generated, or (b) this object should not be cached.
   *
   */
  public function cacheKey();

  /**
   * Indicates how long the item should be stored.
   *
   * Implementations of this method return an integer value that indicates how long an item
   * should live in the cache before it is expired.
   *
   * @return int
   *  The duration (in seconds) that this item should be cached. Note that different cache backends
   *  may interpret edge values (0, -1) differently. Returning NULL will result in Fortissimo
   *  using the default for the underlying cache mechanism.
   */
  public function cacheLifetime();

  /**
   * Indicates which cache to use.
   *
   * Fortissimo supports multiple caches, all of which are managed by the FortissimoCacheManager.
   * This method allows a Cacheable object to declare which cache it uses.
   *
   * Returning NULL will allow the default behavior to take effect.
   *
   * @return string
   *  The name of the cache. If this is NULL, then the default cache will be used.
   */
  public function cacheBackend();


  /**
   * Indicate whether or not the current command is caching.
   *
   * This provides a standard mechanism for indicating whether or not a particular
   * Cacheable instance is allowed to be cached. Implementors can, for example,
   * implement a configuration parameter that will enable or disable caching.
   *
   * Typically, when the request handing subsystem test an object to see if it is
   * able to be cached, the following should all be true:
   *
   * - Fortissimo ought to have a suitable cache provided (e.g. Config::cache())
   * - The command should implement Cacheable
   * - isCaching() should return TRUE
   * - cacheKey() should return a string value
   *
   * Note that this flag is checked after the command is initialized, but before the
   * command is executed. Any changes that the command makes to this value during
   * the command's execution will be ignored.
   *
   * @return boolean
   *  TRUE if this command is in caching mode, FALSE if this command object is
   *  disallowing cached output.
   */
  public function isCaching();
}

/**
 * This is a base class that can be extended to add new commands.
 *
 * The class provides several basic services.
 *
 * First, it simplifies the
 * process of executing a command. The BaseFortissimoCommand::doCommand()
 * method follows a very simple pattern.
 *
 * Second, it provides structure for describing a command. The abstract
 * BaseFortissimoCommand::expects() method provides the facilities for
 * describing what parameters this command should use, how these parameters should
 * be filtered/validated/sanitized, and what each parameter is for.
 *
 * Third, using the data from BaseFortissimoCommand::expects(), this
 * class provides a self-documenting tool, BaseFortissimoCommand::explain(),
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
abstract class BaseFortissimoCommand implements FortissimoCommand, Explainable, Observable {

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
   * The request-wide execution context ({@link FortissimoExecutionContext}).
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
   * Construct a new BaseFortissimoCommand.
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
   * This is a convenience wrapper that fetches the FortissimoRequestMapper and
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
   * {@link FortissimoExecutionContext} for the current request. When
   * a Fortissimo command extending {@link BaseFortissimoCommand} returns,
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

  public function execute($params, FortissimoExecutionContext $cxt) {
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
   * @throws FortissimoException
   *  If the filter fails, an exception is thrown. Note that
   *  FILTER_VALIDATE_BOOLEAN will not throw an exception if it fails. Instead,
   *  if converts values to FALSE. This is a limitation in the PHP
   *  filter library, where a failed filter always returns FALSE.
   *
   * @see validate()
   */
  public function handleIllegalParameter($name, $filter, $payload, $options) {
    $msg = "Filter %s failed for %s (options: %s)";
    throw new FortissimoException(sprintf($msg, $filter, $name, print_r($options, TRUE)));
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
   *  BaseFortissimoCommandParameter::addFilter() for a list of names,
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
   *  And array of {@link BaseFortissimoCommandParameter} objects which
   *  will be used to determine what parameters this object needs.
   *
   * @see BaseFortissimoCommand::expects()
   * @see BaseFortissimoCommand::describe()
   */
  protected function prepareParameters($params) {
    $this->parameters = array();

    // Gets the list of BaseFortissimoCommandParameter objects and loops
    // through them, loading the parameters into the object.
    $expecting = $this->expects();
    foreach ($expecting as $paramObj) {
      $name = $paramObj->getName();
      $filters = $paramObj->getFilters();
      if (!isset($params[$name])) {
        if ($paramObj->isRequired()) {
          throw new FortissimoException(sprintf('Expected param %s in command %s', $name, $this->name));
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
   * of what this command does. The resulting {@link BaseFortissimoCommandParameterCollection}
   * object that is returned should be used to set which parameters this command
   * expects to receive.
   *
   * @param string $string
   *  A description.
   * @return BaseFortissimoCommandParameterCollection
   *  An object for configuring this command.
   * @see BaseFortissimoCommand::expects();
   * @see BaseFortissimoCommand::explain();
   */
  public function description($string) {
    $this->paramsCollection = new BaseFortissimoCommandParameterCollection($string);
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
      throw new FortissimoException('No information for ' . get_class($this));
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
        throw new FortissimoInterruptException('Attempting to call uncallable item ' . (string)$callable);
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
   *  - FortissimoEcho: Basic example
   *  - FortissimoRedirect: Example using filters and regular expressions
   *  - FortissimoTemplate: Sophisticated example with lots of parameters
   *
   * The above describes a command that expects two parameters: name and email.
   * The name command is validated with the string sanitizer, which makes sure
   * that the string doesn't have markup in it.
   * The email command is first sanitized against the email sanitizer, then it
   * is checked against the email validator to make sure that it is a legitimate
   * email address.
   *
   * @return BaseFortissimoCommandParameterCollection
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
   *  - $context: The FortissimoExecutionContext object for this request.
   * @return mixed
   *  A value to be placed into the execution environment. The value can be retrieved
   *  using <code>$cxt->get($name)</code>, where <code>$name</code> is the value of this
   *  object's $name variable.
   * @throws FortissimoException
   *  Thrown when an error occurs, but the application should continue.
   * @throws FortissimoInterrupt
   *  Thrown when this command should terminate the request. This is a NON-ERROR condition.
   * @throws FortissimoInterruptException
   *  Thrown when a fatal error occurs and the request should terminate.
   */
  abstract public function doCommand();
}

/**
 * Stores information about Fortissimo commands.
 *
 * The configuration is typically created by the Config class, which provides a
 * fluent interface for configuring Fortissimo.
 */
class FortissimoConfig {

  protected $config;

  /**
   * Construct a new configuration object.
   *
   * This loads a PHP file containing a configuration
   *
   * @param string $configurationFile
   *  A file with configuration data. This will be included into the running program.
   *
   * @see http://api.querypath.org/docs
   */
  public function __construct($configurationFile = NULL) {

    if (is_string($configurationFile)) {
      include $configurationFile;
    }
    // This is useful for embedded Fortissimo instances and for unit testing.
    /* Also, it's unnecessary.
    elseif (is_array($configurationFile)) {
      Config::initialize($configurationFile);
    }
    */

    $this->config = Config::getConfiguration();
  }

  /**
   * Get an array of additional paths to be added to the include path.
   *
   * Fortissimo uses an autoloader to load classes into the engine. This
   * loader can use additional paths. To pass additional paths to Fortissimo,
   * add them using the <code>include</code> element in the commands.xml file.
   *
   * @return array
   *  An array of include paths as defined in the command configuration
   *  (commands.xml).
   */
  public function getIncludePaths() {
    return $this->config[Config::PATHS];
  }

  public function getRequestMapper($default = 'FortissimoRequestMapper') {
    if (isset($this->config[Config::REQUEST_MAPPER])) {
      return $this->config[Config::REQUEST_MAPPER];
    }
    return $default;
  }

  /**
   * Check whether the named request is known to the system.
   *
   * @param string $requestName
   *  The name of the request to check.
   * @param boolean $allowInternalRequests
   *  If this is TRUE, this will allow internal request names. Otherwise, it will flag internal
   *  requests as having illegal names.
   * @return boolean
   *  TRUE if this is a known request, false otherwise.
   */
  public function hasRequest($requestName, $allowInternalRequests = FALSE){
    if (!self::isLegalRequestName($requestName, $allowInternalRequests))  {
      throw new FortissimoException('Illegal request name.');
    }
    return isset($this->config[Config::REQUESTS][$requestName]);
  }

  /**
   * Validate the request name.
   *
   * A request name can be any combination of one or more alphanumeric characters,
   * together with dashes (-) and underscores (_).
   *
   * @param string $requestName
   *  The name of the request. This value will be validated according to the rules
   *  explained above.
   * @param boolean $allowInternalRequests
   *  If this is set to TRUE, the checking will be relaxed to allow at-requests.
   * @return boolean
   *  TRUE if the name is legal, FALSE otherwise.
   */
  public static function isLegalRequestName($requestName, $allowInternalRequests = FALSE) {
    $regex = $allowInternalRequests ? '/^@?[_a-zA-Z0-9\\-]+$/' : '/^[_a-zA-Z0-9\\-]+$/';

    return preg_match($regex, $requestName) == 1;
  }

  /**
   * Get all loggers.
   *
   * This will load all of the loggers from the command configuration
   * (typically commands.xml) and return them in an associative array of
   * the form array('name' => object), where object is a FortissimoLogger
   * of some sort.
   *
   * @return array
   *  An associative array of name => logger pairs.
   * @see FortissimoLogger
   */
  public function getLoggers() {
    $loggers = $this->getFacility(Config::LOGGERS);

    foreach ($loggers as $logger) $logger->init();

    return $loggers;
  }

  /**
   * Get all caches.
   *
   * This will load all of the caches from the command configuration
   * (typically commands.php) and return them in an associative array of
   * the form array('name' => object), where object is a FortissimoRequestCache
   * of some sort.
   *
   * @return array
   *  An associative array of name => cache pairs.
   * @see FortissimoRequestCache
   */
  public function getCaches() {
    $caches = $this->getFacility(Config::CACHES);
    foreach ($caches as $cache) $cache->init();
    return $caches;
  }

  public function getDatasources() {
    return $this->getFacility(Config::DATASOURCES);
  }

  /**
   * Internal helper function.
   *
   * @param string $type
   *  The type of item to retrieve. Use the Config class constants.
   * @return array
   *  An associative array of the form <code>array('name' => object)</code>, where
   *  the object is an instance of the respective 'invoke' class.
   */
  protected function getFacility($type = Config::LOGGERS) {
    $facilities = array();
    foreach ($this->config[$type] as $name => $facility) {
      $klass = $facility['class'];
      $params = isset($facility['params']) ? $this->getParams($facility['params']) : array();
      $facilities[$name] = new $klass($params, $name);
    }
    return $facilities;
  }

  /**
   * Get the parameters for a facility such as a logger or a cache.
   *
   * @param array $params
   *  Configuration for the given facility.
   * @return array
   *  An associative array of param name/values. @code<param name="foo">bar</param>@endcode
   *  becomes @code array('foo' => 'bar') @endcode.
   */
  protected function getParams(array $params) {
    $res = array();
    // Basically, for facility params we just collapse the array.
    foreach ($params as $name => $values) {
      $res[$name] = $values['value'];
    }
    return $res;
  }

  /**
   * Given a request name, retrieves a request queue.
   *
   * The queue (in the form of an array) contains information about what
   * commands should be run, and in what order.
   *
   * @param string $requestName
   *  The name of the request
   * @param boolean $allowInternalRequests
   *  If this is true, internal requests (@-requests, at-requests) will be allowed.
   * @return FortissimoRequest
   *  A queue of commands that need to be executed. See {@link createCommandInstance()}.
   * @throws FortissimoRequestNotFoundException
   *  If no such request is found, or if the request is malformed, and exception is
   *  thrown. This exception should be considered fatal, and a 404 error should be
   *  returned. Note that (provisionally) a FortissimoRequestNotFoundException is also thrown if
   *  $allowInternalRequests if FALSE and the request name is for an internal request. This is
   *  basically done to prevent information leakage.
   */
  public function getRequest($requestName, $allowInternalRequests = FALSE) {

    // Protection against attempts at request hacking.
    if (!self::isLegalRequestName($requestName, $allowInternalRequests))  {
      throw new FortissimoRequestNotFoundException('Illegal request name.');
    }

    if (empty($this->config[Config::REQUESTS][$requestName])) {
      // This should be treated as a 404.
      throw new FortissimoRequestNotFoundException(sprintf('Request %s not found', $requestName));
      //$request = $this->config[Config::REQUESTS]['default'];
    }
    else {
      $request = $this->config[Config::REQUESTS][$requestName];
    }

    $isCaching = isset($request['#caching']) && filter_var($request['#caching'], FILTER_VALIDATE_BOOLEAN);
    $isExplaining = isset($request['#explaining']) && filter_var($request['#explaining'], FILTER_VALIDATE_BOOLEAN);

    unset($request['#caching'], $request['#explaining']);

    // Once we have the request, find out what commands we need to execute.
    $commands = array();
    foreach ($request as $cmd => $cmdConfig) {
      $commands[] = $this->createCommandInstance($cmd, $cmdConfig);
    }

    $request = new FortissimoRequest($requestName, $commands);
    $request->setCaching($isCaching);
    $request->setExplain($isExplaining);

    return $request;
  }

  /**
   * Create a command instance.
   *
   * Retrieve command information from the configuration file and transform these
   * into an internal data structure.
   *
   * @param string $cmd
   *  Name of the command
   * @param array $config
   *  Command configuration
   * @return array
   *  An array with the following keys:
   *  - name: Name of the command
   *  - class: Name of the class
   *  - instance: An instance of the class
   *  - params: Parameter information. Note that the application must take this
   *    information and correctly populate the parameters at execution time.
   *    Parameter information is returned as an associative array of arrays:
   *    <?php $param['name'] => array('from' => 'src:name', 'value' => 'default value'); ?>
   *  - listeners: Information on event listeners and what they are listening for.
   * @throws FortissimoException
   *  In the event that a paramter does not have a name, an exception is thrown.
   */
  protected function createCommandInstance($cmd, $config) {
    $class = $config['class'];
    if (empty($class)) {
      throw new FortissimoConfigurationException('No class specified for ' . $cmd);
    }

    $cache = isset($config['caching']) && filter_var($config['caching'], FILTER_VALIDATE_BOOLEAN);
    $params = isset($config['params']) ? $config['params'] : array();
    $listeners = isset($config['listeners']) ? $config['listeners'] : array();

    $inst = new $class($cmd, $cache);
    return array(
      'isCaching' => $cache,
      'name' => $cmd,
      'class' => $class,
      'instance' => $inst,
      'params' => $params,
      'listeners' => $listeners,
    );
  }

  /**
   * Get the configuration information.
   *
   * @return array
   *  The configuration information
   */
  public function getConfig() {
    return $this->config;
  }

}