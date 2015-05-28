<?php

class QueueInterface {

	/**
	 * adds a message to the queue
	 */
	public function queue($message);

	/**
	 * dequeues $num messages
	 */
	public function dequeue($num = 1) ;

	/**
	 * return the current queue length
	 */
	public function length();
}
