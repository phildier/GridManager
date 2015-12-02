<?php

namespace RavenTools\GridManager;

abstract class Config extends Singleton {

	private static $instance = null;

	abstract public static function bootstrap() {
	}

	final public static function getInstance() {
		if(!is_object(self::$instance)) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function get($key) {
		return $this->config->{$key};
	}

	protected function __construct() {
		if(file_exists("/vagrant")) {
			$this->config = $this->loadConfig("config-vagrant.json");
		} else {
			$this->config = $this->loadConfig("config.json");
		}
	}

	protected function loadConfig($path) {

		if(!file_exists($path)) {
			throw new \Exception("config $path not found");
		}

		if(($json = file_get_contents($path)) === false) {
			throw new \Exception("could not read $path");
		}

		if(($decoded = json_decode($json)) === null) {
			throw new \Exception("could not decode $path");
		}

		return $decoded;
	}
}
