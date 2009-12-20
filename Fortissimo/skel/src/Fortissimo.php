<?php
/**
 * The Fortissimo core.
 *
 * This file contains the core classes necessary to bootstrap and run an
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
 * {@link FortissimoCommand} class. Most commands will extend the abstract
 * {@link BaseFortissimoCommand} class, which provides a baseline of 
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
 * @package Fortissimo
 * @subpackage Core
 * @author M Butcher <matt@aleph-null.tv>
 * @license http://opensource.org/licenses/mit.php An MIT-style License (See LICENSE.txt)
 * @see Fortissimo
 * @copyright Copyright (c) 2009, Matt Butcher.
 * @version @UNSTABLE@
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
spl_autoload_register();

/**
 * QueryPath is a core Fortissimo utility.
 * @see http://querypath.org
 */ 
require_once('QueryPath/QueryPath.php');
// ^^ This is explicitly loaded because of the factory function.
 
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
 * @package Fortissimo
 * @subpackage Core
 */
class Fortissimo {
  
  /**
   * Error codes that should be converted to exceptions and thrown.
   */
  const ERROR_TO_EXCEPTION = 771; // 257 will catch only errors; 771 is errors and warnings.
  
  protected $commandConfig = NULL;
  protected $initialConfig = NULL;
  protected $logManager = NULL;
  protected $cxt = NULL;
  protected $cacheManager = NULL;
  
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
   * @param mixed $commandsXMLFile
   *  A configuration pointer. Typically, this is a filename of the commands.xml
   *  file on the filesystem. However, straight XML, a DOMNode or DOMDocument, 
   *  and a SimpleXML object are among the various objects that can be passed
   *  in as $commandsXMLFile.
   * @param array $configData
   *  Any additional configuration data can be added here. This information 
   *  will be placed into the {@link FortissimoExecutionContext} that is passsed
   *  into each command. In this way, information passed here should be available
   *  to every command, as well as to the overarching framework.
   */
  public function __construct($commandsXMLFile, $configData = array()) {
    
    $this->initialConfig = $configData;
    
    // Parse configuration file.
    $this->commandConfig = new FortissimoConfig($commandsXMLFile);
    
    // Add additional files to the include path:
    $paths = $this->commandConfig->getIncludePaths();
    $this->addIncludePaths($paths);
    
    // Create the log manager.
    $this->logManager = new FortissimoLoggerManager($this->commandConfig->getLoggers());
    
    // Create cache manager.
    $this->cacheManager = new FortissimoCacheManager($this->commandConfig->getCaches());
  }
  
