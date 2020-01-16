<?php


namespace OpenTBS;

use http\Exception\RuntimeException;
use OpenTBS\Exceptions\OpenTBSException;
use OpenTBS\Services\OpenTBS;
use PHPUnit\Framework\TestCase;

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
        $tbs->Show(OPENTBS_FILE, 'osef');
    }

    //todo: find a way to insert than the variable is actually injected
    public function testTextAndPictureInjection(): void
    {
        $editedPath = __DIR__.'/var/edited3.pptx';
        if (file_exists($editedPath)) {
            unlink($editedPath);
        }

        $tbs = new OpenTBS();
        $handle = fopen(__DIR__.'/var/testOpenTBSWithImage.pptx', 'r');
        $tbs->LoadTemplate($handle);
        $tbs->VarRef['textreplace'] = 'super';
        $tbs->VarRef['pic2'] = __DIR__.'/var/pierre.jpeg';
        $tbs->Show(OPENTBS_FILE, $editedPath);
        $this->assertFileExists($editedPath);
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
        $tbs->VarRef['textreplace'] = 'super';
        $tbs->VarRef['textreplace2'] = 'encore plus super';
        $tbs->Show(OPENTBS_FILE, $editedPath);
        $this->assertFileExists($editedPath);
        
    } 
    
    public function testSubTemplateNotFound(): void
    {
        $tbs = new OpenTBS();
        $handle = fopen(__DIR__.'/var/testOpenTBS.pptx', 'r');
        $tbs->LoadTemplate($handle);
        $this->expectException(OpenTBSException::class);
        $tbs->LoadTemplate('#ppt/slides/slide10.xml');
    }

}