<?php
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