  /**
   * Add paths that will be used by the autoloader and include/require.
   *
   * Fortissimo uses the {@link spl_autoload()} family of functions to
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
   * When a request comes in, this method is responsible for displatching
   * the request to the necessary commands, executing commands in sequence.
   *
   * <strong>Note:</strong> Fortissimo has experimental support for request
   * caching. When request caching is enabled, the output of a request is 
   * stored in a cache. Subsequent identical requests will be served out of
   * the cache, thereby avoiding all overhead associated with loading and 
   * executing commands.
   */
  public function handleRequest($requestName = 'default', FortissimoExecutionContext $initialCxt = NULL) {
    
    $request = $this->commandConfig->getRequest($requestName);
    $cacheKey = NULL; // This is set only if necessary.
    
    // If this request is in explain mode, explain and exit.
    if ($request->isExplaining()) {
      print $this->explainRequest($request);
      return;
    }
    /*
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
      
      // Turn on output buffering. We use this to capture data
      // for insertion into the cache.
      ob_start();
    }
    */
    
    // This allows pre-seeding of the context.
    if (isset($initialCxt)) {
      $this->cxt = $initialCxt;
    }
    // This sets up the default context.
    else {
      $this->cxt = new FortissimoExecutionContext($this->initialConfig, $this->logManager);
    }
    
    foreach ($request as $command) {
      try {
        $this->execCommand($command);
      }
      // Kill the request and log an error.
      catch (FortissimoInterruptException $ie) {
        $this->logManager->log($e, 'Fatal Error');
        return;
      }
      // Forward any requests.
      catch (FortissimoForwardRequest $forward) {
        $this->handleRequest($forward->destination(), $forward->context());
        return;
      }
      // Kill the request, no error.
      catch (FortissimoInterrupt $i) {
        return;
      }
      // Log the error, but continue to the next command.
      catch (FortissimoException $e) {
        $this->logManager->log($e, 'Recoverable Error');
        continue;
      }
      catch (Exception $e) {
        // Assume that a non-caught exception is fatal.
        $this->logManager->log($e, 'Fatal Error');
        return;
      }
    }
    
    /*
    // If caching is on, place this entry into the cache.
    if ($request->isCaching() && isset($this->cacheManager)) {
      $contents = ob_get_contents();
      
      // Add entry to cache.
      $this->cacheManager->set($cacheKey, $contents);
      // Turn off output buffering & send to client.
      ob_end_flush();
    }    
    */
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
   * Execute a single command.
   *
   * @param array $commandArray
   *  An associative array, as described in {@link FortissimoConfig::createCommandInstance}.
   * @param FortissimoExecutionContext $cxt
   *  The context of this request. This is passed from command to command.
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
   *  in {@link FortissimoConfig::createCommandInstance}.
   * @param FortissimoExecutionContext $cxt
   *  The execution context.
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
      if (!isset($params[$name])) $params[$name] = $config['value'];
    }
    return $params;
  }
  
  /**
   * Parse a parameter specification and retrieve the appropriate data.
   *
   * @param string $from
   *  A parameter specification of the form <source>:<name>. Examples:
   *  - get:myParam
   *  - post:username
   *  - cookie:session_id
   *  - session:last_page
   *  - cmd:lastCmd
   *  - env:cwd
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
   * @param FortissimoExecutionContext $cxt
   *  The current working context. This is used to retrieve data from cmd: 
   *  sources.
   * @return string 
   *  The value or NULL.
   */
  protected function fetchParameterFromSource($from) {
    list($proto, $paramName) = explode(':', $from, 2);
    $proto = strtolower($proto);
    switch ($proto) {
      case 'g':
      case 'get':
        return $_GET[$paramName];
      case 'p':
      case 'post':
        return $_POST[$paramName];
      case 'c':
      case 'cookie':
      case 'cookies':
        return $_COOKIE[$paramName];
      case 's':
      case 'session':
        return $_SESSION[$paramName];
      case 'x':
      case 'cmd':
      case 'cxt':
      case 'context':
        return $this->cxt->get($paramName);
      case 'e':
      case 'env':
      case 'environment':
        return $_ENV[$paramName];
      case 'server':
        return $_SERVER[$paramName];
      case 'r':
      case 'request':
        return $_REQUEST[$paramName];
      case 'a':
      case 'arg':
      case 'argv':
        return $argv[(int)$paramName];
    }
  }
}


/**
 * A Fortissimo request.
 *
 * This class represents a single request.
 * @package Fortissimo
 * @subpackage Core
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
 * @package Fortissimo
 * @subpackage Core
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
 * {@link BaseFortissimoCommand} to store parameter information for use
 * in {@link BaseFortissimoCommand::explain()} and 
 * {@link BaseFortissimoCommand::expects()}. A builder for these is found
 * in {@link BaseFortissimoCommand::description()}, which provides a semi-fluent
 * interface for defining expectations.
 * @package Fortissimo
 * @subpackage Core
 * @see BaseFortissimoCommand
 * @see BaseFortissimoCommandParameter
 */
class BaseFortissimoCommandParameterCollection implements IteratorAggregate {
  protected $params = array();
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
   * system will merely check to see if the data is acceptible.
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
 * @package Fortissimo
 * @subpackage Core
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
   * system will merely check to see if the data is acceptible.
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
   * @param array $validators
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
   *  An array of the form specified in {@link setValidators()}.
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
 * @package Fortissimo
 * @subpackage Core
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
 * This is a base class that can be extended to add new commands.
 *
 * The class provides several basic services. 
 *
 * First, it simplifies the
 * process of executing a command. The {@link BaseFortissimoCommand::doCommand()}
 * method follows a very simple pattern.
 *
 * Second, it provides structure for describing a command. The abstract 
 * {@link BaseFortissimoCommand::expects()} method provides the facilities for
 * describing what parameters this command should use, how these parameters should
 * be filtered/validated/sanitized, and what each parameter is for.
 *
 * Third, using the data from {@link BaseFortissimoCommand::expects()}, this 
 * class provides a self-documenting tool, {@link BaseFortissimoCommand::explain()},
 * which uses the information about the parameter to provide human-radible 
 * documentation about what this command does.
 *
 * When extending this class, there are two things that every extension must do:
 * 
 * 1. It must provide information about what parameters it uses. This is done
 *  by implementing {@link expects()}.
 * 2. It must provide logic for performing the command. This is done in 
 *  {@link doCommand()}.
 * @abstract
 * @package Fortissimo
 * @subpackage Core
 */
abstract class BaseFortissimoCommand implements FortissimoCommand, Explainable {
  
