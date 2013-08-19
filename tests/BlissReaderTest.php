<?php

define('BLISS_VERSION', '0.0.0');
define('BLISS_BASE_DIR', rtrim(__DIR__ . '/../', '/'));

require_once BLISS_BASE_DIR . '/setup.php';
require_once __DIR__ . '/testSetup.php';

class BlissReaderTest extends PHPUnit_Framework_TestCase
{

    public function testIndex()
    {
        $html = Reader::index();
        $this->assertContains('<footer>', $html);
    }

    public function testArchives()
    {
        $html = Reader::archive();
        $this->assertContains('Monday, August 12, 2013', $html);
        $this->assertContains('<footer>', $html);
    }

    public function testGallery()
    {
        $html = Reader::gallery();
        $this->assertContains('jquery.fancybox', $html);
        $this->assertContains('<footer>', $html);
    }

    public function testNothingFlagged()
    {
        $html = Reader::nothing("select-flagged-articles");
        $this->assertContains('You have no Flagged Articles', $html);
    }

    public function testNothingMissingArticle()
    {
        $html = Reader::nothing("select-article-serfsdf");
        $this->assertContains('The Selected Article can\'t be found', $html);
    }

    public function testNothingEmptyFeed()
    {
        $html = Reader::nothing("select-feed-serfsdf");
        $this->assertContains('This Feed has no Articles', $html);
    }

    public function testGalleryPage()
    {
        $html = Reader::galleryPage(0);
        $this->assertContains('thumb=b9f89a64396e53a00f5541814bca964e.spi', $html);
    }

    public function testImage()
    {
        $_GET['i'] = 'b9f89a64396e53a00f5541814bca964e';

        ob_start();
        Reader::image();
        $img = ob_get_contents();
        ob_end_clean();

        $this->assertNotEmpty($img);
    }

    public function testNext()
    {
        $_POST['last_id'] = time();
        $html = Reader::next('select-all-articles');
        $this->assertContains('CBeerta pushed to master at CBeerta/Bliss', $html);
    }

    public function testRead()
    {
        $_POST['name'] = 'b65c61d943f0aa0757474538d66df1f0-cbeerta-s-activity/1376823849-tag-github-com-2008-forkevent-1807306936.item';

        $this->expectOutputRegex('/OK/im');
        Reader::read();    
    }

    public function testFlag()
    {
        $_POST['name'] = 'b65c61d943f0aa0757474538d66df1f0-cbeerta-s-activity/1376823849-tag-github-com-2008-forkevent-1807306936.item';

        $this->expectOutputRegex('/.*tag_blue_delete.*/im');
        Reader::flag();
    }

    public function testPoll()
    {
        $_POST['first_id'] = 0;
        $this->expectOutputRegex('/.*updates_available.*true.*/im');
        Reader::poll('select-all-articles');
    }
}
