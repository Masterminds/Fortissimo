
This application was built using the Fortissimo Framework.

Core files, which should *never* be altered, can be found in src/core. These files
are part of the Fortissimo framework, and will be overwritten during a framework
upgrade.

All application-specific code should be stored in src/includes. The structure of 
that directory should reflect your application.

The commands.xml file maps requests (often URL fragments) onto a chain of commands
which will be run in sequence.

Typically, a Fortissimo application is constructed by creating custom commands, 
each of which performs a single task, and then chaining the commands together using
the commands.xml file. A chain of commands is wrapped in a request, where a request
has an ID. When fortissimo handles a request, it will execute each command in the 
request one at a time.