
This application was built using the Fortissimo Framework.

A Fortissimo application has the following directory structure:

PROJECT
 |
 |- build: Destination for Phing build commands
 |- doc: Destination for documentation
 |- test: Destination for PHPUnit unit tests
 |- src
    |- build.xml: The make file
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