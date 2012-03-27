<?php
/**
 * @file
 * The logger manager.
 */

namespace Fortissimo\Logger;

/**
 * Manage loggers for a server.
 *
 * A Fortissimo instance may have zero or more loggers. Loggers
 * perform the standard task of handling messages that need recording for
 * review by administrators.
 *
 * The logger manager manages the various logging instances, delegating logging
 * tasks.
 *
 */
class Manager {

  protected $loggers = NULL;

  /**
   * Build a new logger manager.
   *
   * @param QueryPath $config
   *  The configuration object. Typically, this is from commands.xml.
   */
  //public function __construct(QueryPath $config) {
  public function __construct($config) {
    // Initialize array of loggers.
    $this->loggers = &$config;
  }

  public function setCacheManager(\Fortissimo\CacheManager $manager) {
    foreach ($this->loggers as $name => $obj) $obj->setCacheManager($manager);
  }

  public function setDatasourceManager(\Fortissimo\DatasourceManager $manager) {
    foreach ($this->loggers as $name => $obj) $obj->setDatasourceManager($manager);
  }


  /**
   * Get a logger.
   *
   * @param string $name
   *  The name of the logger, as indicated in the configuration.
   * @return Fortissimo::Logger
   *  The logger corresponding to the name, or NULL if no such logger is found.
   */
  public function getLoggerByName($name) {
    return $this->loggers[$name];
  }

  /**
   * Get all buffered log messages.
   *
   * Some, but by no means all, loggers buffer messages for later retrieval.
   * This method provides a way of retrieving all buffered messages from all
   * buffering loggers. Messages are simply concatenated together from all of
   * the available loggers.
   *
   * To fetch the log messages of just one logger instead of all of them, use
   * {@link getLoggerByName()}, and then call that logger's {@link Fortissimo::Logger::getMessages()}
   * method.
   *
   * @return array
   *  An indexed array of messages.
   */
  public function getMessages() {
    $buffer = array();
    foreach ($this->loggers as $name => $logger) {
      $buffer += $logger->getMessages();
    }
    return $buffer;
  }

  /**
   * Log messages.
   *
   * @param mixed $msg
   *  A string or an Exception.
   * @param string $category
   *  A string indicating what type of message is
   *  being logged. Standard values for this are:
   *  - error
   *  - warning
   *  - info
   *  - debug
   *  Your application may use whatever values are
   *  fit. However, underlying loggers may interpret
   *  these differently.
   * @param string $details
   *   Additional information. When $msg is an exception,
   *   this will automatically be populated with stack trace
   *   information UNLESS explicit string information is passed
   *   here.
   */
  public function log($msg, $category, $details = '') {
    foreach ($this->loggers as $name => $logger) {
      $logger->rawLog($msg, $category);
    }
  }
}
