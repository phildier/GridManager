<?php

namespace RavenTools\GridManager;

use RuntimeException;

class QueueMessage {

	private $queue = null;
	private $handle = null;
	private $body = null;

	public function __construct($params = []) {
		if(array_key_exists('queue',$params) && !is_object($params['queue'])) {
			$this->setQueue($params['queue']);
		}

		if(array_key_exists('handle',$params) && !empty($params['handle'])) {
			$this->setHandle($params['handle']);
		}

		if(array_key_exists('body',$params) && !empty($params['body'])) {
			$this->setBody($params['body']);
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

	public function setBody($body) {
		$this->body = $body;
	}

	public function getBody() {
		return $this->body;
	}

	public function delete() {
		return $this->getQueue()->delete($this->getHandle());
	}
}
