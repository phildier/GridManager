<?php

namespace RavenTools\GridManager;

use RuntimeException;

class QueueMessage {

	private $queue = null;
	private $handle = null;
	private $work_item = null;

	public function __construct($params = []) {
		if(array_key_exists('queue',$params) && is_object($params['queue'])) {
			$this->setQueue($params['queue']);
		}

		if(array_key_exists('handle',$params) && !empty($params['handle'])) {
			$this->setHandle($params['handle']);
		}

		if(array_key_exists('work_item',$params) && !empty($params['work_item'])) {
			$this->setWorkItem($params['work_item']);
		}
	}

	public function setQueue($queue) {
		$this->queue = $queue;
	}

	public function getQueue() {
		if(is_null($this->queue)) {
			throw new RuntimeException('queue was not defined');
		}

		return $this->queue;
	}

	public function setHandle($handle) {
		$this->handle = $handle;
	}

	public function getHandle() {
		return $this->handle;
	}

	public function setWorkItem($work_item) {
		$this->work_item = $work_item;
	}

	public function getWorkItem() {
		return $this->work_item;
	}

	public function delete() {
		return $this->getQueue()->delete($this->getHandle());
	}

	public function to_array() {
		return [
			'handle' => $this->getHandle(),
			'work_item' => (string) $this->getWorkItem()
		];
	}

	public function __toString() {
		return print_r((object) $this->to_array(), true);
	}
}
