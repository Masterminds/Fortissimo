<?php
/**
 * @file
 */
namespace Fortissimo;
/**
 * This class is used for building configurations.
 *
 *
 * Typical usage looks something like this:
 *
 * @code
 * <?php
 * $reg = new \Fortissimo\Registry();
 * $reg->route('foo')
 *     ->does('\Foo\Bar\Command', 'command1')
 *       ->uses('query')->from('get:q')
 *       ->uses('template', 'mytemplate.tpl.php')
 *     ->does('\Bar\Baz\OtherCommand', 'command2')
 *       ->uses('results')->from('cxt:command1')
 *
 *
 *
 * Registry::request('foo')
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
 * - Registry::request(): Add a new request with a chain of commands.
 * - Registry::includePath(): Add a new path that will be used by the autoloader.
 * - Registry::group(): Add a new group that can be referenced from within a request.
 * - Registry::datasource(): Add a new datasource, such as a database or document store.
 * - Registry::logger(): Add a new logging facility.
 * - Registry::cache(): Add a new cache.
 *
 * In Fortissimo, the data that App creates may be used only at the beginning of a
 * request. Be careful of race conditions or other anomalies that might occur if you
 * attempt to use App after Fortissimo has been bootstrapped.
 */
class Registry {

  private $config = NULL;
  private $currentCategory = NULL;
  private $currentRequest = NULL;
  private $currentName = NULL;

  protected $appName;

  const REQUESTS = 'requests';
  const GROUPS = 'groups';
  const PATHS = 'paths';
  const DATASOURCES = 'datasources';
  const CACHES = 'caches';
  const LOGGERS = 'loggers';
  const REQUEST_MAPPER = 'requestMapper';
  const LISTENERS = 'listeners';

