<?php

namespace RavenTools\GridManager;

class S3Artifact {

	private $s3_client = null;
	private $bucket = null;

	public function __construct($params) {

		$this->s3_client = $params['client'];
		$this->bucket = $params['bucket'];
	}

	public function put($data) {

		$key = $this->genKey();

		try {
			$this->s3_client->putObject(array(
				"Bucket" => $this->bucket,
				"Body" => $data,
				"Key" => $key
			));
		} catch(\Exception $e) {
			return false;
		}

		return $key;
	}

	public function get($key) {

		try {
			$response = $this->s3_client->getObject(array(
				"Bucket" => $this->bucket,
				"Key" => $key
			));
		} catch(\Exception $e) {
			return false;
		}

		return (string) $response['Body'];
	}

	private function genKey() {
		return sprintf("%s_%s",uniqid(),mt_rand());
	}
}
