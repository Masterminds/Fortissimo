<?php
/**
 * @file
 * Fortissimo::Command::BaseParameter class.
 */
namespace Fortissimo\Command;

/**
 * Describe a parameter.
 *
 * Describe a parameter for a command.
 *
 * @see Fortissimo::Command
 * @see Fortissimo::Command::Base::expects()
 * @see Fortissimo::Command::BaseParameterCollection
 */
class BaseParameter {
  protected $filters = array();

  protected $name, $description, $defaultValue;
  protected $required = FALSE;

  /**
   * Create a new parameter with a name, and optionally a description.
   *
   * @param string $name
   *  The name of the parameter. This is used to fetch the parameter
   *  from the server.
   * @param string $description
   *  A human-readible description of what this parameter is used for.
   *  This is used to automatically generate assistance.
   */
  public function __construct($name, $description = '') {
    $this->name = $name;
    $this->description = $description;
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
   * list, including valide options, see
   * {@link http://us.php.net/manual/en/book.filter.php}.
   *
   * Filters each have options, and the options can augment filter behavior, sometimes
   * in remarkable ways. See {@link http://us.php.net/manual/en/filter.filters.php} for
   * complete documentation on all filters and all of their options.
   *
   * @param string $filter
   *  One of the predefined filter types supported by PHP. You can obtain the list
   *  from the PHP builtin function {@link filter_list()}. Here are values currently
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
   *  - magic_quotes: Run {@link addslashes()}.
   *  - callback: Use the given callback to filter.
   * @param mixed $options
   *  This can be either an array or an OR'd list of flags, as specified in the
   *  PHP documentation.
   * @return Fortissimo::Command::BaseParameter
   *  Returns this object to facilitate chaining.
   */
  public function addFilter($filter, $options = NULL) {
    $this->filters[] = array('type' => $filter, 'options' => $options);
    return $this;
  }

  /**
   * Set all filters for this object.
   * Validators must be in the form:
   * <?php
   * array(
   *   array('type' => FILTER_SOME_CONST, 'options' => array('some'=>'param')),
   *   array('type' => FILTER_SOME_CONST, 'options' => array('some'=>'param'))
   * );
   * ?>
   * @param array $filters
   *  An indexed array of validator specifications.
   * @return Fortissimo::Command::BaseParameter
   *  Returns this object to facilitate chaining.
   */
  public function setFilters($filters) {
    $this->filters = $filters;
    return $this;
  }



  public function setRequired($required) {
    $this->required = $required;
  }

  public function isRequired() {return $this->required;}

  /**
   * Set the default value.
   */
  public function setDefault($val) {
    $this->defaultValue = $val;
  }

  /**
   * Get the default value.
   */
  public function getDefault() {
    return $this->defaultValue;
  }

  /**
   * Get the list of filters.
   * @return array
   *  An array of the form specified in setFilters().
   */
  public function getFilters() { return $this->filters; }
  public function getName() { return $this->name; }
  public function getDescription() { return $this->description; }
}
