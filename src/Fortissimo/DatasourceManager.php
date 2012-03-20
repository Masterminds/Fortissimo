<?php
/**
 * @file
 *
 * The datasource manager.
 */
namespace Fortissimo;

/**
 * Manages data sources.
 *
 * Fortissimo provides facilities for declaring multiple data sources. A
 * datasource is some readable or writable backend like a database.
 *
 * This class manages multiple data sources, providing the execution context
 * with a simple way of retrieving datasources by name.
 */
class DatasourceManager {

  protected $datasources = NULL;
  protected $initMap = array();


  /**
   * Build a new datasource manager.
   *
   * @param array $config
   *  The configuration for this manager as an associative array of
   *  names=>instances.
   */
  public function __construct($config) {
    $this->datasources = &$config;
  }

  public function setCacheManager(\Fortissimo\CacheManager $manager) {
    foreach ($this->datasources as $name => $obj) $obj->setCacheManager($manager);
  }

  public function setLogManager(\Fortissimo\LoggerManager $manager) {
    foreach ($this->datasources as $name => $obj) $obj->setLogManager($manager);
  }


  /**
   * Get a datasource by its string name.
   *
   * @param string $name
   *  The name of the datasource to get.
   * @return FortissimoDatasource
   *  The requested source, or NULL if no such source exists.
   */
  public function getDatasourceByName($name) {
    return $this->datasources[$name];
  }

  /**
   * Scan the datasources and return the first one marked default.
   *
   * Note that this does not make sure that datasources have been initialized.
   * @return FortissimoDatasource
   *  An initialized FortissimoDatasource, or NULL if no default is found.
   */
  protected function getDefaultDatasource() {
    foreach ($this->datasources as $k => $o) if ($o->isDefault()) return $o;
  }

  /**
   * Get a datasource.
   *
   * If a name is given, retrieve the named datasource. Otherwise, return
   * the default. If no suitable datasource is found, return NULL.
   *
   * @param string $name
   *  The name of the datasource to return.
   * @return FortissimoDatasource
   *  The datasource.
   */
  public function datasource($name = NULL) {
    $ds = NULL;
    if (empty($name)) {
      $ds = $this->getDefaultDatasource();
    }
    else {
      $ds = $this->getDatasourceByName($name);
    }

    // We initialize lazily so that datasources do not
    // have resources allocated until necessary.
    if (!empty($ds) && !isset($this->initMap[$name])) {
      $ds->init();
      $this->initMap[$name] = TRUE;
    }
    return $ds;
  }

  /**
   * Initialize all datasources managed by this manager instance.
   *
   * By default, datasource initialization is delayed as long as possible so that
   * resources are not allocated needlessly. On some occasions, you may want to
   * initialize all of the datasources at once. Use this function to do so.
   *
   * Keep in mind that if there are a lot of datasources, this may consume many
   * system resources.
   */
  public function initializeAllDatasources() {
    foreach ($this->datasources as $name => $ds) {
      if (!isset($this->initMap[$name])) {
        $ds->init();
        $this->initMap[$name] = TRUE;
      }
    }
  }

  /**
   * Get all datasources.
   *
   * This does not initialize resources automatically. If you need all datasources
   * to be initialized first, call initializeAllDatasources() before calling this.
   *
   * @return array
   *  Returns an associative array of datasource name=>object pairs.
   */
  public function datasources() {
    return $this->datasources;
  }
}
