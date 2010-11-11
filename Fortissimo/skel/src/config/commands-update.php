<?php
/** @page commands.php
 * The commands.php file is the main configuration file for Fortissimo.
 *
 * There are (at least) seven different facilities you can configure in this file:
 *
 * - requests: This is how you instruct Fortissimo on how to handle inbound requests. Essentially,
 *   you map a request to a chain of commands. The default request is 'default'. For more on 
 *   requests, check out doc/Command-examples.mdown and doc/QUICKSTART.mdown.
 * - groups: Groups are an abstraction that allows you to declare a particular chain of commands, 
 *   but not assign them to a request. You can import groups into requests wherever you want.
 *   Effectively, this allows you to create a group of related commands that you can later use
 *   in multiple requests.
 * - datasources: Does your appplication use MySQL, MongoDB or some other data source? You can 
 *   declare the datasource here, which then makes it available throughout the application. See
 *   the FortissimoMongoDatasource class for a simple example of declaring a datasource.
 * - loggers: Fortissimo provides a lightweight and extensible logging framework. You can tell it 
 *   where to log messages by declaring a logger. The built in FAIL and FOIL loggers provide good
 *   starting points for displaying logged messages in the browser.
 * - caches: Fortissimo supports caching at the highest levels. You can declare your caches in the
 *   configuration file, and Fortissimo will try to handle all of the actual caching for you. This
 *   is more advanced, and is generally only needed on high-traffic apps.
 * - include paths: Fortissimo uses a PHP autoloader to find and include classes. You can tell
 *   Fortissimo what paths to use when seeking for classes.
 * - request mapper: Each Fortissimo instance can have a single request mapper. This is a 
 *   class that handles matching inbound URIs (or strings) onto a request. Fortissimo uses
 *   its own FortissimoRequestMapper by default. You can replace it using 
 *   Config::useRequestMapper().
 *
 *
 *
 * Requests are "containers" that describe a single process from beginning to end. A request is
 * a chain of commands. Each command is executed in sequence, with each command having access to
 * the output of the last command.
 *
 * There are a few requests that have special meaning to Fortissimo:
 *
 * - default: This is the request that will be executed when no request is explicitly issued.  Think
 *   of it as Fortissimo's equivalent to request a base URL. 'default' is equivalent to 'index.html'
 *   in that analogy.
 * - 404: If a request named 404 exists, it will be used whenever a 404 error is encountered (e.g.
 *   when no request is found to match the incoming URI/string.) Your request mapper, should you use
 *   one, can redirect the 404 name to a different request name, too.
 */

/**
 * @section include_path_config Include Paths
 *
 * To declare a new include path, you will want to use code like this:
 *
 * @code
 * <?php
 * Config::includePath('path/to/some/classes');
 * ?>
 * @endcode
 *
 * By default, Fortissimo uses a flat namespace (no deeply nested directories) because Fortissimo 
 * itself is a thin framework.
 */
// Config::includePath('includes/MyClasses');

/**
 * @section datasource_config Datasources
 * Fortissimo provides a very thin database abstraction layer.
 *
 * To use it with MongoDB, simply customize the setup below. To use another
 * database, implement FortissimoDatasource, and then use the implementing
 * class in the invoke method here.
 *
 * @code
 * Config::datasource('db') // Name of datasource
 *   ->whichInvokes('FortissimoMongoDatasource') // The class it uses
 *   // Parameters for the FortissimoMongoDatasource:
 *   ->withParam('server')->whoseValueIs('mongodb://localhost:27017')
 *   ->withParam('defaultDB')->whoseValueIs('my_db_name')
 *   ->withParam('isDefault')->whoseValueIs(TRUE) // Only datasource one can be default.
 * ;
 * @endcode
 *
 * You can use as many datasources as you want. Just give each one a different
 * name.
 */
Config::datasource('db') // Name of datasource
  ->whichInvokes('FortissimoMongoDatasource') // The class it uses
  ->withParam('server')->whoseValueIs('mongodb://localhost:27017')
  ->withParam('defaultDB')->whoseValueIs('my_db_name')
  ->withParam('isDefault')->whoseValueIs(TRUE) // Only datasource one can be default.
;


/**
 * @section group_config Groups
 * A group is a grouping of commands that cannot be executed as a request.
 *
 * While they are not directly executed (ever), they can be included into a request. See the 
 * example in the section on requests.
 *
 * Example:
 *
 * @code
 * <?php
 * Config::group('bootstrap')
 *   ->doesCommand('some_command')
 *     ->whichInvokes('SomeCommandClass')
 *     ->withParam('some_param')
 *       ->whoseValueIs('some value');
 *   ->doesCommand('some_other_command')->whichInvokes('SomeOtherCommandClass')
 * ;
 * ?>
 * @endcode
 * 
 * The above defines a group with a chain of two commands. The first has a single parameter. The 
 * second has no parameters.
 */
Config::group('bootstrap')
  //->doesCommand('some_command')->whichInvokes('SomeCommandClass')
  //->doesCommand('some_other_command')->whichInvokes('SomeOtherCommandClass')
;

