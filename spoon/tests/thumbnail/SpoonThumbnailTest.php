<?php

use PHPUnit\Framework\TestCase;

$includePath = dirname(dirname(dirname(dirname(__FILE__))));
set_include_path(get_include_path() . PATH_SEPARATOR . $includePath);

require_once 'spoon/spoon.php';

class SpoonThumbnailTest extends TestCase
{
	public function testIsSupportedFileType()
	{
		$this->assertTrue(
			SpoonThumbnail::isSupportedFileType(dirname(dirname(realpath(__FILE__))) . '/tmp/spoon.jpg')
		);
	}
}
