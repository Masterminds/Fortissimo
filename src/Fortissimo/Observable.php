<?php
/**
 * @file
 * Fortissimo::Observable class.
 */
namespace Fortissimo;

/**
 * Classes that implement this advertise that they support event listening support.
 *
 * Commands in Fortissimo may optionally support an events model in which the
 * command fires events that other classes may then respond to.
 *
 * The core event system is part of Fortissimo proper, but commands may or may
 * not choose to declare (or answer) any events. Commands that extend
 * Fortissimo::Command::Base can very easily declare and answer events. Those that do
 * not will need to provide their own event management, adhering to this interface.
 */
interface Observable {
  /**
   * Set the event handlers.
   *
   * This tells the Observable what listeners are registered for the given
   * object. The listeners array should be an associative array mapping
   * event names to an array of callables.
   *
   * @code
   * <?php
   * array(
   *   'load' => array(
   *      'function_name'
   *      function () {},
   *      array($object, 'methodName'),
   *      array('ClassNam', 'staticMethod').
   *    ),
   *   'another_event => array(
   *      'some_other_function',
   *    ),
   * );
   * ?>
   * @endcode
   *
   * @param array $listeners
   *  An associative array of event names and an array of eventhandlers.
   */
  public function setEventHandlers($listeners);

  /**
   * Trigger a particular event.
   *
   * @param string $eventName
   *   The name of the event.
   * @param array $data
   *   Any data that is to be passed into the event.
   * @return
   *   An optional return value, as determined by the particular event.
   */
  public function fireEvent($eventName, $data = NULL);
}
