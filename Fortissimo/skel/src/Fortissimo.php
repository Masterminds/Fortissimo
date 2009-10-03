<?php
/**
 * The Fortissimo core.
 *
 * This file contains the core classes necessary to bootstrap and run an
 * application that makes use of the Fortissimo framework.
 */
 
require_once('include/QueryPath/QueryPath.php');
 
/**
 * The Fortissimo front controller.
 *
 * This class is used to bootstrap Fortissimo and oversee execution of a
 * Fortissimo request. Unlike Rhizome, there is no split between the 
 * front controller and the request handler.
 *
 * Typically, the entry point for this class is {@link handleRequest()}, which 
 * takes a request name and executes all associated commands.
 */
class Fortissimo {
  
  protected $commandConfig = NULL;
  
  public function __construct($commandsXMLFile) {
    // Parse configuration file.
    $this->commandConfig = new FortissimoConfig($commandsXMLFile);
  }
  
  /**
   * Handles a request.
   *
   * When a request comes in, this method is responsible for displatching
   * the request to the necessary commands, executing commands in sequence.
   */
  public function handleRequest($requestName) {
    $request = $this->commandConfig->getRequest($reqestName);
    $cr = new FortissimoExecutionContext();
    foreach ($request as $command) {
      try {
        $this->execCommand($command, $cr);
      }
      // Kill the request and log an error.
      catch (FortissimoInterruptException $ie) {
        $this->routeErrorMessage($e);
        return;
      }
      // Kill the request, no error.
      catch (FortissimoInterrupt $i) {
        return;
      }
      // Log the error, but continue to the next command.
      catch (FortissimoException $e) {
        // FIXME: Something needs to happen to errors.
        $this->routeErrorMessage($e);
        continue;
      }
    }
  }
  
  public function routeErrorMessage($e) {
    if ($e instanceof Exception) {
      print $e->getMessage();
    }
    else {
      print $e;
    }
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
  protected function execCommand($commandArray, FortissimoExecutionContext $cxt) {
    // We should already have a command object in the array.
    $inst = $commandArray['instance'];
    
    $params = $this->fetchParameters($commandArray, $cxt);
    
    try {
      $inst->execute($params, $cxt);
    }
    // Only catch a FortissimoException. Allow FortissimoInterupt to go on.
    catch (FortissimoException $e) {
      // Do something here...
      $cxt->addException($e);
    }
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
  protected function fetchParameters($commandArray, FortissimoExecutionContext $cxt) {
    $params = array();
    foreach ($commandArray['params'] as $name => $config) {
      
      // If there is a FROM source, fetch the data from the designated source(s).
      if (isset($config['from'])) {
        
        // Handle cases like this: 'from="get:preferMe post:onlyIfNotInGet"'
        $fromItems = explode(' ', $config['from']);
        $value = NULL;
        
        // Stop as soon as a paramter is fetched and is not NULL.
        do {
          $value = $this->fetchParameterFromSource($fromItems, $cxt);
        }
        while ($value != NULL);
        $params[$name] = $value;
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
   * @param FortissimoExecutionContext $cxt
   *  The current working context. This is used to retrieve data from cmd: 
   *  sources.
   * @return string 
   *  The value or NULL.
   */
  protected function fetchParameterFromSource($from, FortissimoExecutionContext $cxt) {
    list($proto, $paramName) = explode(':', $from, 2);
    $proto = strtolower($source);
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
        return $_COOKIES[$paramName];
      case 's':
      case 'session':
        return $_SESSION[$paramName];
      case 'x':
      case 'cmd':
      case 'context':
        return $cxt[$paramName];
      case 'e':
      case 'env':
      case 'environment':
        return $ENV[$paramName];
    }
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
   */
  public function __construct($name);
  
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
}

/**
 * Stores information about Fortissimo commands.
 *
 * This is used when bootstrapping to map a request to a series of commands.
 * Note that this does not provide an object represenation of the configuration
 * file. Instead, it interprets the configuration file, and assembles the 
 * information as the application needs it. To get directly at the configuration
 * information, use {@link getConfig()}.
 */
class FortissimoConfig {
  
  protected $config;
  
  public function __construct($commandsXMLFile) {
    $this->config = qp($commandsXMLFile);
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
   * Given a request name, retrieves a request queue.
   *
   * The queue (in the form of an array) contains information about what 
   * commands should be run, and in what order.
   *
   * @param string $requestName
   *  The name of the request
   * @return array 
   *  A queue of commands that need to be executed. See {@link createCommandInstance()}.
   * @throws FortissimoRequestNotFoundException
   *  If no such request is found, or if the request is malformed, and exception is 
   *  thrown. This exception should be considered fatal, and a 404 error should be 
   *  returned.
   */
  public function getRequest($requestName){
    
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
    
    
    return $commands;
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
    
    $inst = new $class($name);
    return array(
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
  
  /**
   * Create a new context.
   *
   * @param array $initialContext
   *  An associative array of context pairs.
   */
  public function __construct($initialContext = array()) {
    if ($initialContext instanceof FortissimoExecutionContext) {
      $this->data = $initialContext->toArray();
    }
    else {
      $this->data = $initialContext;
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
    return $this->data[$name];
  }
  
  /**
   * Remove an item from the context.
   *
   * @param string $name
   *  The thing to remove.
   */
  public function remove($name) {
    unset($this->data[$name]);
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

  // Inherit docs.
  public function getIterator() {
    // Does this work?
    return new ArrayIterator($this->data);
  }
}

class FortissimoInterrupt extends Exception {}
class FortissimoInterruptException extends Exception {}
class FortissimoException extends Exception {}
class FortissimoConfigurationException extends FortissimoException {}
class FortissimoRequestNotFoundException extends FortissimoException {}