  protected $paramsCollection;
  
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
   *  The default value to return if no such paramter is found.
   *  This is NULL by default.
   * @see context()
   */
  protected function param($name, $default = NULL) {
    $val = $this->parameters[$name];
    return isset($val) ? $val : $default;
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
   *  context.
   * @see param()
   */
  protected function context($name, $default = NULL) {
    $val = $this->context->get($name);
    return isset($val) ? $val : $default;
  }
  
  public function execute($params, FortissimoExecutionContext $cxt) {
    $this->context = $cxt;
    $this->prepareParameters($params);
    $this->context->add($this->name, $this->doCommand());
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
    $msg = "Filter %s failed for %s";
    throw new FortissimoException(sprintf($msg, $filter, $name));
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
   *  {@link BaseFortissimoCommandParameter::addFilter()} for a list of names,
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
    $filterID = filter_id($filter);
    $res = filter_var($payload, $filterID, $options);
    
    
    // Boolean validation returns FALSE if the bool is false, or if a fail occurs.
    // So we just pass through. Nothing more that can really be done about it.
    if ($res === FALSE && $filter != FILTER_VALIDATE_BOOLEAN) {
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
   * <code>
   * <?php
   * public function expects() {
   *  return $this
   *   ->description('This command sends a name to an email address.')
   *   ->param('name', 'The name to echo back')
   *    ->withFilter('string')
   *   ->param('email', 'An email address to echo data to.')
   *    ->withFilter('email')
   *    ->withFilter('validate_email')
   *   ->andReturns('A copy of the sent message.');
   * }
   * ?>
   * </code>
   *
   * The above describes a command that expects two parameters: name and email.
   * The name command is validated with the string sanitizer, which makes sure
   * that the string doesn't have markup in it.
   * The email command is first santized against the email sanitizer, then it
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
   * Every class that extends this base class should implement {@link doCommand()},
   * executing the command's logic, and returning the value or values that should
   * be placed into the execution context.
   *
   * This object provides access to the following variables of interest:
   *  - {@link $name}: The name of the command.
   *  - {@link $parameters}: The name/value list of parameters. These are learned 
   *    and validated based on the contents of the {@link expects()} method.
   *  - {@link $context}: The {@link FortissimoExecutionContext} object for this request.
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
 * This is used when bootstrapping to map a request to a series of commands.
 * Note that this does not provide an object represenation of the configuration
 * file. Instead, it interprets the configuration file, and assembles the 
 * information as the application needs it. To get directly at the configuration
 * information, use {@link getConfig()}.
 *
 * @package Fortissimo
 * @subpackage Core
 * @see Fortissimo
 */
class FortissimoConfig {
  
  protected $config;
  
  /**
   * Construct a new configuration object.
   *
   * @param mixed $commandsXMLFile
   *  A pointer to configuration information. Typically, this is a filename. 
   *  However, it may be any object that {@link qp()} can process.
   *
   * @see http://api.querypath.org/docs
   */
  public function __construct($commandsXMLFile) {
    $this->config = qp($commandsXMLFile);
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
    $includes = $this->config->branch(':root>include');
    $array = array();
    foreach ($includes as $i) {
      $array[] = $i->attr('path');
    }
    return $array;
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
    return $this->config->top()->find('request[name="' . $requestName . '"]')->size() > 0;
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
    return $this->getFacility('logger');
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
    return $this->getFacility('cache');
  }
  
  /**
   * Internal helper function.
   *
   * @param string $type
   *  The type of item to retrieve. Essentially, this is treated like a selector.
   * @param array 
   *  An associative array of the form <code>array('name' => object)</code>, where
   *  the object is an instance of the respective 'invoke' class.
   */
  protected function getFacility($type = 'logger') {
    $facilities = array();
    $fqp = $this->config->branch()->top($type);
    foreach ($fqp as $facility) {
      $name = $facility->attr('name');
      $klass = $facility->attr('invoke');
      $params = $this->getParams($facility);
      
      $facility = new $klass($params);
      $facility->init();
      
      $facilities[$name] = $facility;
    }
    return $facilities;
  }
  
  /**
   * Get the parameters for a facility such as a logger or a cache.
   *
   * @param QueryPath $logger
   *  Configuration for the given facility.
   * @return array
   *  An associative array of param name/values. <param name="foo">bar</param>
   *  becomes array('foo' => 'bar').
   */
  protected function getParams(QueryPath $facility) {
    $res = array();
    $params = $facility->find('param');
    if ($params->size() > 0) {
      foreach ($params as $item) {
        $res[$item->attr('name')] = $item->text();
      }
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
    
    // We know that per request, we only need to find one request, so we 
    // defer request lookups until we know the specific request we are after.
    $request = $this->config->top()->find('commands>request[name="' . $requestName . '"]');
    if ($request->size() == 0) {
      // This should be treated as a 404.
      throw new FortissimoRequestNotFoundException(sprintf('Request %s not found', $requestName));
    }
    
    // Determine whether the request supports caching.
    $cache = $request->attr('cache');
    $explain = $request->attr('explain');
    // FIXME: This should support true, t, yes, y.
    //$isCaching = isset($cache) && strtolower($cache) == 'true';
    $isCaching = filter_var($cache, FILTER_VALIDATE_BOOLEAN);
    $isExplaining = filter_var($explain, FILTER_VALIDATE_BOOLEAN);
    
    // Once we have the request, find out what commands we need to execute.
    $commands = array();
    $chain = $request->branch()->children('cmd');
    if ($chain->size() > 0) {
      foreach ($chain as $cmd) {
        if ($cmd->hasAttr('group')) {
          $gr = $cmd->attr('group');
          if (!self::isLegalRequestName($gr)) {
            throw new FortissimoRequestNotFoundException('Illegal group name.');
          }
          // Handle group importing.
          $this->importGroup($gr, $commands);
        }
        else {
          $commands[] = $this->createCommandInstance($cmd);
        }
      }
    }
    
    $request = new FortissimoRequest($requestName, $commands);
    $request->setCaching($isCaching);
    $request->setExplain($isExplaining);
    
    return $request;
  }
  
  /**
   * Import a group into the current request context.
   *
   * @param string $groupName
   *  Name of the group to import.
   * @param array &$commands
   *  Reference to an array of commands. The group commands will be appended to 
   *  this array.
   */
  protected function importGroup($groupName, &$commands) {
    //$group = $this->config->branch()->top()->find('group[name=' . $groupName . ']');
    $groups = $this->config->branch()->top()->find('commands>group');
    $group = NULL;
    foreach ($groups as $g) {
      if ($g->attr('name') == $groupName) {
        $group = $g;
        break;
      }
    }

    if (!isset($group)) {
      throw new FortissimoException(sprintf('No group found with name %s.', $groupName));
    }
    
    foreach ($group->children('cmd') as $cmd) {
      $commands[] = $this->createCommandInstance($cmd);
      
    }
  }
  
  /**
   * Create a command instance.
   *
   * Retrieve command information from the configuration file and transform these
   * into an internal data structure.
   *
   * @param QueryPath $cmd
   *  QueryPath object wrapping the command.
   * @return array
   *  An array with the following keys:
   *  - name: Name of the command
   *  - class: Name of the class
   *  - instance: An instance of the class
   *  - params: Parameter information. Note that the application must take this 
   *    information and correctly populate the parameters at execution time.
   *    Parameter information is returned as an associative array of arrays:
   *    <?php $param['name'] => array('from' => 'src:name', 'value' => 'default value'); ?>
   * @throws FortissimoException
   *  In the event that a paramter does not have a name, an exception is thrown.
   */
  protected function createCommandInstance(QueryPath $cmd) {
    $class = $cmd->attr('invoke');
    $cache = strtolower($cmd->attr('cache'));
    $caching =  (isset($cache) && $cache == 'true');
    
    if (empty($class))
      throw new FortissimoConfigException('Command is missing its "invoke" attribute.');
    
    $name = $cmd->hasAttr('name') ? $cmd->attr('name') : $class;
    
    $params = array();
    foreach ($cmd->branch()->children('param') as $param) {
      $pname = $param->attr('name');
      if (empty($pname)) {
        throw new FortissimoException('Parameter is missing name attribute.');
      }
      $params[$pname] = array(
        'from' => $param->attr('from'), // May be NULL.
        'value' => $param->text(), // May be empty.
      );
    }
    
    $inst = new $class($name, $caching);
    return array(
      'isCaching' => $caching,
      'name' => $name,
      'class' => $class,
      'instance' => $inst,
      'params' => $params,
    );
  }
  
  /**
   * Get the configuration information as a QueryPath object.
   *
   * @return QueryPath
   *  The configuration information wrapped in a QueryPath object.
   */
  public function getConfig() {
    return $this->config->top();
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
 * @package Fortissimo
 * @subpackage Core
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
   */
  public function __construct($initialContext = array(), FortissimoLoggerManager $logger = NULL ) {
    if ($initialContext instanceof FortissimoExecutionContext) {
      $this->data = $initialContext->toArray();
    }
    else {
      $this->data = $initialContext;
    }
    
    if (isset($logger)) {
      $this->logger = $logger;
    }
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
   * @return mixed
   *  The value in the array, or NULL if $name was not found.
   */
  public function get($name) {
    // isset() is used to avoid E_STRICT warnings.
    return isset($this->data[$name]) ? $this->data[$name]: NULL;
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
}

/**
 * Manage caches.
 *
 * This manages top-level {@link FortissimoRequestCache}s. Just as with 
 * {@link FortissimoLoggerManager}, a FortissimoCacheManager can manage
 * multiple caches. It will procede from cache to cache in order until it
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
 * @package Fortissimo
 * @subpackage Core
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
   * If no cahce is found, this will silently continue. If a name is given, but the
   * named cache is not found, the next available cache will be used.
   *
   * @param string $key
   *  The cache key
   * @param string $value
   *  The value to store
   * @param string $cache
   *  The name of the cache to store the value in. If not given, the cache 
   *  manager will store the item wherever it is most convenient.
   */
  public function set($key, $value, $cache = NULL) {
    
    // If a named cache key is found, set:
    if (isset($cache) && isset($this->caches[$cache])) {
      return $this->caches[$cache]->set($key, $value);
    }
    
    // XXX: Right now, we just use the first item in the cache:
    $keys = array_keys($this->caches);
    if (count($keys) > 0) {
      return $this->caches[$keys[0]]->set($key, $value);
    }
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
 * Manage loggers for a server.
 *
 * A {@link Fortissimo} instance may have zero or more loggers. Loggers
 * perform the standard task of handling messages that need recording for
 * review by administrators.
 *
 * The logger manager manages the various logging instances, delegating logging
 * tasks.
 *
 * @package Fortissimo
 * @subpackage Core
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
   */
  public function log($msg, $category) {
    foreach ($this->loggers as $name => $logger) {
      $logger->rawLog($msg, $category);
    }
  }
  
}

/**
 * The FOIL logger sends messages directly to STDOUT.
 *
 * Log messages will be emitted to STDOUT as soon as they are logged.
 *
 * @package Fortissimo
 * @subpackage Core
 */
class FortissimoOutputInjectionLogger extends FortissimoLogger {
  protected $filter;
  protected $isHTML = FALSE;
  
  public function init() {
    $this->isHTML = filter_var($this->params['html'], FILTER_VALIDATE_BOOLEAN);
    $this->filter = empty($this->params['html']) ? '%s %s %s' : '<div class="log-item %s"><strong>%s</strong> %s</div>';
  }
  public function log($message, $category) {
    
    if ($this->isHTML) {
      $severity = strtr($category, ' ', '-');
      $message = strtr($message, array("\n" => '<br/>'));
      $filter = '<div class="log-item %s"><strong>%s</strong> %s</div>';
      printf($filter, $severity, $category, $message);
    }
    else {
      printf('%s: %s', $category, $message);
    }
  }
}

/**
 * The FAIL logger maintains an array of messages to be retrieved later.
 * 
 * Log entries can be injected into the output by retrieving a list
 * of log messages with {@link getMessages()}, and then displaying them,
 * or by simply calling {@link printMessages()}.
 *
 * @package Fortissimo
 * @subpackage Core
 */
class FortissimoArrayInjectionLogger extends FortissimoLogger {
  protected $logItems = array();
  protected $filter;
  
  public function init() {
    $this->filter = empty($this->params['html']) ? '%s: %s' : '<div class="log-item %s">%s</div>';
  }
  
  public function getMessages() {
    return $this->logItems;
  }
  
  public function printMessages() {
    print implode('', $this->logItems);
  }
  
  public function log($message, $category) {
    $severity = str_replace(' ', '-', $category);
    $this->logItems[] = sprintf($this->filter, $severity, $message);
  }
}

/**
 * Provide a simple user-friendly (non-trace) error message.
 * @see FortissimoArrayInjectionLogger
 */
class SimpleArrayInjectionLogger extends FortissimoArrayInjectionLogger {
  public function log($message, $category) {
    $severity = str_replace(' ', '-', $category);
    $filter = '<div class="log-item %s"><strong>%s</strong> %s</div>';
    switch ($category) {
      case 'Fatal Error':
        $msg = 'An unrecoverable error occurred. Your request could not be completed.';
      case 'Recoverable Error':
        $msg = 'An error occurred. Some data may be lost or incomplete.';
      default:
        $msg = 'An unexpected error occurred. Some data may be lost or incomplete.';
    }
    $this->logItems[] = sprintf($filter, $severity, 'Error', $msg);
  }
}

/**
 * Provide a simple user-friendly (non-trace) error message.
 */
class SimpleOutputInjectionLogger extends FortissimoOutputInjectionLogger {
  
  public function log($message, $category) {
    $severity = strtr($category, ' ', '-');
    $filter = '<div class="log-item %s"><strong>%s</strong> %s</div>';
    switch ($category) {
      case 'Fatal Error':
        $msg = 'An unrecoverable error occurred. Your request could not be completed.';
      case 'Recoverable Error':
        $msg = 'An error occurred. Some data may be lost or incomplete.';
      default:
        $msg = 'An unexpected error occurred. Some data may be lost or incomplete.';
    }
    printf($filter, $severity, 'Error', $msg);
  }
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
 * @package Fortissimo
 * @subpackage Core
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
   */
  public function set($key, $value);
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
 * A logger responsible for logging messages to a particular destination.
 *
 * @package Fortissimo
 * @subpackage Core
 * @abstract
 */
abstract class FortissimoLogger {
  
  /**
   * The parameters for this logger.
   */
  protected $params = NULL;
  
  /**
   * Construct a new logger instance.
   *
   * @param array $params
   *   An associative array of name/value pairs.
   */
  public function __construct($params = array()) {
    $this->params = $params;
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
   */
  public function rawLog($message, $category = 'General Error') {
    if ($message instanceof Exception) {
      $buffer = get_class($message) . PHP_EOL;
      $buffer .= $message->getMessage() . PHP_EOL;
      $buffer .= $message->getTraceAsString();
    }
    elseif (is_object($message)) {
      $buffer = $mesage->toString();
    }
    else {
      $buffer = $message;
    }
    $this->log($buffer, $category);
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
   * @param string $category
   *  The log message category. Typical values are 
   *  - warning
   *  - error
   *  - info
   *  - debug
   */
  public abstract function log($msg, $severity);
  
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
 * @package Fortissimo
 * @subpackage Core
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
 * @package Fortissimo
 * @subpackage Core
 */
class FortissimoInterruptException extends Exception {}
/**
 * General Fortissimo exception.
 *
 * This should be thrown when Fortissimo encounters an exception that should be
 * logged and stored, but should not interrupt the execution of a command.
 * @package Fortissimo
 * @subpackage Core
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
 * @package Fortissimo
 * @subpackage Core
 */
class FortissimoConfigurationException extends FortissimoException {}
/**
 * Request was not found.
 * @package Fortissimo
 * @subpackage Core
 */
class FortissimoRequestNotFoundException extends FortissimoException {}

/**
 * Forward a request to another request.
 *
 * This special type of interrupt can be thrown to redirect a request mid-stream
 * to another request. The context passed in will be used to pre-seed the context
 * of the next request.
 * @package Fortissimo
 * @subpackage Core
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