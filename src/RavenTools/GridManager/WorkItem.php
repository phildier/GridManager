<?php

namespace RavenTools\GridManager;

use JsonSerializable;
use OutOfBoundsException;
use StdClass;

class WorkItem implements JsonSerializable {

	private $state = [
		'params' => [],
		'results' => []
	];

	public function __construct($state = null) {
		if(is_object($state)) {
			// ensure state is an array
			$this->state = get_object_vars($state);
		} elseif(is_array($state)) {
			$this->state = $state;
		}

		foreach(['params','results'] as $type) {
			// ensure state properties are arrays
			if(array_key_exists($type,$this->state) && is_object($this->state[$type])) {
				$this->state[$type] = get_object_vars($this->state[$type]);
			} elseif(!array_key_exists($type,$this->state)) {
				$this->state[$type] = [];
			}
		}
	}

	/**
	 * used to fetch parameters, immutable after object is created
	 */
	public function params($key = null) {
		if(is_null($key)) {
			// we are asking for all params
			return $this->state['params'];
		} elseif(is_string($key) && isset($this->state['params'][$key])) {
			// we are asking for a specific result key
			return $this->state['params'][$key];
		}
	}

	/**
	 * used to store job results
	 */
	public function results($key = null, $value = null) {
		if(is_null($key)) {
			// we are asking for all results
			return $this->state['results'];
		} elseif(is_string($key) && is_null($value) && array_key_exists($key,$this->state['results'])) {
			// we are asking for a specific result key
			return $this->state['results'][$key];
		} elseif(!empty($key) && is_string($key) && !is_null($value)) {
			// we are setting a result key to a value
			return $this->state['results'][$key] = $value;
		}
	}

	public function __set($key,$value) {
		throw new OutOfBoundsException('cannot set properties directly on a WorkItem');
	}

	public function __get($key) {
		throw new OutOfBoundsException('cannot get properties directly from a WorkItem');
	}

	public function to_array() {
		return $this->state;
	}

	public function jsonSerialize() {
		return $this->to_array();
	}

	public function __toString() {
		return print_r($this->to_array(),true);
	}
}
