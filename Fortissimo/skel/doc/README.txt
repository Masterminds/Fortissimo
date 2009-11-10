Introduction
============
This application was built using the Fortissimo Framework.


About Fortissimo
================
Fortissimo is a PHP application framework designed to help 
software developers quickly build websites that will scale.

A Fortissimo application has the following directory structure:

PROJECT
 |
 |- build: Destination for Phing build commands
 |- doc: Destination for documentation
 |  |- Fortissimo: Documentation (included) about Fortissimo
 |     |- api: Complete API reference for Fortissimo.
 |
 |- test: Destination for PHPUnit unit tests
 |- src
    |- build.xml: The Phing make file
    |- config : All configuration files
    |   |- commands.xml: The Fortissimo command configuration file
    |
    |- core: Core files. DO NOT EDIT
    |   |- Fortissimo: Fortissimo library. Built-in commands live here.
    |   |- QueryPath: QueryPath library.
    |
    |- includes: Destination for application-specific code
    |- index.php: Bootstrap script
    |- .htaccess: Apache configuration directives.

The primary configuration system for Fortissimo is the commands.xml file.

All application-specific code should be stored in src/includes. The structure of that
directory should reflect your application. To get all files to autoload correctly,
you may need to add paths in the commands.xml file using the <include/> tag.

Core files, which should *never* be altered, can be found in src/core. These files
are part of the Fortissimo framework, and will be overwritten during a framework
upgrade. The main server, Fortissimo.php, is typically compressed. It, too, should
not be altered.

Typically, a Fortissimo application is constructed by creating custom commands, 
each of which performs a single task, and then chaining the commands together using
the commands.xml file. A chain of commands is wrapped in a request, where a request
has an ID. When fortissimo handles a request, it will execute each command in the 
request one at a time.

About Phing
===========
Phing is a build tool for PHP. It is similar to ANT, Rake, and GNU Make.

Use Phing to perform various tasks on your project. For example, this command
can be used to generate documentation from PHPDoc comments:

$ phing doc

To see a list of all supported phing commands, run this:

$ phing -l

(That's a lowercase L)

About QueryPath
===============
Fortissimo uses the QueryPath tool to work with XML and HTML processing. QueryPath
is available from anywhere in Fortissimo. You can use it in your applications to
process XML and HTML.

Learn more about QueryPath at http://querypath.org

About the License
=================
Fortissimo is released under an MIT-style license. Because of this, you are free
to build your own applications, and you are not obligated to make those
applications Open Source. While you are under no obligation, we encourage
you to submit bugs, patches, and improvements back to the project so that
the framework itself will improve over time.