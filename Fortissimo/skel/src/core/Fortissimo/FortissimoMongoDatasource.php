<?php
/**
 * A basic datasource that wraps a MongoDB instance.
 *
 * This datasource wraps a MongoDB database instance.
 *
 * @ingroup Fortissimo
 */
 
/**
 * The MongoDB Datasource
 *
 * This class provides a simple access layer to the MongoDB database. Like
 * most datasource wrappers, it is very simple, and merely provides direct
 * access to a {@link MongoDB} instance.
 *
 * This uses two parameters from the configuration:
 * - server: The server URL (e.g. 'mongodb://localhost:27017')
 * - defaultDB: The database (e.g. 'myDB')
 *
 * @ingroup Fortissimo
 */
class FortissimoMongoDatasource extends FortissimoDatasource {
  
  /**
   * The MongoDB instance.
   */
  protected $mongoInstance = NULL;
  protected $mongoDB = NULL;
  protected $server = NULL;
  protected $dbName = NULL;
  
  /**
   * Initialize the database connection.
   *
   * This will open a connection to the server and then set the default
   * database.
   *
   * It expects two parameters:
   * - server: The server (default: 'mongodb://localhost:27017' or a php.ini override). 
   *   This can have user/pw info in it.
   * - defaultDB: The default database to use (required)
   *
   * The following parameters are passed on to Mongo: 'username', 'password', 'connect', 'timeout', 'replicaSet'.
   */
  public function init() {
    if (!isset($this->params['defaultDB'])) {
      throw new FortissimoInterruptException("'defaultDB' is a required parameter.");
    }
    
    $optionKeys = array('username', 'password', 'connect', 'timeout', 'replicaSet');
    
    // Avoid E_STRICT warning.
    $this->server = isset($this->params['server']) ? $this->params['server'] : NULL;
    
    $this->dbName = $this->params['defaultDB'];
    
    // Pass options in.
    $mongoOptions = array();
    foreach ($optionKeys as $pname) {
      if (isset($this->params[$pname])) {
        $mongoOptions[$pname] = $this->params[$pname];
      }
    }
    
    $this->mongoInstance = new Mongo($this->server);
    $this->mongoDB = $this->mongoInstance->selectDB($this->dbName);
  }
  
  /**
   * Get a MongoDB object.
   * 
   * @return MongoDB
   *  Returns an instance of the MongoDB.
   */
  public function get() {
    return $this->mongoDB;
  }
  
  /**
   * Get the Mongo instance.
   * 
   * This is useful in the rare cases when you need to get a handle on 
   * the Mongo instance directly -- perhaps to query a different database. Most of
   * the time, you can access the MongoDB object using {@link get()}.
   *
   * @return Mongo
   *  The Mongo instance.
   */
  public function getMongoInstance() {
    return $this->mongoInstance;
  }
} 