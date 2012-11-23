<?php
/** @file
 * Datasource providng PDO access to SQL databases.
 */
namespace Fortissimo\Datasource;
/**
 * Provides a datasource access layer for PDO.
 *
 * This is a thin wrapper layer for controlling the initialization and
 * access of a PDO connection. PDO is part of PHP, and is documented on PHP.net.
 *
 * Example configuration:
 *
 * @code
 * <?php
 * $registry->datasource('pdo')
 *   ->whichInvokes('\Fortissimo\Datasource\PDO')
 *   ->withParam('dsn')
 *     ->whoseValueIs('mysql:host=localhost;dbname=test')
 *   ->withParam('user')
 *     ->whoseValueIs('db_user')
 *   ->withParam('password')
 *     ->whoseValueIs('db_pass')
 *   // Hopefully rarely used:
 *   ->withParam('driver_options)
 *     ->whoseValueIs(array(PDO::SOME_CONST => 'some_value'))
 * ;
 * ?>
 * @endcode
 *
 * To access a PDO database connection, do something like this:
 *
 * @code
 * <?php
 * class FooClass extends \Fortissimo\Command\Base {
 *
 *  public function expects() {
 *    return $this
 *      ->description('Some command that does something')
 *    ;
 *  }
 *
 *  public function doCommand() {
 *    // Note that 'pdo' is the name we declared in the example above.
 *    $db = $this->context->datasource('pdo');
 *    // Do whatever with DB:
 *    $res = $db->query('SELECT * FROM foo');
 *  }
 * }
 * ?>
 * @endcode
 *
 * Parameters
 * - dsn: The PDO DSN
 * - user: The username for the database connection
 * - password: The password for the database connection
 * - driver_options: An array of driver options, as defined by PDO.
 *
 * @ingroup Fortissimo
 */
class PDO {

  public function __invoke($params, $name, $manager) {

    if (empty($params['dsn'])) {
      throw new \Fortissimo\InterruptException('Missing DSN in ' . __CLASS__);
    }

    $dsn = $params['dsn'];

    $user = isset($params['user']) ? $params['user'] : NULL;
    $pass = isset($params['password']) ? $params['password'] : NULL;
    $options = isset($params['driver_options']) ? $params['driver_options'] : NULL;

    return new \PDO($dsn, $user, $pass, $options);
  }
}
