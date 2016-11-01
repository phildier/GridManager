<?php

use RavenTools\GridManager\QueueMessage;
use Mockery as m;

class QueueMessageTest extends PHPUnit_Framework_TestCase {

	private $object = null;

	public function setUp() {
		$this->object = new QueueMessage;
	}

	public function tearDown() {
	}

	public function testGetSetQueue() {
		$m = m::mock('SQSQueue');
		$this->object->setQueue($m);
		$this->assertSame($m,$this->object->getQueue());
	}

	public function testGetSetHandle() {
		$handle = "abcd1234";
		$this->object->setHandle($handle);
		$this->assertSame($handle,$this->object->getHandle());
	}

	public function testGetSetBody() {
		$body = ['stuff' => 'things', 'other' => [1,2,3,4]];
		$this->object->setBody($body);
		$this->assertSame($body,$this->object->getBody());
	}

	public function testDelete() {
		$m = m::mock('SQSQueue')
			->shouldReceive('delete')
			->andReturn(true,false)
			->getMock();

		$this->object->setQueue($m);

		$this->assertTrue($this->object->delete());
		$this->assertFalse($this->object->delete());
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testDeleteMissingQueue() {
		$this->object->delete();
	}
}
