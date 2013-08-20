<?php

require_once __DIR__ . '/../setup.php';

use Bliss\Feeds;
use Bliss\Controllers\Reader;
use Bliss\Controllers\Fetch;
use Bliss\Controllers\Manage;

class BlissReaderTest extends PHPUnit_Framework_TestCase
{

    public function testArchives()
    {
        $archive = Reader::archive();
        
        $this->assertContains('Archive', $archive);
        $this->assertContains('2013-08-12', array_keys($archive['archives']));
    }

    public function testNothingFlagged()
    {
        $ret = Reader::nothing("select-flagged-articles");
        $this->assertContains('You have no Flagged Articles', $ret);
    }

    public function testNothingMissingArticle()
    {
        $ret = Reader::nothing("select-article-serfsdf");
        $this->assertContains('The Selected Article can\'t be found', $ret);
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
        $img = Reader::image();

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
        print Reader::read();
    }

    public function testFlag()
    {
        $_POST['name'] = 'b65c61d943f0aa0757474538d66df1f0-cbeerta-s-activity/1376823849-tag-github-com-2008-forkevent-1807306936.item';

        $this->expectOutputRegex('/.*tag_blue_delete.*/im');
        print Reader::flag();
    }

    public function testPoll()
    {
        $_POST['first_id'] = 0;
        $this->expectOutputRegex('/.*updates_available.*true.*/im');
        print Reader::poll('select-all-articles');
    }
}
