<?php


namespace OfficeTemplateEngine\lib\Cleaners;

use OfficeTemplateEngine\lib\TBSXmlLoc;

//todo test
class MsPowerpointCleaner
{
    public function clean(string $Txt): string
    {

        $this->cleanRpr($Txt, 'a:rPr');
        $Txt = str_replace('<a:rPr/>', '', $Txt);

        $this->cleanRpr($Txt, 'a:endParaRPr');
        $Txt = str_replace('<a:endParaRPr/>', '', $Txt); // do not delete, can change layout

        // Join split elements
        $Txt = str_replace('</a:t><a:t>', '', $Txt);
        $Txt = str_replace('</a:t></a:r><a:r><a:t>', '', $Txt); // this join TBS split tags

        // Delete empty elements
        // An <a:r> must contain at least one <a:t>. An empty <a:t> may exist after several merges or an OpenTBS cleans.
        $Txt = str_replace('<a:r><a:t></a:t></a:r>', '', $Txt);
        
        return $Txt;
    }

    private function cleanRpr(string $Txt, string $elem): string
    {
        $p = 0;
        while ($x = TBSXmlLoc::FindStartTag($Txt, $elem, $p)) {
            $x->DeleteAtt('noProof');
            $x->DeleteAtt('lang');
            $x->DeleteAtt('err');
            $x->DeleteAtt('smtClean');
            $x->DeleteAtt('dirty');
            $p = $x->PosEnd;
        }
        return $Txt;
    }
}
