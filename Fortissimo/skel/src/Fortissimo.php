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
 * For optimal performance, use this instead of {@link time()}
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
    
    // Create the log manager.
    $this->logManager = new FortissimoLoggerManager($this->commandConfig->getLoggers());
    
    // Create cache manager.
    $this->cacheManager = new FortissimoCacheManager($this->commandConfig->getCaches());
    
    // Create the datasource manager.
    $this->datasourceManager = new FortissimoDatasourceManager($this->commandConfig->getDatasources());
    
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
   * executing commands.
   *
   * @param string $identifier
   *  A named identifier, typically a URI. By default (assuming ForitissimoRequestMapper has not 
   *  been overridden) the $identifier should be a request name.
   * @param FortissimoExecutionContext $initialCxt
   *  If an initialized context is necessary, it can be passed in here.
   */
  public function handleRequest($identifier = 'default', FortissimoExecutionContext $initialCxt = NULL) {
    
    // Experimental: Convert errors (E_ERROR | E_USER_ERROR) to exceptions.
    set_error_handler(array('FortissimoErrorException', 'initializeFromError'), 257);
    
    // Load the request.
    try {
      // Use the mapper to determine what the real request name is.
      $requestName = $this->requestMapper->uriToRequest($identifier);
      $request = $this->commandConfig->getRequest($requestName);
    }
    catch (FortissimoRequestNotFoundException $nfe) {
      // Need to handle this case.
      $this->logManager->log($nfe, self::LOG_USER);
      $requestName = $this->requestMapper->uriToRequest('404');
      
      if ($this->commandConfig->hasRequest($requestName)) {
        $request = $this->commandConfig->getRequest($requestName);
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
        $this->cacheManager
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
        
        // Forward the request to another handler.
        $this->handleRequest($forward->destination(), $forward->context());
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
  
  public function __construct($name, $caching = FALSE) {
    $this->name = $name;
    $this->caching = $caching;
  }
  
  /**
   * By default, a Fortissimo base command is cacheable.
   *
   * @return boolean
   *  Returns TRUE unless a subclass overrides this.
   */
  public function isCacheable() {
    return TRUE;
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
    if ($this instanceof Cacheable && ($key = $this->cacheKey()) != NULL) {
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
   * @return boolean
   *  TRUE if this is a known request, false otherwise.
   */
  public function hasRequest($requestName){
    if (!self::isLegalRequestName($requestName))  {
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
   * @return boolean
   *  TRUE if the name is legal, FALSE otherwise.
   */
  public static function isLegalRequestName($requestName) {
    return preg_match('/^[_a-zA-Z0-9\\-]+$/', $requestName) == 1;
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
   * (typically commands.xml) and return them in an associative array of 
   * the form array('name' => object), where object is a FortissimoRequestCache
   * of some sort.
   * 
   * @return array
   *  An associative array of name => logger pairs.
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
      $facilities[$name] = new $klass($params);
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
   * @return FortissimoRequest 
   *  A queue of commands that need to be executed. See {@link createCommandInstance()}.
   * @throws FortissimoRequestNotFoundException
   *  If no such request is found, or if the request is malformed, and exception is 
   *  thrown. This exception should be considered fatal, and a 404 error should be 
   *  returned.
   */
  public function getRequest($requestName) {
    
    // Protection against attempts at request hacking.
    if (!self::isLegalRequestName($requestName))  {
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
      throw new FortissimoConfigException('No class specified for ' . $cmd);
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

/**
 * Tracks context information over the lifecycle of a request's execution.
 *
 * An execution context is passed from command to command during the course of 
 * a request's execution. State information is inserted into the context by 
 * various commands. Certain commands may also take data out of the context, though
 * this operation is not without its risks. Finally, objects may use information
 * found in the context, either to perform some operation (writing data to 
 * the client) or to modify the context data.
 *
 * The idea of the context is to provide three things during the course of the 
 * request(s):
 * - Shared access to data being generated.
 * - Common access to the logging system (see FortissimoLoggerManager).
 * - Common access to the datasources (See FortissimoDatasourceManager).
 * - Access to the underlying cache engine (so commands can cache their own data). 
 *   See FortissimoCacheManager.
 * - Access to the request mapper. See FortissimoRequestMapper.
 *
 * Thus, every command can utilize the loggers and datasources defined for the
 * application, and commands can pass data throughout the lifecycle of the request.
 *
 * Note that when one request forwards to another request, the context may be 
 * transferred along with it. Thus, sometimes a context will span multiple 
 * defined requests (though this will always be in the handling of one 
 * client serving operation -- i.e., it will only span one HTTP request, even if
 * multiple Fortissimo requests are fired.)
 *
 * @see Fortissimo
 */
class FortissimoExecutionContext implements IteratorAggregate {
  
  // Why do we create a class that is basically a thin wrapper around an array?
  // Three reasons:
  // 1. It gives us the ability to control access to the objects in the context.
  // 2. It gives us the ability to add validation and other features
  // 3. It eliminates the need to do overt pass-by-reference of a context array,
  //   which is likely to cause confusion with less experienced developers.
  // However, we do provide the to/from array methods to allow developers to make
  // use of the richer array library without our re-inventing the wheel.
  
  protected $data = NULL;
  protected $logger = NULL;
  protected $datasources = NULL;
  protected $cacheManager = NULL;
  protected $requestMapper = NULL;
  /** Command cache. */
  protected $cache = array();
  protected $caching = FALSE;
  
  /**
   * Create a new context.
   *
   * @param array $initialContext
   *  An associative array of context pairs.
   * @param FortissimoLoggerManager $logger
   *  The logger.
   * @param FortissimoDatasourceManager $datasources
   *  The manager for all datasources declared for this request.
   * @param FortissimoCacheManager $cacheManager
   *  The manager for all caches. Commands may use this to store or retrieve cached content.
   * @param FortissimoRequestMapper $requestMapper
   *  The request mapper used on this request. A request mapper should know how to construct
   *  a URL to the app.
   */
  public function __construct($initialContext = array(), FortissimoLoggerManager $logger = NULL, FortissimoDatasourceManager $datasources = NULL, FortissimoCacheManager $cacheManager = NULL, $requestMapper = NULL) {
    if ($initialContext instanceof FortissimoExecutionContext) {
      $this->data = $initialContext->toArray();
    }
    else {
      $this->data = $initialContext;
    }
    
    // Store logger and datasources managers if they are set.
    if (isset($logger)) $this->logger = $logger;
    if (isset($datasources)) $this->datasources = $datasources;
    if (isset($cacheManager)) $this->cacheManager = $cacheManager;
    if (isset($requestMapper)) $this->requestMapper = $requestMapper;
  }
  
  /**
   * Log a message.
   * The context should always have a hook into a logger of some sort. This method
   * passes log messages to the underlying logger.
   *
   * @param mixed $msg
   *  The message to log. This can be a string or an Exception.
   * @param string $category
   *  A category. Typically, this is a string like 'error', 'warning', etc. But 
   *  applications can customize their categories according to the underlying
   *  logger.
   * @see FortissimoLoggerManager Manages logging facilities.
   * @see FortissimoLogger Describes a logger.
   */
  public function log($msg, $category) {
    if (isset($this->logger)) {
      $this->logger->log($msg, $category);
    }
  }
  
  /**
   * Retrieve a named datasource.
   *
   * If no name is passed, this will try to retrieve the default datasource.
   *
   * @param string $name
   *  The name of the datasource to retrieve. If no name is given, the default
   *  datasource will be used.
   * @return FortissimoDatasource
   *  The requested datasource, or NULL if none is found.
   */
  public function datasource($name = NULL) {
    return $this->datasources->datasource($name);
  }
  
  /**
   * Convenience function for {@link datasource()}.
   */
  public function ds($name = NULL) {
    return $this->datasource($name);
  }
  
  /**
   * Check if the context has an item with the given name.
   *
   * @param string $name
   *  The name of the item to check for.
   */
  public function has($name) {
    return isset($this->data[$name]);
  }
  
  /**
   * Get the size of the context.
   *
   * @return int
   *  Number of items in the context.
   */
  public function size() {
    return count($this->data);
  }
  
  /**
   * Add a new name/value pair to the context.
   *
   * This will replace an existing entry with the same name. To check before
   * writing, use {@link has()}.
   *
   * @param string $name
   *  The name of the item to add.
   * @param mixed $value
   *  Some value to add. This can be a primitive, an object, or a resource. Note
   *  that storing resources is not serializable.
   */
  public function add($name, $value) {
    $this->data[$name] = $value;
  }
  //public function put($name, $value) {$this->add($name, $value);}
  
  /**
   * Add all values in the array.
   *
   * This will replace any existing entries with the same name.
   *
   * @param array $array
   *  Array of values to merge into the context.
   */
  public function addAll($array) {
    $this->data = $array + $this->data;
  }
  
  /**
   * Get a value by name.
   *
   * This fetches an item out of the context and returns a reference to it. A 
   * reference is returned so that one can modify the value. But this introduces a risk: You 
   * can accidentally modify the context value if you are not careful.
   *
   * If you are working with a non-object and you want to use it by reference, use the following 
   * syntax:
   * @code
   * $foo =& $context->get('foo');
   * @endcode
   *
   * @return mixed
   *  A reference to the value in the context, or NULL if $name was not found.
   */
  public function &get($name) {
    $var = NULL;
    if (isset($this->data[$name])) {
      $var =  &$this->data[$name];
    }
    return $var;
  }
  
  /**
   * Remove an item from the context.
   *
   * @param string $name
   *  The thing to remove.
   */
  public function remove($name) {
    if (isset($this->data[$name])) unset($this->data[$name]);
  }
  
  /**
   * Convert the context to an array.
   *
   * @return array
   *  Associative array of name/value pairs.
   */
  public function toArray() {
    return $this->data;
  }
  
  /**
   * Replace the current context with the values in the given array.
   *
   * @param array $contextArray
   *  An array of new name/value pairs. The old context will be destroyed.
   */
  public function fromArray($contextArray) {
    $this->data = $contextArray;
  }

  /**
   * Get an iterator of the execution context.
   *
   * @return Iterator
   *  The iterator of each item in the execution context.
   */
  public function getIterator() {
    // Does this work?
    return new ArrayIterator($this->data);
  }
  
  /**
   * Expose the logger manager to commands.
   *
   * The logger manager is responsible for managing all of the underlying
   * loggers. This method provides access to the logger manager. For integrity
   * purposes, it is advised that loggers not be re-configured by commands.
   *
   * @return FortissimoLoggerManager
   *  The logger manager for the current server.
   */
  public function getLoggerManager() {
    return $this->logger;
  }
  
  /**
   * Get the datasource manager.
   *
   * The datasource manager is manages all of the datasources defined in 
   * this Fortissimo instance (typically defined in commands.xml).
   *
   * Often, you will want to get datasources with the {@link datasource()} function
   * defined in this class. Sometimes, though, you may need more control over 
   * the datasource. This method provides direct access to the manager, which 
   * will give you a higher degree of control.
   *
   * @return FortissimoDatasourceManager
   *  An initialized datasource manager.
   */
  public function getDatasourceManager() {
    //throw new Exception('Not implemented.');
    return $this->datasourceManager;
  }
  
  /**
   * Get the FortissimoCacheManager for this request.
   *
   * Fortissimo provides facilities for providing any number of caches. All of the caches are
   * managed by a FortissimoCacheManager instance. This returns a handle to the manager, from
   * which tools can operate on caches.
   *
   * @return FortissimoCacheManager
   *  The current cache manager.
   */
  public function getCacheManager() {
    return $this->cacheManager;
  }
  
  /**
   * Get the FortissimoRequestMapper for this request.
   *
   * The Request Mapper maps requests to URLs and URLs to requests. It can be used
   * for constructing URLs to other parts of the app.
   */
  public function getRequestMapper() {
    return $this->requestMapper;
  }
}

/**
 * Manage caches.
 *
 * This manages top-level {@link FortissimoRequestCache}s. Just as with 
 * {@link FortissimoLoggerManager}, a FortissimoCacheManager can manage
 * multiple caches. It will proceed from cache to cache in order until it
 * finds a hit. (Order is determined by the order returned from the 
 * configuration object.)
 *
 * Front-line Fortissimo caching is optimized for string-based values. You can,
 * of course, serialize values and store them in the cache. However, the 
 * serializing and de-serializing is left up to the implementor.
 *
 * Keys may be hashed for optimal storage in the database. Values may be optimized
 * for storage in the database. All details of caching algorithms, caching style
 * (e.g. time-based, LRU, etc.) is handled by the low-level caching classes.
 *
 * @see FortissimoRequestCache For details on caching.
 */
class FortissimoCacheManager {
  protected $caches = NULL;
  
  public function __construct($caches) {
    $this->caches = $caches;
  }
  
  /**
   * Given a name, retrieve the cache.
   *
   * @return FortissimoRequestCache
   * If there is a cache with this name, the cache object will be 
   * returned. Otherwise, this will return NULL.
   */
  public function getCacheByName($name) {
    return $this->caches[$name];
  }
  
  /**
   * Get the default cache.
   */
  public function getDefaultCache() {
    foreach ($this->caches as $name => $cache) {
      if ($cache->isDefault()) return $cache;
    }
  }
  
  /**
   * Get a value from the caches.
   *
   * This will read sequentially through each defined cache until it
   * finds a match. If no match is found, this will return NULL. Otherwise, it
   * will return the value.
   */
  public function get($key) {
    foreach ($this->caches as $n => $cache) {
      $res = $cache->get($key);
      
      // Short-circuit if we find a value.
      if (isset($res)) return $res;
    }
  }
  
  /**
   * Store a value in a cache.
   *
   * This will write a value to a cache. If a cache name is given
   * as the third parameter, then that named cache will be used.
   *
   * If no cache is named, the value will be stored in the first available cache.
   *
   * If no cache is found, this will silently continue. If a name is given, but the
   * named cache is not found, the next available cache will be used.
   *
   * @param string $key
   *  The cache key
   * @param string $value
   *  The value to store
   * @param string $cache
   *  The name of the cache to store the value in. If not given, the cache 
   *  manager will store the item wherever it is most convenient.
   * @param int $expires_after
   *  An integer indicating how many seconds this item should live in the cache. Some
   *  caching backends may choose to ignore this. Some (like pecl/memcache, pecl/memcached) may 
   *  have an upper limit (30 days). Setting this to NULL will invoke the caching backend's
   *  default.
   */
  public function set($key, $value, $expires_after = NULL, $cache = NULL) {
    
    // If a named cache key is found, set:
    if (isset($cache) && isset($this->caches[$cache])) {
      return $this->caches[$cache]->set($key, $value, $expires_after);
    }
    
    // XXX: Right now, we just use the first item in the cache:
    /*
    $keys = array_keys($this->caches);
    if (count($keys) > 0) {
      return $this->caches[$keys[0]]->set($key, $value, $expires_after);
    }
    */
    $cache = $this->getDefaultCache();
    if (!empty($cache)) $cache->set($key, $value, $expires_after);
  }
  
  /**
   * Check whether the value is available in a cache.
   *
   * Note that in most cases, running {@link has()} before running
   * {@link get()} will result in the same access being run twice. For 
   * performance reasons, you are probably better off calling just
   * {@link get()} if you are accessing a value.
   *
   * @param string $key
   *  The key to check for.
   * @return boolean
   *  TRUE if the key is found in the cache, false otherwise.
   */
  public function has($key) {
    foreach ($this->caches as $n => $cache) {
      $res = $cache->has($key);
      
      // Short-circuit if we find a value.
      if ($res) return $res;
    }
  }
  
  /**
   * Check which cache (if any) contains the given key.
   *
   * If you are just trying to retrieve a cache value, use {@link get()}.
   * You should use this only if you are trying to determine which underlying 
   * cache holds the given value.
   *
   * @param string $key
   *   The key to search for.
   * @return string
   *  The name of the cache that contains this key. If the key is 
   *  not found in any cache, NULL will be returned.
   */
  public function whichCacheHas($key) {
    foreach ($this->caches as $n => $cache) {
      $res = $cache->has($key);
      
      // Short-circuit if we find a value.
      if ($res) return $n;
    }
  }
}

/**
 * Manages data sources.
 *
 * Fortissimo provides facilities for declaring multiple data sources. A 
 * datasource is some readable or writable backend like a database.
 *
 * This class manages multiple data sources, providing the execution context
 * with a simple way of retrieving datasources by name.
 */
class FortissimoDatasourceManager {
  
  protected $datasources = NULL;
  protected $initMap = array();
  
  /**
   * Build a new datasource manager.
   *
   * @param array $config
   *  The configuration for this manager as an associative array of 
   *  names=>instances.
   */
  public function __construct($config) {
    $this->datasources = &$config;
  }
  
  /**
   * Get a datasource by its string name.
   *
   * @param string $name
   *  The name of the datasource to get.
   * @return FortissimoDatasource
   *  The requested source, or NULL if no such source exists.
   */
  public function getDatasourceByName($name) {
    return $this->datasources[$name];
  }
  
  /**
   * Scan the datasources and return the first one marked default.
   *
   * Note that this does not make sure that datasources have been initialized.
   * @return FortissimoDatasource
   *  An initialized FortissimoDatasource, or NULL if no default is found.
   */
  protected function getDefaultDatasource() {
    foreach ($this->datasources as $k => $o) if ($o->isDefault()) return $o;
  }
  
  /**
   * Get a datasource.
   *
   * If a name is given, retrieve the named datasource. Otherwise, return
   * the default. If no suitable datasource is found, return NULL.
   *
   * @param string $name
   *  The name of the datasource to return.
   * @return FortissimoDatasource
   *  The datasource.
   */
  public function datasource($name = NULL) {
    $ds = NULL;
    if (empty($name)) {
      $ds = $this->getDefaultDatasource();
    }
    else {
      $ds = $this->getDatasourceByName($name);
    }
    
    // We initialize lazily so that datasources do not
    // have resources allocated until necessary.
    if (!empty($ds) && !isset($this->initMap[$name])) {
      $ds->init();
      $this->initMap[$name] = TRUE;
    }
    return $ds;
  }
  
  /**
   * Initialize all datasources managed by this manager instance.
   *
   * By default, datasource initialization is delayed as long as possible so that
   * resources are not allocated needlessly. On some occasions, you may want to
   * initialize all of the datasources at once. Use this function to do so.
   *
   * Keep in mind that if there are a lot of datasources, this may consume many 
   * system resources.
   */
  public function initializeAllDatasources() {
    foreach ($this->datasources as $name => $ds) {
      if (!isset($this->initMap[$name])) {
        $ds->init();
        $this->initMap[$name] = TRUE;
      }
    }
  }
  
  /**
   * Get all datasources.
   * 
   * This does not initialize resources automatically. If you need all datasources
   * to be initialized first, call initializeAllDatasources() before calling this.
   *
   * @return array
   *  Returns an associative array of datasource name=>object pairs.
   */
  public function datasources() {
    return $this->datasources;
  }
}

/**
 * Manage loggers for a server.
 *
 * A {@link Fortissimo} instance may have zero or more loggers. Loggers
 * perform the standard task of handling messages that need recording for
 * review by administrators.
 *
 * The logger manager manages the various logging instances, delegating logging
 * tasks.
 *
 */
class FortissimoLoggerManager {
  
  protected $loggers = NULL;
  
  /**
   * Build a new logger manager.
   *
   * @param QueryPath $config
   *  The configuration object. Typically, this is from commands.xml.
   */ 
  //public function __construct(QueryPath $config) {
  public function __construct($config) {
    // Initialize array of loggers.
    $this->loggers = &$config;
  }
  
  /**
   * Get a logger.
   *
   * @param string $name
   *  The name of the logger, as indicated in the configuration.
   * @return FortissimoLogger
   *  The logger corresponding to the name, or NULL if no such logger is found.
   */
  public function getLoggerByName($name) {
    return $this->loggers[$name];
  }
  
  /**
   * Get all buffered log messages.
   *
   * Some, but by no means all, loggers buffer messages for later retrieval.
   * This method provides a way of retrieving all buffered messages from all
   * buffering loggers. Messages are simply concatenated together from all of 
   * the available loggers.
   *
   * To fetch the log messages of just one logger instead of all of them, use
   * {@link getLoggerByName()}, and then call that logger's {@link FortissimoLogger::getMessages()}
   * method.
   *
   * @return array
   *  An indexed array of messages.
   */
  public function getMessages() {
    $buffer = array();
    foreach ($this->loggers as $name => $logger) {
      $buffer += $logger->getMessages();
    }
    return $buffer;
  }
  
  /**
   * Log messages.
   *
   * @param mixed $msg
   *  A string or an Exception.
   * @param string $category
   *  A string indicating what type of message is
   *  being logged. Standard values for this are:
   *  - error
   *  - warning
   *  - info
   *  - debug
   *  Your application may use whatever values are
   *  fit. However, underlying loggers may interpret
   *  these differently. 
   * @param string $details
   *   Additional information. When $msg is an exception,
   *   this will automatically be populated with stack trace 
   *   information UNLESS explicit string information is passed
   *   here.
   */
  public function log($msg, $category, $details = '') {
    foreach ($this->loggers as $name => $logger) {
      $logger->rawLog($msg, $category);
    }
  }
}

abstract class FortissimoCache {
  /**
   * The parameters for this data source
   */
  protected $params = NULL;
  protected $default = FALSE;
  
  /**
   * Construct a new datasource.
   */
  public function __construct($params = array()) {
    $this->params = $params;
    $this->default = isset($params['isDefault']) && filter_var($params['isDefault'], FILTER_VALIDATE_BOOLEAN);
  }
  
  /**
   * Determine whether this is the default cache.
   *
   * Note that this may be called *before* init().
   *
   * @return boolean
   *  Returns TRUE if this is the default. Typically the default status is 
   *  assigned in the commands.xml file.
   */
  public function isDefault() {
    return $this->default;
  }
  
  /**
   * Perform any necessary initialization.
   */
  public abstract function init();
  
  /**
   * Add an item to the cache.
   *
   * @param string $key
   *  A short (<255 character) string that will be used as the key. This is short
   *  so that database-based caches can optimize for varchar fields instead of 
   *  text fields.
   * @param string $value
   *  The string that will be stored as the value.
   * @param integer $expires_after
   *  The number of seconds that should be considered the max age of the cached item. The 
   *  details of how this is interpreted are cache dependent.
   */
  public abstract function set($key, $value, $expires_after = NULL);
  /**
   * Clear the entire cache.
   */
  public abstract function clear();
  /**
   * Delete an item from the cache.
   *
   * @param string $key
   *  The key to remove from the cache.
   */
  public abstract function delete($key);
  /**
   * Retrieve an item from the cache.
   *
   * @param string $key
   *  The key to return.
   * @return mixed
   *  The string found in the cache, or NULL if nothing was found.
   */
  public abstract function get($key);
}

/**
 * A cache for command or request output.
 *
 * This provides a caching facility for the output of entire requests, or for the 
 * output of commands. This cache is used for high-level data caching from within
 * the application.
 *
 * The Fortissimo configuration, typically found in commands.xml, must specify which
 * requests and commands are cacheable.
 *
 * The underlying caching implementation determines how things are cached, for how
 * long, and what the expiration conditions are. It will also determine where data
 * is cached.
 *
 * External caches, like Varnish or Squid, tend not to use this mechanism. Internal
 * mechanisms like APC or custom database caches would use this mechanism. Memcached
 * would also use this mechanism, if appropriate.
 *
 * @deprecated
 */
interface FortissimoRequestCache {
  
  /**
   * Perform any necessary initialization.
   */
  public function init();
  
  /**
   * Add an item to the cache.
   *
   * @param string $key
   *  A short (<255 character) string that will be used as the key. This is short
   *  so that database-based caches can optimize for varchar fields instead of 
   *  text fields.
   * @param string $value
   *  The string that will be stored as the value.
   * @param integer $expires_after
   *  The number of seconds that should be considered the max age of the cached item. The 
   *  details of how this is interpreted are cache dependent.
   */
  public function set($key, $value, $expires_after = NULL);
  /**
   * Clear the entire cache.
   */
  public function clear();
  /**
   * Delete an item from the cache.
   *
   * @param string $key
   *  The key to remove from the cache.
   */
  public function delete($key);
  /**
   * Retrieve an item from the cache.
   *
   * @param string $key
   *  The key to return.
   * @return mixed
   *  The string found in the cache, or NULL if nothing was found.
   */
  public function get($key);
}

/**
 * A datasource.
 *
 * Fortissimo provides a very general (and loose) abstraction for datasources.
 * The idea is to make it possible for all datasources -- from files to RDBs to
 * NoSQL databases to LDAPS -- to be defined in a central place (along with 
 * requests) so that they can easily be configured and also leveraged by the 
 * command configuration.
 *
 * The generality of this class makes it less than ideal for doing strict checks
 * on capabilities, but, then, that's what inheritance if for, isn't it.
 *
 * Each data source type should extend this basic class. This base class contains
 * the absolute minimal amount of information that Fortissimo needs in order to 
 * load the datasources and instruct them to initialize themselves.
 *
 * From there, it's up to implementors to build useful datasource wrappers that
 * can be leveraged from within commands.
 */
abstract class FortissimoDatasource {
  /**
   * The parameters for this data source
   */
  protected $params = NULL;
  protected $default = FALSE;
  
  /**
   * Construct a new datasource.
   */
  public function __construct($params = array()) {
    $this->params = $params;
    $this->default = isset($params['isDefault']) && filter_var($params['isDefault'], FILTER_VALIDATE_BOOLEAN);
  }
  
  /**
   * Determine whether this is the default datasource.
   *
   * Note that this may be called *before* init().
   *
   * @return boolean
   *  Returns TRUE if this is the default. Typically the default status is 
   *  assigned in the commands.xml file.
   */
  public function isDefault() {
    return $this->default;
  }
  
  /**
   * This is called once before the datasource is first used.
   *
   * While there is no guarantee that this will be called only when necessary, it
   * is lazier than the constructor, so initialization of connections may be better
   * left to this function than to overridden constructors.
   */
  public abstract function init();
  
  /**
   * Retrieve the underlying datasource object.
   *
   * Ideally, this returns the underlying data source. In some circumstances,
   * it may return NULL.
   *
   * @return mixed
   *  The underlying datasource. Example: a PDO object or a Mongo object.
   */
  public abstract function get();
}

/**
 * A logger responsible for logging messages to a particular destination.
 *
 * The FortissimoLogger abstract class does recognize one parameter.
 *
 *  - 'categories': An array or comma-separated list of categories that this logger listens for.
 *     If no categories are set, this logs ALL categories.
 *
 * Category logic is encapsulated in the method FortissimoLogger::isLoggingThisCategory(). 
 *
 * 
 */
abstract class FortissimoLogger {
  
  /**
   * The parameters for this logger.
   */
  protected $params = NULL;
  protected $facilities = NULL;
  
  /**
   * Construct a new logger instance.
   *
   * @param array $params
   *   An associative array of name/value pairs.
   */
  public function __construct($params = array()) {
    $this->params = $params;
    
    // Add support for facility declarations.
    if (isset($params['categories'])) {
      $fac = $params['categories'];
      if (!is_array($fac)) {
        $fac = explode(',', $fac);
      }
      // Assoc arrays provide faster lookups on keys.
      $this->facilities = array_combine($fac, $fac);
    }
    
  }
  
  /**
   * Return log messages.
   * 
   * Some, but not all, loggers buffer messages for retrieval later. This
   * method should be used to retrieve messages from such loggers.
   *
   * @return array
   *  An indexed array of log message strings. By default, this returns an 
   *  empty array.
   */
  public function getMessages() {
    return array();
  }
  
  /**
   * Check whether this category is being logged.
   *
   * In general, this check is run from rawLog(), and so does not need to be 
   * directly called elsewhere.
   *
   * @param string $category
   *  The category to check.
   * @return boolean
   *  TRUE if this is logging for the given category, false otherwise.
   */
  public function isLoggingThisCategory($category) {
    return empty($this->facilities) || isset($this->facilities[$category]);
  }
  
  /**
   * Handle raw log requests.
   *
   * This handles the transformation of objects (Exceptions)
   * into loggable strings. 
   *
   * @param mixed $message
   *  Typically, this is an Exception, some other object, or a string.
   *  This method normalizes the $message, converting it to a string
   *  before handing it off to the {@link log()} function.
   * @param string $category
   *  This message is passed on to the logger.
   * @param string $details
   *  A detail for the given message. If $message is an Exception, then 
   *  details will be automatically filled with stack trace information.
   */
  public function rawLog($message, $category = 'General Error', $details = '') {
    
    // If we shouldn't log this category, skip this step.
    if (!$this->isLoggingThisCategory($category)) return;
    
    if ($message instanceof Exception) {
      $buffer = $message->getMessage();
      
      if (empty($details)) {
        $details = get_class($message) . PHP_EOL;
        $details .= $message->getMessage() . PHP_EOL;
        $details .= $message->getTraceAsString();
      }
      
    }
    elseif (is_object($message)) {
      $buffer = $mesage->toString();
    }
    else {
      $buffer = $message;
    }
    $this->log($buffer, $category, $details);
    return;
  }
  
  /**
   * Initialize the logger.
   *
   * This will happen once per server construction (typically
   * once per request), and it will occur before the command is executed.
   */
  public abstract function init();
  
  /**
   * Log a message.
   *
   * @param string $msg
   *  The message to log.
   * @param string $severity
   *  The log message category. Typical values are 
   *  - warning
   *  - error
   *  - info
   *  - debug
   * @param string $details
   *  Further text information about the logged event.
   */
  public abstract function log($msg, $severity, $details);
  
}

/**
 * Indicates that a condition has been met that necessitates interrupting the command execution chain.
 *
 * This exception is not necessarily intended to indicate that something went 
 * wrong, but only htat a condition has been satisfied that warrants the interrupting
 * of the current chain of execution.
 *
 * Note that commands that throw this exception are responsible for responding
 * to the user agent. Otherwise, no output will be generated.
 *
 * Examples of cases where this might be desirable:
 * - Application should redirect (302, 304, etc.) user to another page.
 * - User needs to be prompted to log in, using HTTP auth, before continuing.
 */
class FortissimoInterrupt extends Exception {}
/**
 * Indicates that a fatal error has occured.
 *
 * This is the Fortissimo exception with the strongest implications. It indicates
 * that not only has an error occured, but it is of such a magnitude that it 
 * precludes the ability to continue processing. These should be used sparingly,
 * as they prevent the chain of commands from completing.
 *
 * Examples:
 * - A fatal error has occurred, and a 500-level error should be returned to the user.
 * - Access is denied to the user.
 * - A request name cannot be found.
 */
class FortissimoInterruptException extends Exception {}
/**
 * General Fortissimo exception.
 *
 * This should be thrown when Fortissimo encounters an exception that should be
 * logged and stored, but should not interrupt the execution of a command.
 */
class FortissimoException extends Exception {}

/**
 * Transform an error or warning into an exception.
 */
class FortissimoErrorException extends FortissimoException {
  public static function initializeFromError($code, $str, $file, $line, $cxt) {
    //printf("\n\nCODE: %s %s\n\n", $code, $str);
    $class = __CLASS__;
    throw new $class($str, $code, $file, $line);
  }
  
  public function __construct($msg = '', $code = 0, $file = NULL, $line = NULL) {    
    if (isset($file)) {
      $msg .= ' (' . $file;
      if (isset($line)) $msg .= ': ' . $line;
      $msg .= ')';
    }
    parent::__construct($msg, $code);
  }
}
/**
 * Configuration error.
 */
class FortissimoConfigurationException extends FortissimoException {}
/**
 * Request was not found.
 */
class FortissimoRequestNotFoundException extends FortissimoException {}

/**
 * Forward a request to another request.
 *
 * This special type of interrupt can be thrown to redirect a request mid-stream
 * to another request. The context passed in will be used to pre-seed the context
 * of the next request.
 */
class FortissimoForwardRequest extends FortissimoInterrupt {
  protected $destination;
  protected $cxt;
  
  /**
   * Construct a new forward request.
   *
   * The information in this forward request will be used to attempt to terminate
   * the current request, and continue processing by forwarding on to the 
   * named request.
   *
   * @param string $requestName
   *  The name of the request that this should forward to.
   * @param FortissimoExecutionContext $cxt
   *  The context. IF THIS IS PASSED IN, the next request will continue using this
   *  context. IF THIS IS NOT PASSED OR IS NULL, the next request will begin afresh
   *  with an empty context.
   */
  public function __construct($requestName, FortissimoExecutionContext $cxt = NULL) {
    $this->destination = $requestName;
    $this->cxt = $cxt;
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
   * @return FortissimoExecutionContext
   *  The context as it was at the point when the request was interrupted.
   */
  public function context() {
    return $this->cxt;
  }
}

/**
 * This class is used for building configurations.
 *
 * 
 * Typical usage looks something like this:
 *
 * @code
 * <?php
 * Config::request('foo')
 *  ->doesCommand('command1')
 *  ->whichInvokes('MyCommandClass')
 *    ->withParam('arg1')
 *    ->whoseValueIs('Some default value')
 *  ->doesCommand('command2')
 *  ->whichInvokes('SomeOtherCommandClass')
 *    ->withParam('anArgument')
 *    ->from('get:q') // <-- Use $_GET['q']
 * ?>
 * @endcode
 *
 * This class is used to add requests, loggers, datasources, and cache handlers to 
 * a Fortissimo application. Typically, it is used in commands.php.
 *
 * - Config::request(): Add a new request with a chain of commands.
 * - Config::includePath(): Add a new path that will be used by the autoloader.
 * - Config::group(): Add a new group that can be referenced from within a request.
 * - Config::datasource(): Add a new datasource, such as a database or document store.
 * - Config::logger(): Add a new logging facility.
 * - Config::cache(): Add a new cache.
 *
 * In Fortissimo, the data that Config creates may be used only at the beginning of a 
 * request. Be careful of race conditions or other anomalies that might occur if you 
 * attempt to use Config after Fortissimo has been bootstrapped.
 */
class Config {
  
  private static $instance = NULL;
  
  private $config = NULL;
  private $currentCategory = NULL;
  private $currentRequest = NULL;
  private $currentName = NULL;
  
  const REQUESTS = 'requests';
  const GROUPS = 'groups';
  const PATHS = 'paths';
  const DATASOURCES = 'datasources';
  const CACHES = 'caches';
  const LOGGERS = 'loggers';
  const REQUEST_MAPPER = 'requestMapper';
  const LISTENERS = 'listeners';
  
  public static function request($name, $description = '') {
    return self::set(self::REQUESTS, $name);
  }
  /**
   * Add an include path.
   *
   * This will be added to the class loader's registry.
   *
   * @param string $path
   *  The string path to add.
   * @return Config
   *  This object.
   */
  public static function includePath($path) {
    $i = self::inst();
    $i->config[self::PATHS][] = $path;
    $i->currentCategory = self::PATHS;
    $i->currentName = NULL;
    return $i;
  }
  
  /**
   * Request mappers determine how input is mapped to internal request names.
   *
   * Fortissimo provides a default request mapper that assumes that the incoming identifier
   * string is actually a request name. Thus http://example.com/?ff=foo is treated as if 
   * it was trying to execute the request named 'foo'.
   *
   * For some common website features (like Search Engine Friendly URLs, aka SEFs), a more
   * robust mapper would be desirable. This allows developers to write a custom mapper and 
   * use that instead.
   *
   * Example:
   *
   * @code
   * <?php
   * Config::useRequestMapper('MyMapperClass');
   * ?>
   * @endcode
   *
   * For implementation details, see FortissimoRequestMapper and Fortissimo::handleRequest().
   */
  public static function useRequestMapper($class) {
    $i = self::inst();
    $i->config[self::REQUEST_MAPPER] = $class;
    $i->currentCategory = self::REQUEST_MAPPER;
    $i->currentName = NULL;
    return $i;
  }
  /**
   * Declare a new group of commands.
   *
   * Entire groups of commands can be added to a request.
   *
   * @code
   * <?php
   * Config::group('myGroup')
   *  ->doesCommand('a')->whichInvokes('MyA')
   * ;
   *
   * Config::request('myRequest')
   *  ->doesCommand('b')->whichInvokes('MyB')
   *  ->usesGroup('myGroup')
   * ;
   * ?>
   * @endcode
   *
   * The above is equivalent to declaring a request with two commands ('a' and 'b').
   * You can re-use a group in multiple request, but you cannot use the same group
   * multiple times in the same request.
   *
   * @param string $name
   *  The name of the group.
   */
  public static function group($name) {
    return self::set(self::GROUPS, $name);
  }
  
  /**
   * Declare an event listener that will bind to ALL requests.
   *
   * @code
   * <?php
   * Config::listener('FooClass', 'load', function ($e) {});
   *
   * // ...
   *
   * // The above will automatically bind to this.
   * Config::request('foo')->hasCommand('bar')->whichInvokes('FooClass');
   * ?>
   * @endcode
   *
   * @param string $klass
   *  The name of the class to bind to.
   * @param string $event
   *  The name of the event to listen for.
   * @param callable $callable
   *  The callback to execute when $klass fires $event.
   * @return
   *  The config instance.
   */
  public static function listener($klass, $event, $callable) {
    $i = self::inst();
    //$i->config[self::LISTENERS] = arra();
    $i->currentCategory = self::LISTENERS;
    $i->currentName = NULL;
    
    // Now register the callable.
    $i->config[self::LISTENERS][$klass][$event][] = $callable;
    
    return $i;
  }
  
  /**
   * Declare a new datasource.
   *
   * @param string $name
   *  The name of the datasource to add. The name can be referenced by other parts
   *  of the application.
   * @return Config
   *  This object.
   */
  public static function datasource($name) {
    return self::set(self::DATASOURCES, $name);
  }
  /**
   * Declare a new logger.
   *
   * Fortissimo can use numerous loggers. You can declare
   * one or more loggers in your configuration.
   *
   * @param string $name
   *  The name of the logger. This is for other parts of the application
   *  to reference.
   * @return Config
   *  The object.
   */
  public static function logger($name) {
    return self::set(self::LOGGERS, $name);
  }
  /**
   * Declare a new cache.
   *
   * @param string $name
   *  The name of the cache. Caches can be referenced by name in other parts of
   *  the application.
   * @return Config
   *  The object.
   */
  public static function cache($name) {
    return self::set(self::CACHES, $name);
  }
  
  private static function set($cat, $name) {
    $i = self::inst();
    $i->currentCategory = $cat;
    $i->currentName = $name;
    $i->config[$cat][$name] = array();
    return $i;
  }

  /**
   * Create a new configuration from the given array.
   *
   * EXPERTS: This provides a mechanism for passing in an array instead
   * of executing a fluent chain. It overwrites the current configuration,
   * and so should be used with extreme caution.
   *
   * @param array $config
   *  A complete configuration array.
   * @return Config
   *  This object.
   */
  public static function initialize(array $config = NULL) {
    self::$instance = new Config();
    if (!is_null($config)) self::$instance->config = $config;
    return self::$instance;
  }
  
  /**
   * Get the complete configuration array.
   *
   * This returns a datastructure that represents the configuration for the system.
   *
   * @return array
   *  The configuration.
   */
  public static function getConfiguration() {
    return self::inst()->config;
  }
  
  /**
   * Get an instance of the configuration Config object.
   *
   * This controls access to the singleton.
   *
   * @return Config
   *  An instance of the Config object.
   */
  private static function inst() {
    if (empty(self::$instance)) {
      self::$instance = new Config();
    }
    return self::$instance;
  }
  
  /**
   * Config is a singleton.
   */
  private function __construct() {
    $this->config = array(
      self::REQUESTS => array(),
      self::LOGGERS => array(),
      self::CACHES => array(),
      self::PATHS => array(),
      self::GROUPS => array(),
      self::DATASOURCES => array(),
      self::LISTENERS => array(),
      self::REQUEST_MAPPER => NULL,
    );
  }
  public function usesGroup($name) {
    if ($this->currentCategory = self::REQUESTS) {
      // In PHP, ths will copy the array. But any objects in the array
      // will not be cloned. Don't know if that is a problem.
      $group = $this->config[self::GROUPS][$name];
      $cat = $this->currentCategory;
      $name = $this->currentName;
      $this->config[$cat][$name] += $group;
      /*
      foreach ($group as $command => $desc) {
        $this->config[$cat][$name][$command] = $desc;
      }
      */
    }
    return $this;
  }
  public function doesCommand($name) {
    $this->commandName = $name;
    $this->config[$this->currentCategory][$this->currentName][$this->commandName] = array();
    return $this;
  }
  public function whichInvokes($className) {
    switch ($this->currentCategory) {
      case self::LOGGERS:
      case self::CACHES:
      case self::DATASOURCES:
        $this->config[$this->currentCategory][$this->currentName]['class'] = $className;
        break;
      case self::REQUESTS:
      case self::GROUPS:
        $this->config[$this->currentCategory][$this->currentName][$this->commandName]['class'] = $className;
        
        // We need to bind global listeners to each request that invokes the class.
        if (!empty($this->config[self::LISTENERS][$className])) {
          foreach($this->config[self::LISTENERS][$className] as $event => $callable) {
            $this->config[$this->currentCategory][$this->currentName][$this->commandName]['listeners'][$event] = $callable;
          }
        }
        break;
      default:
        $msg = 'Tried to add a class to ' . $this->currentCategory;
        throw new FortissimoConfigurationException($msg);
    }
    return $this;
  }
  /**
   * Set a parameter for a class.
   */
  public function withParam($param) {
    $this->currentParam = $param;
    $cat = $this->currentCategory;
    $name = $this->currentName;
    switch ($this->currentCategory) {
      case self::LOGGERS:
      case self::CACHES:
      case self::DATASOURCES:
        $this->config[$cat][$name]['params'][$param] = NULL;
        break;
      case self::REQUESTS:
      case self::GROUPS:
        $this->config[$cat][$name][$this->commandName]['params'][$param] = NULL;
        break;
      default:
        $msg = 'Tried to add a param to ' . $this->currentCategory;
        throw new FortissimoConfigurationException($msg);
    }
    return $this;
  }
  public function bind($eventName, $callable) {
    $cat = $this->currentCategory;
    $name = $this->currentName;
    switch ($cat) {
      case self::GROUPS:
      case self::REQUESTS:
        $this->config[$cat][$name][$this->commandName]['listeners'][$eventName][] = $callable;
        break;
      default:
        $msg = 'Tried to add an event listener to ' . $this->currentCategory;
        throw new FortissimoConfigurationException($msg);
    }
    return $this;
  }
  /**
   * Sets a default value for a param.
   */
  public function whoseValueIs($value) {
    $param = $this->currentParam;
    $cat = $this->currentCategory;
    $name = $this->currentName;
    switch ($this->currentCategory) {
      case self::LOGGERS:
      case self::CACHES:
      case self::DATASOURCES:
        $this->config[$cat][$name]['params'][$param]['value'] = $value;
        break;
      case self::REQUESTS:
      case self::GROUPS:
        $this->config[$cat][$name][$this->commandName]['params'][$param]['value'] = $value;
        break;
      default:
        $msg = 'Tried to add a param value to ' . $this->currentCategory;
        throw new FortissimoConfigurationException($msg);
    }
    return $this;
  }
  /**
   * Indicates where Fortissimo should retrieve this param's value from.
   *
   * For examples, see Fortissimo::fetchParameterFromSource().
   *
   * @param string $source
   *  A string indicating where Fortissimo should look for parameter values.
   */
  public function from($source) {
    $param = $this->currentParam;
    $cat = $this->currentCategory;
    $name = $this->currentName;
    switch ($this->currentCategory) {
      case self::LOGGERS:
      case self::CACHES:
      case self::DATASOURCES:
        $this->config[$cat][$name]['params'][$param]['from'] = $source;
        break;
      case self::REQUESTS:
      case self::GROUPS:
        $this->config[$cat][$name][$this->commandName]['params'][$param]['from'] = $source;
        break;
      default:
        $msg = 'Tried to add a param value to ' . $this->currentCategory;
        throw new FortissimoConfigurationException($msg);
    }
    return $this;
  }
  /*
  public function andCachesInto($name) {
    switch ($this->currentCategory) {
      case self::REQUESTS:
      case self::GROUPS:
        $this->config[$cat][$name][$this->commandName]['cache'] = $name;
        break;
      default:
        $msg = 'Tried to add a cache handler to ' . $this->currentCategory;
        throw new FortissimoConfigurationException($msg);
    }
  }
  */
  /**
   * Turn on or off explaining for a request.
   */
  public function isExplaining($boolean = FALSE) {
    if ($this->currentCategory == self::REQUESTS) {
      $cat = $this->currentCategory;
      $name = $this->currentName;
      $this->config[$cat][$name]['#explaining'] = $boolean;
    }
    return $this;
  }
  /**
   * Turn on or off caching for a request.
   */
  public function isCaching($boolean = TRUE) {
    if ($this->currentCategory == self::REQUESTS) {
      $cat = $this->currentCategory;
      $name = $this->currentName;
      $this->config[$cat][$name]['#caching'] = $boolean;
    }
    return $this;
    
  }
}
/**
 * The request mapper receives some part of a string or URI and maps it to a Fortissimo request.
 *
 * Mapping happens immediately before request handling (see Fortissimo::handleRequest()). 
 * Typically, datasources and loggers are available by this point.
 *
 * Custom request mappers can be created by extending this one and then configuring commands.php
 * accordingly. 
 *
 * @code
 * <?php 
 * Config::useRequestMapper('ClassName');
 * ?>
 * @endcode
 *
 * For a user-oriented description, see Config::useRequestMapper().
 */
class FortissimoRequestMapper {
  
  protected $loggerManager;
  protected $cacheManager;
  protected $datasourceManager;
  
  /**
   * Construct a new request mapper.
   *
   * 
   *
   * @param FortissimoLoggerManager $loggerManager
   *  The logger manager.
   * @param FortissimoCacheManager $cacheManager
   *  The cache manager.
   * @param FortissimoDatasourceManager $datasourceManager
   *  The datasource manager
   */
  public function __construct($loggerManager, $cacheManager, $datasourceManager) {
    $this->loggerManager = $loggerManager;
    $this->cacheManager = $cacheManager;
    $this->datasourceManager = $datasourceManager;
  }
  

  /**
   * Map a given string to a request name.
   *
   * @param string $uri
   *  For web apps, this is a URI passed from index.php. A commandline app may pass the request
   *  name directly.
   * @return string
   *  The name of the request to execute.
   */
  public function uriToRequest($uri) {
    return $uri;
  }
  
  /**
   * Map a request into a URI (usually a URL).
   *
   * This takes a request name and transforms it into an absolute URL.
   */
  public function requestToUri($request = 'default', $params = array(), $fragment = NULL) {
    // base
    $baseURL = $this->baseURL();
    $fragment = empty($fragment) ? '' : '#' . $fragment;
    
    $buffer = $baseURL . $fragment;
    
    
    // FIXME: Need to respect Apache rewrite rules.
    if ($request != 'default') $params['ff'] = $request;
    
    if (!empty($params)) {
      // XXX: Do & need to be recoded as &amp; here?
      $qstr = http_build_query($params);
      $buffer .= '?' . $qstr;      
    }
    
    return $buffer;
  }
  
  /**
   * The canonical host name to be used in Fortissimo.
   *
   * By default, this is fetched from the $_SERVER variables as follows:
   * - If a Host: header is passed, this attempts to use that ($_SERVER['HTTP_HOST'])
   * - If no host header is passed, server name ($_SERVER['SERVER_NAME']) is used.
   *
   * This can be used in URLs and other references.
   *
   * @return string 
   *  The hostname.
   */
  public function hostname() {
    return !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
  }
  
  /**
   * Get the base URL for this instance.
   *
   * @return string
   *  A string of the form 'http[s]://hostname[:port]/[base_uri]'
   */
  public function baseURL() {
    $uri = empty($_SERVER['REQUEST_URI']) ? '/' : $_SERVER['REQUEST_URI'];
    $host = $this->hostname();
    $scheme = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
    
    $default_port = empty($_SERVER['HTTPS']) ? 80 : 443;
    
    if ($_SERVER['SERVER_PORT'] != $default_port) {
      $host .= ':' . $_SERVER['SERVER_PORT'];
    }
    
    return $scheme . $host . $uri;
  }
}

// End defgroup.
/** @} */
/** @defgroup Fortissimo Fortissimo
 * Features that are included in Fortissimo by default.
 */
/** @page Fortissimo
  * Fortissimo is a PHP application-building framework designed for performance, ease of
  * development, and flexibility.
  *
  * Instead of using the MVC pattern, Fortissimo uses a pattern much more suited to web
  * development: Chain of Command.
  *
  * In a "chain of command" (CoC) pattern, we map a <em>request</em> to a series of 
  * <em>commands</em>. Each command is executed in sequence, and each command can build off of the 
  * results of the previous commands.
  *
  * If you are new to Fortissimo, you should get to know the following:
  *
  * - commands.php: The configuration file.
  * - BaseFortissimoCommand: The base command that most of your classes will extend.
  * 
  * Take a look at the built-in Fortissimo commands in src/core/Fortissimo. In particular, 
  * the FortissimoPHPInfo command is a good starting point, as it shows how to build a command
  * with parameters, and it simply outputs phpinfo().
  *
  * Learn more:
  * - Read QUICKSTART.mdown to get started right away
  * - Read the README.mdown in the documentation
  * - Take a look at Fortissimo's unit tests
  *
  * @section getting_started Getting Started
  * 
  * To start a new project, see the documentation in the README file. It explains how to run
  * the command-line project generator, which will stub out your entire application for you.
  *
  * Once you have a base application, you should edit commands.php. While you can configure 
  * several things there (loggers, caches, include paths, etc.), the main purpose of this file
  * is to provide a location to map a request to a chain of commands.
  *
  * For the most part, developing a Fortissimo application should consist of only a few main tasks:
  * define your requests in commands.php, and create commands by writing new classes that 
  * extend BaseFortissimoCommand.
  *
  * Your commands should go in src/includes/. As long as the classname and file name are the same, 
  * Fortissimo's autoloader will automatically find your commands and load them when necessary.
  *
  * @section default_facilities_explained Default Facilities
  *
  * Fortissimo provides several facilities that you can make use of:
  *
  * - Datasources: Fortissimo provides a facility for declaring and working with various data
  *  storage systems such as relational SQL databases and NoSQL databases like MongoDB or even
  *  Memcached. Fortissimo comes with support for Mongo DB (FortissimoMongoDatasource) and
  *  PDO-based SQL drivers (FortissimoPDODatasource). Writing custom datasources is usally trivial.
  * - Loggers: Fortissimo has a pluggable logging system with built-in loggers for printing 
  *  straight to output (FortissimoOutputInjectionLogger), an array for later retrieval
  *  (FortissimoArrayInjectionLogger), or to a system logger (FortissimoSyslogLogger).
  * - Caches: Fortissimo supports a very robust notion of caches: Requests can be cached, and 
  *  any command can declare itself cacheable. Thus, individual commands can cache data. In 
  *  addition, the caching layer is exposed to commands, which can cache arbitrary data. Extending
  *  the caching system is trivial. A PECL/Memcache implementation is provided in 
  *  FortissimoMemcacheCache.
  * - Request Mapper: With the popularity of search-engine-friendly (SEF) URLs, Fortissimo provides
  *  a generic method by which application developers can write their own URL mappers. The 
  *  default FortissimoRequestMapper provides basic support for mapping a URL to a request. You
  *  can extend this to perform more advanced URL handling, including looking up path aliases
  *  in a datasource.
  * - Include Paths: By default, Fortissimo searches the includes/ directory for your source code.
  *  Sometimes you will want it to search elsewhere. Use include paths to add new locations for
  *  Fortissimo to search. This is done with Config::includePath().
  *
  * @section fortissimo_license License
  Fortissimo
  Matt Butcher <mbutcher@aleph-null.tv>
  Copyright (C) 2009, 2010 Matt Butcher

  Permission is hereby granted, free of charge, to any person obtaining a copy
  of this software and associated documentation files (the "Software"), to deal
  in the Software without restriction, including without limitation the rights
  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
  copies of the Software, and to permit persons to whom the Software is
  furnished to do so, subject to the following conditions:

  The above copyright notice and this permission notice shall be included in
  all copies or substantial portions of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
  THE SOFTWARE.
  */