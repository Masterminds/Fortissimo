<?php
/**
 * @file
 * The default registry for Fortissimo.
 *
 * DO NOT MODIFY. Your application should declare its own registry.
 */

use \Fortissimo\Registry;

$register = new Registry('Fortissimo');

$register->route('@test');
