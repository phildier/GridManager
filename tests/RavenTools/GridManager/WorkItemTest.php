<?php

use RavenTools\GridManager\WorkItem;
use Mockery as m;

class WorkItemTest extends PHPUnit_Framework_TestCase {

	private $object = null;

	private $state = null;

	public function setUp() {

		$this->state = [
			'params' => [
				'account_id' => 'abcd-1234',
				'session_id' => 'wxyz-7890'
			],
			'results' => [
				'status' => 'OK',
				'error' => 'no error',
				'things' => [
					'path' => '/tmp',
					'detail' => 'temporary'
				],
				'an_array' => [1,2,3,4]
			]
		];

		$this->object = new WorkItem($this->state);
	}

	public function tearDown() {
	}

	public function testParamsGetKey() {
		$this->assertEquals(
			$this->state['params']['session_id'],
			$this->object->params('session_id')
		);
	}

	public function testParamsGetAll() {

		$response = $this->object->params();
		$this->assertInternalType('array',$response);
		$this->assertEquals($this->state['params'],$response);
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

		$response = $this->object->results();
		$this->assertInternalType('array',$response);
		$this->assertEquals($this->state['results'],$response);
	}

	public function testResultsNullKeyWithValues() {

		$response = $this->object->results(null, [1,2,3]);
		$this->assertInternalType('array',$response);
		$this->assertEquals($this->state['results'],$response);
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
