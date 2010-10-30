<?php

/**
 * Fortissimo users the SPL autoloader to load classes, where the 
 * assumption is that ClassName is stored in ClassName.php, ClassName.cmd.php,
 * or ClassName.inc.
 *
 * To add additional directories to the class loader, specify them here.
 *
 * Example:
 */
// Config::includePath('path/to/include/for/autoloading');

/**
 * A request maps an incoming request to a series of commands.
 */
// Create a new request.
Config::request('foo')
  ->usesGroup('MyGroup')
  ->doesCommand('bar')
    ->whichInvokes('MyBar')
    ->withParam('text')
      ->whoseValueIs('This is some text')
  ->doesCommand('baz')
    ->whichInvokes('MyBaz')
    ->withParam('path')
      ->from('get:path')
;

/**
 * A group is a grouping of commands that cannot be executed as a request.
 *
 * They can be referenced in requests, though. Think of it as a way to create a group
 * of commands that you can use whenever it is convenient.
 */
 
// Greate a group.
Config::group('bootstrap')
  ->doesCommand('bar')
    ->whichInvokes('MyBar')
    ->withParam('text')
      ->whoseValueIs('This is some text')
;

/*
 * Fortissimo provides a very thin database abstraction layer.
 * 
 * To use it with MongoDB, simply customize the setup below. To use another
 * database, implement FortissimoDatasource, and then use the implementing
 * class in the invoke method here.
 * 
 * You can use as many datasources as you want. Just give each one a different
 * name.
 */

// Create a new datasource which connects to a MongoDB server.
Config::datasource('db')
  ->whichInvokes('FortissimoMongoDatasource')
  ->withParam('server')
    ->whoseValueIs('mongodb://localhost:27017</param>')
  ->withParam('defaultDB')
    ->whoseValueIs('%%PROJECT%%')
  // Only one database can be set as the default.
  ->withParam('isDefault')
    ->whoseValueIs(TRUE)
;

/**
 * Fortissimo allows you to specify one or more loggers to which 
 * important data can be written during the processing of a request.
 */

// Logs directly into STDOUT (the browser, the console).
Config::logger('foil')
  ->whichInvokes('FortissimoOutputInjectionLogger')
;

// Buffers log messages in an array to be retrieved later. 
// Config::logger('fail')
//   ->whichInvokes('ForitissimoArrayInjectionLogger')
// ;

// Example of how a cache might be declared.
// Config::cache('memcache')
//   ->whichInvokes('MemcacheCaching')
//     ->withParam('Server')
//     ->whoseValueIs('localhost:11211')
// ;