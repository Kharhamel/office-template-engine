<?php


namespace OfficeTemplateEngine;


use OfficeTemplateEngine\lib\FileHelpers\PathFinder;
use PHPUnit\Framework\TestCase;

class PathFinderTest extends TestCase
{
    public function testRelativePath(): void
    {
        $r = PathFinder::getRelativePath('dir1/dir2/file_a.xml', 'dir1/dir2/file_b.xml');
        $this->assertEquals('file_a.xml', $r);
        $r = PathFinder::getRelativePath('dir1/file_a.xml', 'dir1/dir2/file_b.xml');
        $this->assertEquals('../file_a.xml', $r);
    }

    public function testAbsolutePath(): void
    {
        $r = PathFinder::getAbsolutePath('../file_a.xml', 'dir1/dir2/file_b.xml');
        $this->assertEquals('dir1/file_a.xml', $r);
    }

    public function testRelsGetPath(): void
    {
        $r = PathFinder::relsGetPath('ppt/presentation.xml');
        $this->assertEquals('ppt/_rels/presentation.xml.rels', $r);
        $r = PathFinder::relsGetPath('ppt/slides/slide1.xml');
        $this->assertEquals('ppt/slides/_rels/slide1.xml.rels', $r);
    }

}