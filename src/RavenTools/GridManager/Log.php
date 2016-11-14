<?php

namespace RavenTools\GridManager;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ElasticSearchHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\ElasticaFormatter;

class Log {

	private static $monolog = null;

	public static function enable($log_name) {

		self::$monolog = new Logger($log_name);

		// log everything to stdout
		$streamhandler = new StreamHandler("php://stdout", Logger::DEBUG);
		$streamhandler->setFormatter(new LineFormatter(null, null, true, true));
		self::$monolog->pushHandler($streamhandler);

		/*
		if(Config::getInstance()->has('elasticsearch')) {

			$es_connections = Config::getInstance()['elasticsearch'];

			$es_client = new \Elastica\Client([
				'connections' => $es_connections
			]);

			$es_options = [
				'index' => 'auditor_index',
				'type' => 'document'
			];

			// only log warnings+errors to elastic search
			$elasticsearch = new ElasticSearchHandler($es_client,$es_options,Logger::WARN);
			$elasticsearch->setFormatter(new ElasticaFormatter('auditor_index','document'));
			self::$monolog->pushHandler($elasticsearch);
		}
		*/
	}

	public static function __callStatic($name,$args) {

		if(is_object(self::$monolog)) {

			if(!is_string($args[0])) {
				$args[0] = json_encode($args[0]);
			}

			call_user_func_array([self::$monolog,$name],$args);
		}
	}
}
