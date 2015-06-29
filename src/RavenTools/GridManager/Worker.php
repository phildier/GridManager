<?php

namespace RavenTools\GridManager;

class Worker {

	protected $dequeue_callback = null;
	protected $work_item_callbacks = array();
	protected $queue_callback = null;

	public function __construct(Array $params) {
		$this->validateAndSetParams($params);
	}

	/**
	 * sets object parameters contained within the $params array
	 */
	protected function validateAndSetParams(Array $params) {

		if(array_key_exists("dequeue_callback",$params)) {
			$this->setDequeueCallback($params['dequeue_callback']);
		}

		if(array_key_exists("work_item_callback",$params)) {
			if(is_callable($params['work_item_callback'])) {
				$this->addWorkItemCallback($params['work_item_callback']);
			} elseif(is_array($params['work_item_callback'])) {
				foreach($params['work_item_callback'] as $c) {
					$this->addWorkItemCallback($c);
				}
			}
		}

		if(array_key_exists("queue_callback",$params)) {
			$this->setQueueCallback($params['queue_callback']);
		}

	}

	/**
	 * sets a callback to dequeue an output item from a queue for post processing.
	 * expected to block for a defined period and return an output item if available, or
	 * return false after a timeout.
	 */
	public function setDequeueCallback($callback) {
		if(is_callable($callback)) {
			$this->dequeue_callback = $callback;
		} else {
			throw new \Exception("callable argument required");
		}
	}

	/**
	 * add an output item callback to the chain of post processing callbacks.
	 * output items are passed through this chain in order, with the output of the previous callback
	 * sent as the input to the next.  the resulting output items are batched and sent to the
	 * write data callback.
	 */
	public function addWorkItemCallback($callback) {
		if(is_callable($callback)) {
			$this->work_item_callbacks[] = $callback;
		} else {
			throw new \Exception("callable argument required");
		}
	}

	/**
	 * sets a callback to queue output items to the output queue
	 */
	public function setQueueCallback($callback) {
		if(is_callable($callback)) {
			$this->queue_callback = $callback;
		} else {
			throw new \Exception("callable argument required");
		}
	}

	/**
	 * runs the output job
	 * - dequeues one or more output items
	 * - runs output item through output item callback chain
	 * - batches output items and runs them through write data callback
	 * returns array with count of successes and failures
	 */
	public function run() {

		$response = array(
				"success" => 0,
				"failure" => 0,
				"items" => 0
				);

		$cb = $this->dequeue_callback;
		$data = $cb();

		$output_item_buffer = array();

		if($data !== false) {
			foreach($data as $work_item) {

				foreach($this->work_item_callbacks as $cb) {
					$work_item = $cb($work_item);
				}

				$output_item_buffer[] = $work_item;
			}

			$cb = $this->queue_callback;
			if($cb($output_item_buffer) === true) {
				$response['success'] = 1;
				$response['items'] += count($output_item_buffer);
			} else {
				$response['failure'] = 1;
			}
		}

		return $response;
	}
}
