<?php

namespace RavenTools\GridManager;

class Worker {

	protected $num_to_process = 1;
	protected $num_processed = 0;

	protected $dequeue_callback = null;
	protected $queue_message_callbacks = array();
	protected $queue_callback = null;
	protected $shutdown_callback = null;
	protected $shutdown_timeout = null;
	protected $process_exit_callback = null;

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

		if(array_key_exists("dequeue_callback",$params) && is_callable($params['dequeue_callback'])) {
			$this->setDequeueCallback($params['dequeue_callback']);
		}

		if(array_key_exists("queue_message_callback",$params)) {
			if(is_callable($params['queue_message_callback'])) {
				$this->addQueueMessageCallback($params['queue_message_callback']);
			} elseif(is_array($params['queue_message_callback'])) {
				foreach($params['queue_message_callback'] as $c) {
					if(is_callable($c)) {
						$this->addQueueMessageCallback($c);
					}
				}
			}
		}

		if(array_key_exists("queue_callback",$params) && is_callable($params['queue_callback'])) {
			$this->setQueueCallback($params['queue_callback']);
		}

		if(array_key_exists("shutdown_callback",$params) && is_callable($params['shutdown_callback'])) {
			$this->setShutdownCallback($params['shutdown_callback']);
		} else {
			$this->setShutdownCallback(function() { exit(111); });
		}

		if(array_key_exists("shutdown_timeout",$params)) {
			$this->setShutdownTimeout($params['shutdown_timeout']);
		}

		if(array_key_exists("process_exit_callback",$params) && is_callable($params['process_exit_callback'])) {
			$this->setProcessExitCallback($params['process_exit_callback']);
		} else {
			$this->setProcessExitCallback(function() { exit(); });
		}

		if(array_key_exists("num_to_process",$params)) {
			$this->setNumToProcess($params['num_to_process']);
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
	public function addQueueMessageCallback($callback) {
		if(is_callable($callback)) {
			$this->queue_message_callbacks[] = $callback;
		} else {
			throw new \Exception("callable argument required");
		}
	}

	public function getQueueMessageCallbacks() {
		return $this->queue_message_callbacks;
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

	public function getShutdownCallback() {
		return $this->shutdown_callback;
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

		while($this->running) {

			$data = call_user_func($this->dequeue_callback);

			$output_item_buffer = array();

			if($data !== false) {
				$this->last_item_ts = time();

				foreach($data as $queue_message) {

					foreach($this->queue_message_callbacks as $cb) {
						$queue_message = call_user_func($cb,$queue_message);
					}

					$output_item_buffer[] = $queue_message;
				}

				if(call_user_func($this->queue_callback,$output_item_buffer) === true) {
					$response['success']++;
					$response['items'] += count($output_item_buffer);
				} else {
					$response['failure']++;
				}

				if(++$this->num_processed >= $this->num_to_process) {
					$this->running = false;
				} elseif($response['failure'] > $response['success']) {
					sleep(1);
				} elseif($response['failure'] == 0 && $response['success'] == 0) {
					sleep(1);
				}
			} else {

				if(!is_null($this->shutdown_timeout) && $this->last_item_ts < (time() - $this->shutdown_timeout)) {
					$this->running = false;
				}

				usleep(500);
			}

			if($this->shouldShutdown()) {
				Log::info("shutdown requested");

				if(is_callable($this->shutdown_callback)) {
					Log::info("calling shutdown function");
					call_user_func($this->shutdown_callback);
				}
			} elseif($this->shouldRestart()) {
				Log::info("restart requested");
				sleep(2); // prevent flapping
				$this->running = false;
			}
		}

		call_user_func($this->process_exit_callback);

		return $response;
	}

	private function shouldShutdown() {
		return $this->shouldExit("shutdown");
	}

	private function shouldRestart() {
		return $this->shouldExit("restart");
	}

	/**
	 * @param $type restart or shutdown
	 */
	private function shouldExit($type) {
		$haltfile = "/tmp/halt_workers";
		$procfile = sprintf('/proc/%s',getmypid());
		clearstatcache($procfile);
		$start_time = stat($procfile)[9];

		if(!is_file($haltfile)) {
			return false;
		}

		clearstatcache($haltfile);
		clearstatcache($procfile);
		$halt_time = stat($haltfile)[9];
		if($halt_time < $start_time) {
			return false;
		}

		if(trim(file_get_contents($haltfile)) !== $type) {
			return false;
		}

		return true;
	}
}
