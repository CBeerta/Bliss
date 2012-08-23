<?php

define('BLISS_VERSION', '0.0.0');
define('BLISS_BASE_DIR', rtrim(__DIR__ . '/../', '/'));

require_once BLISS_BASE_DIR . '/setup.php';

class BlissReaderTest extends PHPUnit_Framework_TestCase
{

    public function testIndex()
    {
        $this->expectOutputRegex('/.*<footer>.*/im');
        Reader::index();
    }

    public function testArchives()
    {
        $this->markTestIncomplete();
    }

    public function testGallery()
    {
        $this->markTestIncomplete();
    }

    public function testNothing()
    {
        $this->markTestIncomplete();
    }

    public function testGalleryPage()
    {
        $this->markTestIncomplete();
    }

    public function testImage()
    {
        $this->markTestIncomplete();
    }

    public function testNext()
    {
        $_POST['last_id'] = 0;
        Reader::next('select-all-articles');
        $this->markTestIncomplete();
    }

    public function testRead()
    {
        $this->markTestIncomplete();
    }

    public function testFlag()
    {
        $this->markTestIncomplete();
    }

    public function testPoll()
    {
        $this->markTestIncomplete();
    }
}