/**
 * @section request_config Requests
 *
 * This part of the configuration file is used for mapping an inbound request to a 
 * chain of commands. Fortissimo will begin with the first command and process commands
 * one at a time until the chain has completed (or some error condition has occurred.)
 *
 * Requests are "containers" that describe a single process from beginning to end. A request is
 * a chain of commands. Each command is executed in sequence, with each command having access to
 * the output of the last command.
 *
 * There are a few requests that have special meaning to Fortissimo:
 *
 * - default: This is the request that will be executed when no request is explicitly issued.  Think
 *   of it as Fortissimo's equivalent to request a base URL. 'default' is equivalent to 'index.html'
 *   in that analogy.
 * - 404: If a request named 404 exists, it will be used whenever a 404 error is encountered (e.g.
 *   when no request is found to match the incoming URI/string.) Your request mapper, should you use
 *   one, can redirect the 404 name to a different request name, too.
 *
 *
 * @code
 * <?php
 * Config::request('default')
 *  // Bootstrap
 *   ->usesGroup('bootstrap')
 *   // Initialize the context with some values.
 *   ->doesCommand('initContext')
 *     ->whichInvokes('FortissimoAddToContext')
 *     ->withParam('title')
 *       ->whoseValueIs('%%PROJECT%%')
 *     ->withParam('welcome')
 *       ->whoseValueIs('Fortissimo has been successfully installed.')
 *   // Use the template engine to generate a welcome page.
 *   ->doesCommand('tpl')
 *     ->whichInvokes('FortissimoTemplate')
 *     ->withParam('template')
 *       ->whoseValueIs('example.twig')
 *     ->withParam('templateDir')
 *       ->whoseValueIs('theme/vanilla')
 *     ->withParam('templateCache')
 *       ->whoseValueIs('./cache')
 *     ->withParam('disableCache')
 *       ->whoseValueIs(FALSE)
 *     // ->withParam('debug')->whoseValueIs(FALSE)
 *     // ->withParam('trimBlocks')->whoseValueIs(TRUE)
 *     // ->withParam('auto_reload')->whoseValueIs(FALSE)
 *
 *   // Send the rendered welcome page to the browser.
 *   ->doesCommand('echo')
 *     ->whichInvokes('FortissimoEcho')
 *     ->from('context:tpl')
 * ;
 * @endcode
 *
 * A request can have two things in its chain: commands and groups.
 */
Config::request('default')
  // Bootstrap
  ->usesGroup('bootstrap')
  // Initialize the context with some values.
  ->doesCommand('initContext')
    ->whichInvokes('FortissimoAddToContext')
    ->withParam('title')
      ->whoseValueIs('%%PROJECT%%')
    ->withParam('welcome')
      ->whoseValueIs('Fortissimo has been successfully installed.')
  // Use the template engine to generate a welcome page.
  ->doesCommand('tpl')
    ->whichInvokes('FortissimoTemplate')
    ->withParam('template')
      ->whoseValueIs('example.twig')
    ->withParam('templateDir')
      ->whoseValueIs('theme/vanilla')
    ->withParam('templateCache')
      ->whoseValueIs('./cache')
    ->withParam('disableCache')
      ->whoseValueIs(TRUE) // This should be FALSE on production.
    // ->withParam('debug')->whoseValueIs(FALSE)
    // ->withParam('trimBlocks')->whoseValueIs(TRUE)
    // ->withParam('auto_reload')->whoseValueIs(FALSE)
    
  // Send the rendered welcome page to the browser.
  ->doesCommand('echo')
    ->whichInvokes('FortissimoEcho')
    ->withParam('text')
      ->from('context:tpl')
;

/**
 * @section logger_config Loggers
 * 
 * You can configure Fortissimo to log to one or more logging backends.
 *
 * @code
 * Config::logger('foil')
 *  ->whichInvokes('FortissimoOutputInjectionLogger')
 * ;
 * @endcode
 *
 * The code above configures Fortissimo's FOIL logger, which simply logs all errors into Standard
 * Output. Another built-in logger is FortissimoArrayInjectionLogger (FAIL), which logs messages
 * into an array for later retrieval.
 *
 * @code
 * Config::logger('fail')
 *   ->whichInvokes('ForitissimoArrayInjectionLogger')
 *   // Use this only if you want to restrict what is logged by this logger:
 *   ->withParam('categories')
 *     ->whoseValueIs('Fatal Error,Recoverable Error')
 * ;
 * @endcode
 *
 * New loggers can be created very easily. See the FortissimoOutputInjectionLogger code for an 
 * example.
 *
 * Loggers that ship with Fortissimo:
 *  - FortissimoOutputInjectionLogger (aka FOIL)
 *  - FortissimoArrayInjectionLogger (aka FAIL)
 *  - SimpleOutputInjectionLogger (aka SOIL)
 *  - SimpleArrayInjectionLogger (aka SAIL)
 *  - FortissimoSyslogLogger (aka... Fizzle?)
 */
Config::logger('foil')
  ->whichInvokes('FortissimoOutputInjectionLogger')
;

/**
 * @section cache_config Caches
 *
 * Fortissimo has built-in support for multiple caching backends. For example, applications could
 * strategically cache some data in memcache and some in APC. Fortissimo includes a simple 
 * implementation of a Memcached caching layer (FortissimoMemcacheCache). 
 *@code
 * <?php
 * Config::cache('memcache')
 *   ->whichInvokes('FortissimoMemcacheCache')
 *   ->withParam('servers')
 *     ->whoseValueIs(array('example.com:11211', 'example.com:11212'))
 *   ->withParam('persistent')
 *     ->whoseValueIs(FALSE)
 *   ->withParam('compress')
 *     ->whoseValueIs(FALSE)
 * ;
 * ?>
 * @endcode
 *
 * If you want commands to cache (as opposed to just entire requests), your classes will need
 * to implement Cacheable and extend BaseFortissimoCommand (or you can handle caching yourself
 * in FortissimoCommand::execute()).
 */