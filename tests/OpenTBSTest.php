<?php


namespace OpenTBS;

use OpenTBS\Exceptions\OpenTBSException;
use OpenTBS\Services\OpenTBS;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class OpenTBSTest extends TestCase
{
    public function testBasicPowerpointCreationFromHandle(): void
    {
        $editedPath = __DIR__.'/var/edited.pptx';
        if (file_exists($editedPath)) {
            unlink($editedPath);
        }
        
        $tbs = new OpenTBS();
        $handle = fopen(__DIR__.'/var/testOpenTBS.pptx', 'r');
        $tbs->LoadTemplate($handle);
        $tbs->Show(OPENTBS_FILE, $editedPath);
        $this->assertFileExists($editedPath);
    }

    public function testBasicPowerpointFromPath(): void
    {
        $editedPath = __DIR__.'/var/edited2.pptx';
        if (file_exists($editedPath)) {
            unlink($editedPath);
        }
        $tbs = new OpenTBS();
        $tbs->LoadTemplate(__DIR__.'/var/testOpenTBS.pptx');
        $tbs->Show(OPENTBS_FILE, $editedPath);
        $this->assertFileExists($editedPath);
    }

    public function testBasicPowerpointToString(): void
    {
        $tbs = new OpenTBS();
        $handle = fopen(__DIR__.'/var/testOpenTBS.pptx', 'r');
        $tbs->LoadTemplate($handle);
        $tbs->Show(OPENTBS_STRING);
        $source = $tbs->Source;
        $this->assertNotNull($source);
    }

    public function testExceptionOnNoTemplatePath(): void
    {
        $tbs = new OpenTBS();
        $this->expectException(OpenTBSException::class);
        $tbs->LoadTemplate(__DIR__.'/var/notExist.pptx');
    }
    
    public function testExceptionOnUnfoundVariable(): void
    {
        $tbs = new OpenTBS();
        $handle = fopen(__DIR__.'/var/testOpenTBSWithText.pptx', 'r');
        $tbs->LoadTemplate($handle);
        $tbs->LoadTemplate('#ppt/slides/slide2.xml');
        $this->expectException(OpenTBSException::class);
        $tbs->Show(OPENTBS_FILE, __DIR__.'/var/editedTruc.pptx');
    }
    
    public function testTextAndPictureInjection(): void
    {
        $editedPath = __DIR__.'/var/edited3.pptx';
        if (file_exists($editedPath)) {
            unlink($editedPath);
        }

        $tbs = new OpenTBS();
        $handle = fopen(__DIR__.'/var/testOpenTBSWithImage.pptx', 'r');
        $tbs->LoadTemplate($handle);
        $textToInject = 'here is the injected text';
        $tbs->VarRef['textreplace'] = $textToInject;
        $tbs->VarRef['pic2'] = __DIR__.'/var/pierre.jpeg';
        $tbs->Show(OPENTBS_FILE, $editedPath);
        $this->assertFileExists($editedPath);

        $zip = new ZipArchive();
        $res = $zip->open($editedPath);
        $this->assertTrue($res);
        $injectImageName = $zip->getFromName('ppt/media/opentbs_added_1.jpeg');
        $this->assertNotFalse($injectImageName);
        $editedSlideText = $zip->getFromName('ppt/slides/slide1.xml');
        $this->assertNotFalse(strpos($editedSlideText, $textToInject));
    }

    public function testTextInjectionOn2slides(): void
    {
        $editedPath = __DIR__.'/var/edited4.pptx';
        if (file_exists($editedPath)) {
            unlink($editedPath);
        }
        $tbs = new OpenTBS();
        $handle = fopen(__DIR__.'/var/testOpenTBSWithText.pptx', 'r');
        $tbs->LoadTemplate($handle);
        $tbs->LoadTemplate('#ppt/slides/slide2.xml');
        $tbs->VarRef['textreplace'] = 'injected text slide 1';
        $tbs->VarRef['textreplace2'] = 'injected text slide 2';
        $tbs->Show(OPENTBS_FILE, $editedPath);
        $this->assertFileExists($editedPath);

        $zip = new ZipArchive();
        $res = $zip->open($editedPath);
        $this->assertTrue($res);
        $editedSlideText = $zip->getFromName('ppt/slides/slide1.xml');
        $this->assertNotFalse(strpos($editedSlideText, 'injected text slide 1'));
        $editedSlideText = $zip->getFromName('ppt/slides/slide2.xml');
        $this->assertNotFalse(strpos($editedSlideText, 'injected text slide 2'));
        
    } 
    
    public function testSubTemplateNotFound(): void
    {
        $tbs = new OpenTBS();
        $handle = fopen(__DIR__.'/var/testOpenTBS.pptx', 'r');
        $tbs->LoadTemplate($handle);
        $this->expectException(OpenTBSException::class);
        $tbs->LoadTemplate('#ppt/slides/slide10.xml');
    }
    
    public function testUnloadTemplate(): void
    {
        $tbs = new OpenTBS();
        $handle = fopen(__DIR__.'/var/testOpenTBS.pptx', 'r');
        $tbs->LoadTemplate($handle);
        $tbs->LoadTemplate(false);
        $handle = fopen(__DIR__.'/var/testOpenTBS.pptx', 'r');
        $this->assertTrue(true); //todo find something to assert
    }

}