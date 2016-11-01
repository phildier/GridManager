<?php

namespace RavenTools\GridManager;

use JsonSerializable;
use OutOfBoundsException;

class WorkItem implements JsonSerializable {

	private $results = null;

	public function __construct($results = null) {
		$this->results = $results;
	}

	public function results($key = null, $value = null) {
		if(is_null($key)) {
			// we are asking for all results
			return $this->results;
		} elseif(is_string($key) && is_null($value) && array_key_exists($key,$this->results)) {
			// we are asking for a specific result key
			return $this->results[$key];
		} elseif(!empty($key) && is_string($key) && !is_null($value)) {
			// we are setting a result key to a value
			return $this->results[$key] = $value;
		}
		throw new InvalidArgumentException('tried to set a result value without a key');
	}

	public function __set($key,$value) {
		throw new OutOfBoundsException('cannot set properties directly on a WorkItem');
	}

	public function __get($key) {
		throw new OutOfBoundsException('cannot get properties directly from a WorkItem');
	}

	public function to_array() {
		return $this->results;
	}

	public function jsonSerialize() {
		return $this->to_array();
	}

	public function __toString() {
		return print_r($this->to_array(),true);
	}
}
