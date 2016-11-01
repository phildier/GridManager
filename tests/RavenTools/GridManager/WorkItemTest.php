<?php

use RavenTools\GridManager\WorkItem;
use Mockery as m;

class WorkItemTest extends PHPUnit_Framework_TestCase {

	private $object = null;

	private $results = [
		'status' => 'OK',
		'error' => 'no error',
		'things' => [
			'path' => '/tmp',
			'detail' => 'temporary'
		],
		'an_array' => [1,2,3,4]
	];

	public function setUp() {
		$this->object = new WorkItem($this->results);
	}

	public function tearDown() {
	}

	public function testResultsSetGetKey() {

		$test_results = [
			'things' => 12345,
			'stuff' => 54321
		];

		$this->object->results('test',$test_results);
		$this->assertEquals($test_results,$this->object->results('test'));
	}

	public function testResultsGetAll() {

		$this->assertEquals($this->results,$this->object->results());
	}

	public function testResultsNullKeyWithValues() {

		$response = $this->object->results(null, [1,2,3]);
		$this->assertEquals($this->results,$response);
	}

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testSetOutOfBoundsException() {
		$this->object->bogus = 1;
	}

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testGetOutOfBoundsException() {
		$a = $this->object->bogus;
	}
}
