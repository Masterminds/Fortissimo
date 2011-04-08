<?php
/** @file
 *
 * FortissimoMongoCappedLogger.
 *
 * Created by Matt Butcher on 2011-04-07.
 */

/**
 * Logger using a capped MongoDB collection.
 *
 * A capped collection is similar to a circular linked list. When the maximum number of entries
 * have been added, the collection cycles through again, overwriting values as it goes.
 *
 * Params:
 * - maxEntries: Maximum number of items in the collection (Default: 1,000).
 * - maxSizeInBytes: Amount of space (in bytes) to allocate for the collection (Default: 1M).
 * - mongoDatasourceName: Name of the datasource to fetch. See Conf::datasource().
 * - collectionName: The name of the collection to use in the datasource.
 *
 * @author Matt Butcher
 */
class FortissimoMongoCappedLogger extends FortissimoLogger {

  protected $maxEntries = 1000;
  protected $maxSize = 1048576;
  protected $dsName;
  protected $ds;
  protected $collectionName;

  // Access to $this->{params, facilities, name}

  public function init() {
    //  Cap defaults to 1,000.
    if (isset($this->params['maxEntries'])) {
      $this->maxEntries = (int)$this->params['maxEntries'];
    }
    
    // Defaults to 1M.
    if (isset($this->params['maxSize'])) {
      $this->maxSize = (int)$this->params['maxSizeInBytes'];
    }
    
    // Get the datasource name.
    if (empty($this->params['mongoDatasourceName'])) {
      throw new FortissimoInterruptException('No mongoDatasourceName was supplied to ' . $this->name);
    }
    $this->dsName = $this->params['mongoDatasourceName'];
    
    // Get the collection name.
    if (empty($this->params['collectionName'])) {
      throw new FortissimoInterruptException('No collectionName was set for ' . $this->name);
    }
    $this->collectionName = $this->params['collectionName'];
  }
  
  // Override this to do some initialization when the datasources are set.
  public function setDatsourceManager($manager) {
    parent::setDatasourceManager($manager);
    
    // Try to get the datasource:
    $this->ds = $this->datasourceManager->getDatasourceByName($this->dsName)->get();
    if (!($this->ds instanceof MongoDB)) {
      throw new FortissimoInterruptException('Expected a MongoDB for ' . $this->dsName);
    }
    
    $this->db->createCollection($this->collectionName, TRUE, $this->maxSize, $this->maxEntries);
  }

  public function log($msg, $category, $details) {
    $data = array(
      'ts' => $_SERVER['REQUEST_TIME'],
      'msg' => $msg,
      'cat' => $category,
      'dtls' => $details
    );
    $this->db->selectCollection($this->collectionName)->insert($data);
  }


  // Used for loggers that return buffered messages.
  // public function getMessages() { return array(); }
}
