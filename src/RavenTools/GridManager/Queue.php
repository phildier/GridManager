<?php

namespace RavenTools\GridManager;

class SQSQueue implements QueueInterface {

	protected $sqs_client = null;

	public function __construct(Array $params) {
		$this->validateAndSetParams($params);
	}

    /**
     * sets object parameters contained within the $params array
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
	}

	public function queue($message) {
	}

	public function dequeue($num = 1) {
	}

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

	protected function encode($message) {
		return json_encode($message);
	}

	protected function decode($message) {
		return json_decode($message);
	}
}
