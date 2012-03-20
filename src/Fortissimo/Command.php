<?php
/**
 * @file
 * Fortissimo::Command interface.
 */
namespace Fortissimo;

/**
 * A Fortissimo command.
 *
 * The main work unit in Fortissimo is the Fortissimo::Command. A Fortissimo::Command is
 * expected to conduct a single unit of work -- retrieving a datum, running a
 * calculation, doing a database lookup, etc. Data from a command (if any) can then
 * be stored in the {@link Fortissimo::ExecutionContext} that is passed along the
 * chain of commands.
 *
 * Each command has a request-unique <b>name</b> (only one command in each request
 * can have a given name), a set of zero or more <b>params</b>, passed as an array,
 * and a <b>{@link Fortissimo::ExecutionContext} object</b>. This last object contains
 * the results (if any) of previously executed commands, and is the depository for
 * any data that the present command needs to pass along.
 *
 * Typically, the last command in a request will format the data found in the context
 * and send it to the client for display.
 */
interface Command {
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
   *  {@link Fortissimo::Command::Base} for an example of a caching service. There is
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
   * the {@link Fortissimo::ExcecutionContext} object. On the occasion of an error,
   * the command can either throw a {@link Fortissimo::Exception} (or any subclass
   * thereof), in which case the application will attempt to handle the error. Or it
   * may throw a {@link Fortissimo::Interrupt}, which will interrupt the flow of the
   * application, causing the application to forgo running the remaining commands.
   *
   * @param array $paramArray
   *  An associative array of name/value parameters. A value may be of any data
   *  type, including a classed object or a resource.
   * @param Fortissimo::ExecutionContext $cxt
   *  The execution context. This can be modified by the command. Typically,
   *  though, it is only written to. Reading from the context may have the
   *  effect of making the command less portable.
   * @throws Fortissimo::Interrupt
   *  Thrown when the command should be treated as the last command. The entire
   *  request will be terminated if this is thrown.
   * @throws Fortissimo::Exception
   *  Thrown if the command experiences a general execution error. This may not
   *  result in the termination of the request. Other commands may be processed after
   *  this.
   */
  public function execute($paramArray, \Fortissimo\ExecutionContext $cxt);

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
