<?php
/**
 * @file
 * Fortissimo::Command::BaseParameterCollection class.
 */
namespace Fortissimo\Command;

/**
 * Container for parameter descriptions.
 *
 * This collection contains parameters. It is used by anything that extends
 * Fortissimo::Command::Base to store parameter information for use
 * in Fortissimo::Command::Base::explain() and
 * Fortissimo::Command::Base::expects(). A builder for these is found
 * in Fortissimo::Command::Base::description(), which provides a semi-fluent
 * interface for defining expectations.
 * @see Fortissimo::Command::Base
 * @see Fortissimo::Command::BaseParameter
 */
class BaseParameterCollection implements IteratorAggregate {
  protected $params = array();
  protected $events = array();
  protected $description = '';
  protected $paramCounter = -1;
  protected $returns = 'Nothing';

  public function __construct($description) {$this->description = $description;}

  public function usesParam($name, $description) {
    $param = new BaseParameter($name, $description);
    $this->params[++$this->paramCounter] = $param;

    return $this;
  }
  /**
   * Add a filter to this parameter.
   *
   * A parameter can have any number of filters. Filters are used to
   * either clean (sanitize) a value or check (validate) a value. In the first
   * case, the system will attempt to remove bad data. In the second case, the
   * system will merely check to see if the data is acceptable.
   *
   * Fortissimo supports all of the filters supplied by PHP. For a complete
   * list, including valid options, see
   * http://us.php.net/manual/en/book.filter.php.
   *
   * Filters each have options, and the options can augment filter behavior, sometimes
   * in remarkable ways. See http://us.php.net/manual/en/filter.filters.php for
   * complete documentation on all filters and all of their options.
   *
   * @param string $filter
   *  One of the predefined filter types supported by PHP. You can obtain the list
   *  from the PHP builtin function filter_list(). Here are values currently
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
   *  - magic_quotes: Run addslashes().
   *  - callback: Use the given callback to filter.
   *  - this: A convenience for 'callback' with the options array('options'=>array($this, 'func'))
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
   *  {@link BaseFortissimo::Command::doCommand()} method.
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

  /**
   * Declares an event for this command.
   *
   * This indicates (though does not enforce) that this command may
   * at some point in execution fire an event with the given event name.
   *
   * Event listeners can bind to this command's event and be notified when the
   * event fires.
   *
   * @param string $name
   *  The name of the event. Example: 'load'.
   * @param string $description
   *  A description of the event.
   * @return
   *  This object.
   */
  public function declaresEvent($name, $description) {
    $this->events[$name] = $description;
    return $this;
  }

  /**
   * Set all events for this object.
   *
   * The $events array must follow this form:
   *
   * @code
   * <?php
   * array(
   *  'event_name' => 'Long description help text',
   *  'other_event' => 'Description of conditions under which other_event is called.',
   * );
   * ?>
   * @endcode
   */
  public function setEvents(array $events) {
    $this->events = $events;
    return $this;
  }

  public function events() { return $this->events; }

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
