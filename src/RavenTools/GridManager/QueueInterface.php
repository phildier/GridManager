<?php

namespace RavenTools\GridManager;

interface QueueInterface {

	/**
	 * adds a message to the queue
	 */
	public function send($message);

	/**
	 * dequeues $num messages
	 */
	public function receive($num = 1);

	/**
	 * delete message from queue
	 */
	public function delete($handle);

	/**
	 * return the current queue length
	 */
	public function length();

	/**
	 * creates the queue
	 */
	public function create();
}
