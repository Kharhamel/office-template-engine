<?php


namespace tests;

use OpenTBS\Services\OpenTBS;
use PHPUnit\Framework\TestCase;

class OpenTBSTest extends TestCase
{
    public function testBasicPowerpoint(): void
    {
        $tbs = new OpenTBS();
        $handle = fopen(__DIR__.'/var/testOpenTBS.pptx', 'r');
        $tbs->LoadTemplate($handle);
        $tbs->VarRef['textreplace'] = 'super';
        $tbs->VarRef['pic2'] = __DIR__.'/var/pierre.jpeg';
        $tbs->Show(OPENTBS_FILE, __DIR__.'/var/edited.pptx');
        $this->assertNotNull('');
    }
    
    public function testSubTemplateNotfFound(): void
    {
        $tbs = new OpenTBS();
        $handle = fopen(__DIR__.'/var/testOpenTBS.pptx', 'r');
        $tbs->LoadTemplate($handle);
        $this->expectException(\RuntimeException::class);
        $tbs->LoadTemplate("#ppt/slides/slide10.xml");
    }

}