<?php
/**
 * @file
 *
 * The base logger class.
 */
namespace Fortissimo\Logger;
/**
 * A logger responsible for logging messages to a particular destination.
 *
 * The FortissimoLogger abstract class does recognize one parameter.
 *
 *  - 'categories': An array or comma-separated list of categories that this logger listens for.
 *     If no categories are set, this logs ALL categories.
 *
 * Category logic is encapsulated in the method Fortissimo::Logger::Base::isLoggingThisCategory().
 *
 *
 */
abstract class Base {

  /**
   * The parameters for this logger.
   */
  protected $params = NULL;
  protected $facilities = NULL;
  protected $name = NULL;
  protected $datasourceManager = NULL;
  protected $cacheManager = NULL;

  /**
   * Construct a new logger instance.
   *
   * @param array $params
   *   An associative array of name/value pairs.
   * @param string $name
   *   The name of this logger.
   */
  public function __construct($params = array(), $name = 'unknown_logger') {
    $this->params = $params;
    $this->name = $name;

    // Add support for facility declarations.
    if (isset($params['categories'])) {
      $fac = $params['categories'];
      if (!is_array($fac)) {
        $fac = explode(',', $fac);
      }
      // Assoc arrays provide faster lookups on keys.
      $this->facilities = array_combine($fac, $fac);
    }

  }

  public function setDatasourceManager(\Fortissimo\Datasource\Manager $manager) {
    $this->datasourceManager = $manager;
  }

  public function setCacheManager(\Fortissimo\Cache\Manager $manager) {
    $this->logManager = $manager;
  }


  /**
   * Get the name of this logger.
   *
   * @return string
   *  The name of this logger.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Return log messages.
   *
   * Some, but not all, loggers buffer messages for retrieval later. This
   * method should be used to retrieve messages from such loggers.
   *
   * @return array
   *  An indexed array of log message strings. By default, this returns an
   *  empty array.
   */
  public function getMessages() {
    return array();
  }

  /**
   * Check whether this category is being logged.
   *
   * In general, this check is run from rawLog(), and so does not need to be
   * directly called elsewhere.
   *
   * @param string $category
   *  The category to check.
   * @return boolean
   *  TRUE if this is logging for the given category, false otherwise.
   */
  public function isLoggingThisCategory($category) {
    return empty($this->facilities) || isset($this->facilities[$category]);
  }

  /**
   * Handle raw log requests.
   *
   * This handles the transformation of objects (Exceptions)
   * into loggable strings.
   *
   * @param mixed $message
   *  Typically, this is an Exception, some other object, or a string.
   *  This method normalizes the $message, converting it to a string
   *  before handing it off to the {@link log()} function.
   * @param string $category
   *  This message is passed on to the logger.
   * @param string $details
   *  A detail for the given message. If $message is an Exception, then
   *  details will be automatically filled with stack trace information.
   */
  public function rawLog($message, $category = 'General Error', $details = '') {

    // If we shouldn't log this category, skip this step.
    if (!$this->isLoggingThisCategory($category)) return;

    if ($message instanceof \Exception) {
      $buffer = $message->getMessage();

      if (empty($details)) {
        $details = get_class($message) . PHP_EOL;
        $details .= $message->getMessage() . PHP_EOL;
        $details .= $message->getTraceAsString();
      }

    }
    elseif (is_object($message)) {
      $buffer = $message->toString();
    }
    else {
      $buffer = $message;
    }
    $this->log($buffer, $category, $details);
    return;
  }

  /**
   * Initialize the logger.
   *
   * This will happen once per server construction (typically
   * once per request), and it will occur before the command is executed.
   */
  public abstract function init();

  /**
   * Log a message.
   *
   * @param string $msg
   *  The message to log.
   * @param string $severity
   *  The log message category. Typical values are
   *  - warning
   *  - error
   *  - info
   *  - debug
   * @param string $details
   *  Further text information about the logged event.
   */
  public abstract function log($msg, $severity, $details);

}
