<?php

namespace RavenTools\GridManager;

abstract class Singleton {

	final public static function getInstance() {

		static $instance;

		if(is_null($instance)) {
			$instance = new static();
		}

		return $instance;
	}

	// enforce singleton-ness
	final private function __clone() { }
	final private function __wakeup() { }
	protected function __construct() { }
}
