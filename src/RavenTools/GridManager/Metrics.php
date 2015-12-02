<?php

namespace RavenTools\GridManager;

class Metrics {

	private $prefix = null;

	public function __construct($prefix) {
		$this->prefix = $prefix;
	}

	private function key($suffix) {
		return sprintf("%s.%s",$this->prefix,$suffix);
	}

	public function increment($suffix,$sample=1,$value=1) {
		\StatsD::increment($this->key($suffix),$sample,$value);
	}

	public function gauge($suffix,$value) {
		\StatsD::gauge($this->key($suffix),$value);
	}
}
