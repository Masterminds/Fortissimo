<?php
/**
 * @file
 *
 * The base class for all datasource implementations.
 */
namespace Fortissimo\Datasource;
/**
 * A datasource.
 *
 * Fortissimo provides a very general (and loose) abstraction for datasources.
 * The idea is to make it possible for all datasources -- from files to RDBs to
 * NoSQL databases to LDAPS -- to be defined in a central place (along with
 * requests) so that they can easily be configured and also leveraged by the
 * command configuration.
 *
 * The generality of this class makes it less than ideal for doing strict checks
 * on capabilities, but, then, that's what inheritance if for, isn't it.
 *
 * Each data source type should extend this basic class. This base class contains
 * the absolute minimal amount of information that Fortissimo needs in order to
 * load the datasources and instruct them to initialize themselves.
 *
 * From there, it's up to implementors to build useful datasource wrappers that
 * can be leveraged from within commands.
 */
abstract class Base {
  /**
   * The parameters for this data source
   */
  protected $params = NULL;
  protected $default = FALSE;
  protected $name = NULL;
  protected $logManager = NULL;
  protected $cacheManager = NULL;

  /**
   * Construct a new datasource.
   *
   * @param array $params
   *  An associative array of params from the configuration.
   * @param string $name
   *  The name of the facility.
   */
  public function __construct($params = array(), $name = 'unknown_datasource') {
    $this->params = $params;
    $this->name = $name;
    $this->default = isset($params['isDefault']) && filter_var($params['isDefault'], FILTER_VALIDATE_BOOLEAN);
  }

  public function setCacheManager(\Fortissimo\Cache\Manager $manager) {
    $this->cacheManager = $manager;
  }

  public function setLogManager(\Fortissimo\Logger\Manager $manager) {
    $this->logManager = $manager;
  }

  /**
   * Get this datasource's name, as set in the configuration.
   *
   * @return string
   *  The name of this datasource.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Determine whether this is the default datasource.
   *
   * Note that this may be called *before* init().
   *
   * @return boolean
   *  Returns TRUE if this is the default. Typically the default status is
   *  assigned in the commands.xml file.
   */
  public function isDefault() {
    return $this->default;
  }

  /**
   * This is called once before the datasource is first used.
   *
   * While there is no guarantee that this will be called only when necessary, it
   * is lazier than the constructor, so initialization of connections may be better
   * left to this function than to overridden constructors.
   */
  public abstract function init();

  /**
   * Retrieve the underlying datasource object.
   *
   * Ideally, this returns the underlying data source. In some circumstances,
   * it may return NULL.
   *
   * @return mixed
   *  The underlying datasource. Example: a PDO object or a Mongo object.
   */
  public abstract function get();
}
