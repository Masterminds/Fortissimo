<?php
/**
 * A basic datasource that wraps a MongoDB instance.
 *
 * This datasource wraps a MongoDB database instance.
 *
 */
namespace Fortissimo\Datasource;
/**
 * The MongoDB Datasource
 *
 * This class provides a simple access layer to the MongoDB database. Like
 * most datasource wrappers, it is very simple, and merely provides direct
 * access to a {@link MongoDB} instance.
 *
 * This uses two parameters from the configuration (plus one optional one):
 * - server: The server URL (e.g. 'mongodb://localhost:27017')
 * - defaultDB: The database (e.g. 'myDB')
 * - instanceName: A connection to a database is returned and the mongo connection
 *   instance is added as an additional datasource if this parameter is set. (OPTIONAL)
 */
class MongoDB {

  /**
   * Initialize the database connection.
   *
   * This will open a connection to the server and then set the default
   * database.
   *
   * It expects two parameters (plus one optional one):
   * - server: The server (default: 'mongodb://localhost:27017' or a php.ini override). 
   *   This can have user/pw info in it.
   * - defaultDB: The default database to use (required)
   * - instanceName: A connection to a database is returned and the mongo connection
   *   instance is added as an additional datasource if this parameter is set. (OPTIONAL)
   *
   * The following parameters are passed on to Mongo: 'username', 'password', 'connect', 'timeout', 'replicaSet'.
   */
  public function __invoke($params, $name, $manager) {
    if (!isset($params['defaultDB'])) {
      throw new \Fortissimo\InterruptException("'defaultDB' is a required parameter.");
    }

    $optionKeys = array('username', 'password', 'connect', 'timeout', 'replicaSet');

    // Avoid E_STRICT warning.
    $server = isset($params['server']) ? $params['server'] : NULL;

    $dbName = $params['defaultDB'];

    // Pass options in.
    $mongoOptions = array();
    foreach ($optionKeys as $pname) {
      if (isset($this->params[$pname])) {
        $mongoOptions[$pname] = $this->params[$pname];
      }
    }

    $mongoInstance = new \Mongo($server);
    if (isset($params['instanceName'])) {
      $manager->addDatasource(function () use ($mongoInstance) { return $mongoInstance; }, $params['instanceName']);
    }

    return $mongoInstance->selectDB($dbName);
  }
}
