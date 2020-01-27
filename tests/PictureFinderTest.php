<?php


namespace OfficeTemplateEngine;


use OfficeTemplateEngine\lib\PicturesManipulation\PictureFinder;
use PHPUnit\Framework\TestCase;

class PictureFinderTest extends TestCase
{
    public function testNoFind(): void
    {
        $txt = file_get_contents(__DIR__.'/var/exampleSlide1.xml');
        $r = PictureFinder::firstPicAtt($txt, 1787, false, "onshow.pic2");
        $this->assertEquals('a:blip#r:embed', $r);
    }

}