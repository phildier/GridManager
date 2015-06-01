<?php

namespace RavenTools\GridManager;

class SQSQueue implements \RavenTools\GridManager\QueueInterface {

	protected $sqs_client = null;
	protected $queue_name = null;
	protected $queue_url = null;
	protected $timeout = null;

	public function __construct(Array $params) {
		$this->validateAndSetParams($params);
	}

	/**
	 * sets object properties contained within the $params array
	 */
	protected function validateAndSetParams(Array $params) {

		if(array_key_exists("sqs_client",$params)) {
			$this->sqs_client = $params['sqs_client'];
		} else {
			throw new Exception("sqs_client required");
		}

		if(array_key_exists("queue_name",$params)) {
			$this->queue_name = $params['queue_name'];
			$this->queue_url = $this->sqs_client->GetQueueUrl(array(
									"QueueName"=>$this->queue_name
								))->get("QueueUrl");
		} else {
			throw new Exception("queue_name required");
		}

		if(array_key_exists("timeout",$params)) {
			$this->timeout = $params['timeout'];
		}
	}

	/**
	 * pushes an object to the queue. 
	 * returns response on success, false on failure
	 */
	public function send($message) {
		try {
			$response = $this->sqs_client->sendMessage(array(
							"QueueUrl" => $this->queue_url,
							"MessageBody" => $this->encode($message)
						));
		} catch(Exception $e) {
			error_log("problem sending sqs message ".$e->getMessage());
			return false;
		}
		return $response;
	}

	/**
	 * poll and receive one or more messages from the queue
	 * returns an array of messages or false on timeout or failure
	 */
	public function receive($num = 1) {
		try {
			$response = $this->sqs_client->receiveMessage(array(
							"QueueUrl" => $this->queue_url,
							"WaitTimeSeconds" => $this->timeout
						));
		} catch(Exception $e) {
			error_log("exception retrieving sqs message ".$e->getMessage());
			return false;
		}
		$messages = $response->get("Messages");
		if(is_null($messages)) {
			return false;
		}
		return (object)array(
					"handle"=>$messages[0]["ReceiptHandle"],
					"body"=>$this->decode($messages[0]["Body"])
				);
	}

	/**
	 * delete a message from the queue using its receipt handle
	 * returns response or false on failure
	 */
	public function delete($handle) {
		try {
			$response = $this->sqs_client->deleteMessage(array(
							"QueueUrl" => $this->queue_url,
							"ReceiptHandle" => $handle
						));
		} catch(Exception $e) {
			error_log("exception deleting sqs message ".$e->getMessage());
			return false;
		}
		return $response;
	}

	/**
	 * returns approximate length of queue
	 */
	public function length() {
		try {
			$response = $this->sqs_client->getQueueAttributes(array(
							"QueueUrl" => $this->queue_url,
							"AttributeNames" => array("ApproximateNumberOfMessages")
						));
		} catch(Exception $e) {
			error_log("exception getting queue size ".$e->getMessage());
			return false;
		}
		return $response->getPath("Attributes/ApproximateNumberOfMessages");
	}

	/**
	 * encode message
	 */
	protected function encode($message) {
		return json_encode($message);
	}

	/**
	 * decode message
	 */
	protected function decode($message) {
		return json_decode($message);
	}
}
