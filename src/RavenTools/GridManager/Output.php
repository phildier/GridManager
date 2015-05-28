<?php

namespace RavenTools\GridManager;

class Output {

	protected $dequeue_callback = null;
	protected $output_item_callbacks = array();
	protected $write_data_callback = null;
	protected $dequeue_batch_size = 10;
	protected $write_data_batch_size = 100;

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

		if(array_key_exists("output_item_callback",$params)) {
			if(is_array($params['output_item_callback'])) {
				foreach($params['output_item_callback'] as $c) {
					$this->addOutputItemCallback($c);
				}
			} else {
				$this->addOutputItemCallback($params['output_item_callback']);
			}
		}

		if(array_key_exists("write_data_callback",$params)) {
			$this->setWriteDataCallback($params['write_data_callback']);
		}

		if(array_key_exists("dequeue_batch_size",$params)) {
			$this->dequeue_batch_size = $params['dequeue_batch_size'];
		}

		if(array_key_exists("write_data_batch_size",$params)) {
			$this->write_data_batch_size = $params['write_data_batch_size'];
		}

	}

	/**
	 * sets a callback to dequeue an output item from a queue for post processing.
	 * expected to block for a defined period and return an output item if available, or
	 * return false after a timeout.
	 */
	public function setDequeueCallback(Callable $callback) {
		$this->dequeue_callback = $callback;
	}

	/**
	 * add an output item callback to the chain of post processing callbacks.
	 * output items are passed through this chain in order, with the output of the previous callback
	 * sent as the input to the next.  the resulting output items are batched and sent to the
	 * write data callback.
	 */
	public function addOutputItemCallback(Callable $callback) {
		$this->output_item_callbacks[] = $callback;
	}

	/**
	 * sets a callback that is responsible for writing batches of output items to
	 * persistent storage.  an array of output items are passed in and a boolean response
	 * is expected.
	 */
	public function setWriteDataCallback(Callable $callback) {
		$this->write_data_callback = $callback;
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
		$data = $cb($this->dequeue_batch_size);

		$write_data_buffer = array();

		foreach($data as $d) {

			$output_item = $d;

			foreach($this->output_item_callbacks as $cb) {
				$output_item = $cb($output_item);
			}

			if(count($write_data_buffer) < $this->write_data_batch_size) {
				$write_data_buffer[] = $output_item;
			} else {

				$this->writeData($write_data_buffer,$response);

				// TODO sane failed write_data retries.
				// for now clear write buffer on both success and failure
				$write_data_buffer = array();
			}
		}

		// write any remaining in the buffer
		if(count($write_data_buffer) > 0) {
			$this->writeData($write_data_buffer,$response);
		}

		return $response;
	}

	/**
	 * call write data callback and update response counters
	 */
	protected function writeData(Array $buffer,&$response) {

		$cb = $this->write_data_callback;
		if($cb($buffer) === true) {
			$response['success']++;
			$response['items'] += count($buffer);
			return true;
		} else {
			$response['failure']++;
			return false;
		}
	}
}

