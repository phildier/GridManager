<?php

namespace RavenTools\GridManager;

class Input {

	protected $load_data_callback = null;
	protected $queue_message_callbacks = array();
	protected $batch_size = 1000;

	public function __construct(Array $params) {
		$this->validateAndSetParams($params);
	}

	/**
	 * sets object parameters contained within the $params array
	 */
	protected function validateAndSetParams(Array $params) {

		if(array_key_exists("load_data_callback",$params)) {
			$this->setLoadDataCallback($params['load_data_callback']);
		}

		if(array_key_exists("queue_message_callback",$params)) {
			if(is_callable($params['queue_message_callback'])) {
				$this->addQueueMessageCallback($params['queue_message_callback']);
			} elseif(is_array($params['queue_message_callback'])) {
				foreach($params['queue_message_callback'] as $c) {
					$this->addQueueMessageCallback($c);
				}
			}
		}

		if(array_key_exists("queue_callback",$params)) {
			$this->setQueueCallback($params['queue_callback']);
		}

		if(array_key_exists("batch_size",$params)) {
			$this->batch_size = $params['batch_size'];
		}

	}

	/**
	 * sets a callback to load job data.  
	 * the callback's expected to return an Iterator
	 */
	public function setLoadDataCallback($callback) {
		if(is_callable($callback)) {
			$this->load_data_callback = $callback;
		} else {
			throw new \Exception("callable argument required");
		}
	}

	/**
	 * sets one or more work item callbacks, which will be chained together in order
	 * the callback is expected to return an array or object, which will be passed into the next callback
	 * in the chain. the return from the final callback is pushed to the queue
	 */
	public function addQueueMessageCallback($callback) {
		if(is_callable($callback)) {
			$this->queue_message_callbacks[] = $callback;
		} else {
			throw new \Exception("callable argument required");
		}
	}

	/**
	 * sets a queue callback which is responsible for pushing a message to a queue.
	 * the callback is expected to return true on success, false on failure
	 */
	public function setQueueCallback($callback) {
		if(is_callable($callback)) {
			$this->queue_callback = $callback;
		} else {
			throw new \Exception("callable argument required");
		}
	}

	/**
	 * runs the input job.
	 * - loads the data set
	 * - applies work item callback in order
	 * - queues work item
	 * returns array with count of successes and failures
	 */
	public function run() {

		$response = array(
				"success" => 0,
				"failure" => 0
				);

		$cb = $this->load_data_callback;
		$data = call_user_func($cb,$this->batch_size);

		foreach($data as $d) {
			$queue_message = $d;
			foreach($this->queue_message_callbacks as $cb) {
				$queue_message = call_user_func($cb,$queue_message);
			}

			if($queue_message === false) {
				$response['skipped']++;
				continue;
			}

			$cb = $this->queue_callback;
			if(call_user_func($cb,$queue_message) === true) {
				$response['success']++;
			} else {
				$response['failure']++;
			}
		}

		return $response;
	}
}
