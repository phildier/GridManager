<?php

namespace RavenTools\GridManager;

abstract class Config extends Singleton {

	abstract public static function bootstrap();

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
