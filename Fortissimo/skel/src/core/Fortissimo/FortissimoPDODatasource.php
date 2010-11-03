<?php
/** @file
 * Datasource providng PDO access to SQL databases.
 */

/**
 * Provides a datasource access layer for PDO.
 *
 * This is a thin wrapper layer for controlling the initialization and
 * access of a PDO connection.
 *
 * Example configuration:
 *
 * @code
 * <?php
 * Config::datasource('pdo')
 *   ->whichInvokes('FortissimoPDODatasource')
 *   ->withParam('dsn')
 *     ->whoseValueIs('mysql:host=localhost;dbname=test)
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
 * Parameters
 */
class FortissimoPDODatasource extends FortissimoDatasource {
  
  /**
   * The connection object, which is opened during init().
   */
  protected $con = NULL;
  

  public function init() {
    
    if (empty($this->params['dsn'])) {
      throw new FortissimoInterruptException('Missing DSN in ' . __CLASS__);
    }
    
    $dsn = $this->params['dsn'];
    
    $user = isset($this->params['user']) ? $this->params['user'] : NULL;
    $pass = isset($this->params['password']) ? $this->params['password'] : NULL;
    $options = isset($this->params['driver_options']) ? $this->params['driver_options'] : NULL;
    
    $this->con = new PDO($dsn, $user, $pass, $options);
  }
  
  public function get() {
    return $this->con;
  }
  
}