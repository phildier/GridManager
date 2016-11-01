<?php

namespace RavenTools\GridManager;

class SQSQueue implements \RavenTools\GridManager\QueueInterface {

	protected $sqs_client = null;
	protected $queue_name = null;
	protected $queue_url = null;
	protected $timeout = 1;
	protected $visibility_timeout = 300;

	public function __construct(Array $params) {

		if(array_key_exists("sqs_client",$params)) {
			$this->sqs_client = $params['sqs_client'];
		} else {
			throw new \Exception("sqs_client required");
		}

		if(array_key_exists("queue_name",$params)) {
			$this->setQueueName($params['queue_name']);
		} else {
			throw new \Exception("queue_name required");
		}

		if(array_key_exists("timeout",$params)) {
			$this->timeout = $params['timeout'];
		}

		if(array_key_exists("visibility_timeout",$params)) {
			$this->visibility_timeout = $params['visibility_timeout'];
		}
	}

	public function setQueueName($name) {
		$this->queue_name = $name;
	}

	public function getQueueName() {
		return $this->queue_name;
	}

	public function setQueueUrl($url) {
		$this->queue_url = $url;
	}

	public function getQueueUrl() {

		if(is_null($this->getQueueName())) {
			throw new \Exception("queue_name is required");
		}

		if(is_null($this->queue_url)) {

			$url = $this->sqs_client->GetQueueUrl([
				"QueueName" => $this->getQueueName()
			])->get("QueueUrl");

			$this->setQueueUrl($url);
		}

		return $this->queue_url;
	}

	public function setSQSClient($client) {
		$this->sqs_client = $client;
	}

	public function getSQSClient() {
		return $this->sqs_client;
	}

	/**
	 * pushes an object to the queue. 
	 * returns response on success, false on failure
	 */
	public function send(WorkItem $message) {
		try {
			$response = $this->sqs_client->sendMessage(array(
							"QueueUrl" => $this->getQueueUrl(),
							"MessageBody" => $this->encode($message)
						));
		} catch(\Exception $e) {
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
							"QueueUrl" => $this->getQueueUrl(),
							"WaitTimeSeconds" => $this->timeout,
							"MaxNumberOfMessages" => $num
						));
		} catch(\Exception $e) {
			return false;
		}
		$messages = $response->get("Messages");
		if(is_null($messages)) {
			return false;
		}

		$ret = array();
		foreach($messages as $m) {
			$ret[] = new QueueMessage([
				'queue' => $this,
				'handle' => $m['ReceiptHandle'],
				// initialize a WorkItem from the message body
				'work_item' => new WorkItem($this->decode($m['Body']))
			]);
		}
		return $ret;
	}

	/**
	 * delete a message from the queue using its receipt handle
	 * returns response or false on failure
	 */
	public function delete($handle) {
		try {
			$response = $this->sqs_client->deleteMessage(array(
							"QueueUrl" => $this->getQueueUrl(),
							"ReceiptHandle" => $handle
						));
		} catch(\Exception $e) {
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
							"QueueUrl" => $this->getQueueUrl(),
							"AttributeNames" => array("ApproximateNumberOfMessages")
						));
		} catch(\Exception $e) {
			return false;
		}
		return $response->getPath("Attributes/ApproximateNumberOfMessages");
	}

	/**
	 * creates the queue
	 */
	public function create() {

		try {
			$this->sqs_client->createQueue([
				'QueueName' => $this->getQueueName(),
				'Attributes' => [
					'VisibilityTimeout' => $this->visibility_timeout
				]
			]);
		} catch(\Exception $e) {
			return false;
		}

		return true;
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
