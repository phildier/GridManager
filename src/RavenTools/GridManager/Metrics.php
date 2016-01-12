<?php

namespace RavenTools\GridManager;

use \Domnikl\Statsd\Client as StatsD;
use \Domnikl\Statsd\Connection\UdpSocket;

class Metrics {

	private $client = null;

	public function __construct($params) {

		if(array_key_exists('prefix',$params) && !empty($params['prefix'])) {
			$prefix = $params['prefix'];
		} else {
			throw new \Exception("prefix parameter required");
		}

		$host = "127.0.0.1";
		if(array_key_exists('host',$params) && !empty($params['host'])) {
			$host = $params['host'];
		}

		$port = 8125;
		if(array_key_exists('port',$params) && !empty($params['port'])) {
			$port = (int)$params['port'];
		}

		if(array_key_exists('client',$params) && !empty($params['client'])) {
			$this->client = $params['client'];
		} else {
			$this->client = new StatsD(
				new UdpSocket($host,$port),
				$prefix
			);
		}
	}

	public function increment($suffix,$sample=1,$value=1) {
		$this->client->count($suffix,$value,$sample);
	}

	public function gauge($suffix,$value) {
		$this->client->gauge($suffix,$value);
	}
}
