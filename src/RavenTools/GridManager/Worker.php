<?php

namespace RavenTools\GridManager;

class Worker {

	protected $num_to_process = 1;

	protected $dequeue_callback = null;
	protected $work_item_callbacks = array();
	protected $queue_callback = null;
	protected $shutdown_callback = null;
	protected $shutdown_timeout = "5 minutes";
	protected $process_timeout = "5 minutes";

	protected $start_ts = null;
	protected $last_item_ts = null;

	protected $running = null;

	public function __construct(Array $params) {

		$this->validateAndSetParams($params);

		$this->start_ts = time();
		$this->last_item_ts = time();
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

		if(array_key_exists("shutdown_callback",$params)) {
			$this->setShutdownCallback($params['shutdown_callback']);
		} else {
			$this->setShutdownCallback(function() { });
		}

		if(array_key_exists("shutdown_timeout",$params)) {
			$this->setShutdownTimeout($params['shutdown_timeout']);
		}

		if(array_key_exists("process_exit_callback",$params)) {
			$this->setProcessExitCallback($params['process_exit_callback']);
		} else {
			$this->setProcessExitCallback(function() { });
		}

		if(array_key_exists("process_timeout",$params)) {
			$this->setProcessTimeout($params['process_timeout']);
		}

		if(array_key_exists("num_to_process",$params)) {
			$this->num_to_process = $this->setNumToProcess($params['num_to_process']);
		}
	}

	/**
	 * sets maximum number of jobs to process before exiting
	 */
	public function setNumToProcess($num) {
		$this->num_to_process = $num;
	}

	/**
	 * sets the shutdown timeout (process stops)
	 */
	public function setShutdownTimeout($shutdown_timeout) {
		$this->shutdown_timeout = strtotime($shutdown_timeout) - time();
	}

	/**
	 * sets the process timeout (process stops and restarts)
	 */
	public function setProcessTimeout($process_timeout) {
		$this->process_timeout = strtotime($process_timeout) - time();
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
	 * sets a callback to run when the worker has been idle too long
	 */
	public function setShutdownCallback($callback) {
		if(is_callable($callback)) {
			$this->shutdown_callback = $callback;
		} else {
			throw new \Exception("callable argument required");
		}
	}

	/**
	 * sets a callback to run when the worker has processed $this->num_to_process results
	 */
	public function setProcessExitCallback($callback) {
		if(is_callable($callback)) {
			$this->process_exit_callback = $callback;
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

		$this->running = true;
		$processed = 0;

		while($this->running) {

			$data = call_user_func($this->dequeue_callback);

			$output_item_buffer = array();

			if($data !== false) {
				$this->last_item_ts = time();

				foreach($data as $work_item) {

					foreach($this->work_item_callbacks as $cb) {
						$work_item = call_user_func($cb,$work_item);
					}

					$output_item_buffer[] = $work_item;
				}

				if(call_user_func($this->queue_callback,$output_item_buffer) === true) {
					$response['success']++;
					$response['items'] += count($output_item_buffer);
				} else {
					$response['failure']++;
				}

				if(++$processed >= $this->num_to_process) {
					$this->running = false;
					call_user_func($this->process_exit_callback);
				} elseif($response['failure'] > $response['success']) {
					sleep(1);
				} elseif($response['failure'] == 0 && $response['success'] == 0) {
					sleep(1);
				}
			} else {

				if($this->last_item_ts < (time() - $this->process_timeout)) {
					$this->running = false;
					call_user_func($this->process_exit_callback);
				}

				if($this->last_item_ts < (time() - $this->shutdown_timeout)) {
					$this->running = false;
					call_user_func($this->shutdown_callback);
				}

				usleep(100);
			}
		}

		return $response;
	}
}
