<?php

namespace RavenTools\GridManager;

use JsonSerializable;
use OutOfBoundsException;
use StdClass;

class WorkItem implements JsonSerializable {

	private $state = null;

	public function __construct($state = null) {
		if(is_object($state)) {
			$this->state = $state;
		} else {
			$this->state = (object) [
				'params' => new StdClass,
				'results' => new StdClass
			];
		}
	}

	/**
	 * used to fetch parameters, immutable after object is created
	 */
	public function params($key = null) {
		if(is_null($key)) {
			// we are asking for all params
			return $this->state->params;
		} elseif(is_string($key) && isset($this->state->params->{$key})) {
			// we are asking for a specific result key
			return $this->state->params->{$key};
		}
	}

	/**
	 * used to store job results
	 */
	public function results($key = null, $value = null) {
		if(is_null($key)) {
			// we are asking for all results
			return $this->state->results;
		} elseif(is_string($key) && is_null($value) && isset($this->state->results->{$key})) {
			// we are asking for a specific result key
			return $this->state->results->{$key};
		} elseif(!empty($key) && is_string($key) && !is_null($value)) {
			// we are setting a result key to a value
			return $this->state->results->{$key} = $value;
		}
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