  /**
   * Create a new registry.
   *
   * @param string $name
   *   The name of the application being built.
   */
  public function __construct($name = NULL) {
    $this->appName = $name;
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

  /**
   * An alias for Registry::request().
   */
  public function route($name, $description = '') {
    return $this->request($name, $description);
  }

  /**
   * Declare a new request.
   *
   * A request is a named chain of commands. It roughly maps
   * to a "route" in other frameworks. Unlike routes
   * in other frameworks, though, a request does not point
   * to a controller, but to a chain of commands which will
   * be run sequentially.
   *
   * @param string $name
   *   The name of the request.
   * @param string $description
   *   The human-readable description of the request.
   */
  public function request($name, $description = '') {
    return $this->set(self::REQUESTS, $name);
  }

  /**
   * Add an include path.
   *
   * This will be added to the class loader's registry.
   *
   * @param string $path
   *  The string path to add.
   * @return App
   *  This object.
   */
  public function includePath($path) {
    $this->config[self::PATHS][] = $path;
    $this->currentCategory = self::PATHS;
    $this->currentName = NULL;
    return $this;
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
   * Registry::useRequestMapper('MyMapperClass');
   * ?>
   * @endcode
   *
   * For implementation details, see FortissimoRequestMapper and Fortissimo::handleRequest().
   */
  public function useRequestMapper($class) {
    $this->config[self::REQUEST_MAPPER] = $class;
    $this->currentCategory = self::REQUEST_MAPPER;
    $this->currentName = NULL;
    return $this;
  }
  /**
   * Declare a new group of commands.
   *
   * Entire groups of commands can be added to a request.
   *
   * @code
   * <?php
   * Registry::group('myGroup')
   *  ->doesCommand('a')->whichInvokes('MyA')
   * ;
   *
   * Registry::request('myRequest')
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
  public function group($name) {
    return $this->set(self::GROUPS, $name);
  }

  /**
   * Declare an event listener that will bind to ALL requests.
   *
   * @code
   * <?php
   * Registry::listener('FooClass', 'load', function ($e) {});
   *
   * // ...
   *
   * // The above will automatically bind to this.
   * Registry::request('foo')->hasCommand('bar')->whichInvokes('FooClass');
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
  public function listener($klass, $event, $callable) {
    //$i->config[self::LISTENERS] = arra();
    $this->currentCategory = self::LISTENERS;
    $this->currentName = NULL;

    // Now register the callable.
    $this->config[self::LISTENERS][$klass][$event][] = $callable;

    return $this;
  }

  /**
   * Declare a new datasource.
   *
   * @param string $name
   *  The name of the datasource to add. The name can be referenced by other parts
   *  of the application.
   * @return App
   *  This object.
   */
  public function datasource($name) {
    return $this->set(self::DATASOURCES, $name);
  }
  /**
   * Declare a new logger.
   *
   * Fortissimo can use numerous loggers. You can declare
   * one or more loggers in your configuration.
   *
   * @param string $klass
   *   The fully qualified class name.
   * @param string $name
   *  The name of the logger. This is for other parts of the application
   *  to reference.
   * @return App
   *  The object.
   */
  public function logger($klass, $name = '') {
    if (empty($name)) {
      $name = str_replace('\\','_', $klass);
    }
    return $this->set(self::LOGGERS, $name)->whichInvokes($klass);
  }
  /**
   * Declare a new cache.
   *
   * @param string $name
   *  The name of the cache. Caches can be referenced by name in other parts of
   *  the application.
   * @return App
   *  The object.
   */
  public static function cache($name) {
    return self::set(self::CACHES, $name);
  }

  /**
   * Internal helper.
   */
  protected function set($cat, $name) {
    $this->currentCategory = $cat;
    $this->currentName = $name;
    $this->config[$cat][$name] = array();
    return $this;
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
   * @return App
   *  This object.
   */
  public function initialize(array $config = NULL) {
    if (!is_null($config)) $this->config = $config;
    return $this;
  }

  /**
   * Get the complete configuration array.
   *
   * This returns a datastructure that represents the configuration for the system.
   *
   * @return array
   *  The configuration.
   */
  public function configuration() {
    return $this->config;
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
  /**
   * Attach a command (with an optional name) to a route.
   *
   * @param string $klass
   *   The fully qualified name of a class, or an instance.
   * @param string $name
   *   The name of the command. If no name is supplied, one will automatically be generated.
   */
  public function does($klass, $name = NULL) {
    if (empty($name)) {
      // There's probably a more sensical name.
      $name = md5(rand());
    }
    return $this->doesCommand($name)->whichInvokes($klass);
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
   * Attach parameters to a command.
   *
   * @param string $param
   *   The name of the param to pass to the command.
   * @param mixed $value
   *   The default value. NULL if none is supplied.
   */
  public function using($param, $value = NULL) {
    $this->withParam($param)->whoseValueIs($value);

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
   * Turn on or off caching for a request or command.
   *
   * Command-based caching caches the results of just a specific command. It makes it
   * possible to have certain parts of a request be cached while not caching the entire
   * request.
   *
   * @code
   * <?php
   * Registry::request('foo')
   *   ->doesCommand('bar')
   *   ->whichInvokes('MyBarClass')
   *   ->whichUses('baz')->whoseValueIs('lurp')
   *   ->isCaching(TRUE);
   * ?>
   *
   * Request-based caching (EXPERIMENTAL) caches the output of an entire request.
   *
   * @code
   * <?php
   * Registry::request('foo')->isCaching(TRUE);
   * ?>
   * @endcode
   *
   * @param boolean $boolean
   *  TRUE to turn on caching, FALSE to disable caching.
   */
  public function isCaching($boolean = TRUE) {
    if ($this->currentCategory == self::REQUESTS) {
      $cat = $this->currentCategory;
      $name = $this->currentName;
      $this->config[$cat][$name]['#caching'] = $boolean;
/*
      if (!empty($this->commandName)) {
        $this->config[$cat][$name][$this->commandName]['cache'] = $boolean;
      }
      else {
        $this->config[$cat][$name]['#caching'] = $boolean;
      }

    }
    // Add caching in group commands.
    elseif ($this->currentCategory == self::GROUPS && !empty($this->commandName)) {
      $this->config[$cat][$name][$this->commandName]['cache'] = $boolean;
*/
    }
    return $this;

  }
}
