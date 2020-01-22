<?php


namespace OfficeTemplateEngine\lib\Cleaners;

use OfficeTemplateEngine\Exceptions\MsOfficeCleanerException;
use OfficeTemplateEngine\lib\TBSXmlLoc;

//todo test
class MsWordCleaner
{
    /**
     * <mc:Fallback> entities may contains duplicated TBS fields and this may corrupt the merging.
     * This function delete such entities if they seems to contain TBS fields. This make the DOCX content less compatible with previous Word versions.
     * https://wiki.openoffice.org/wiki/OOXML/Markup_Compatibility_and_Extensibility
     */
    /*public function cleanFallbacks(string $Txt): string
    {
        $p = 0;
        $nb = 0;
        while (($loc = TBSXmlLoc::FindElement($Txt, 'mc:Fallback', $p))!==false) {
            if (strpos($loc->GetSrc(), $this->TBS->_ChrOpen) !== false) {
                $loc->Delete();
                $nb++;
            }
            $p = $loc->PosEnd;
        }
        return $Txt;
    }*/
    
    /**
     * Prevent from the problem of missing spaces when calling ->MsWord_CleanRsID() or under certain merging circumstances.
     * Replace attribute xml:space="preserve" used in <w:t>, with the same attribute in <w:document>.
     * This trick works for MsWord 2007, 2010 but is undocumented. It may be desabled by default in a next version.
     * LibreOffice does ignore this attribute in both <w:t> and <w:document>.
     */
    public function cleanSpacePreserve(string $Txt): string
    {
        $XmlLoc = TBSXmlLoc::FindStartTag($Txt, 'w:document', 0);
        if ($XmlLoc===false) {
            return $Txt;
        }
        if ($XmlLoc->GetAttLazy('xml:space') === 'preserve') {
            return $Txt;
        }

        $Txt = str_replace(' xml:space="preserve"', '', $Txt); // not mendatory but cleanner and save space
        $XmlLoc->ReplaceAtt('xml:space', 'preserve', true);
        return $Txt;
    }
    
    public function clean(string $Txt): string
    {
        $Txt = str_replace('<w:lastRenderedPageBreak/>', '', $Txt); // faster
        //$this->MsWord_CleanFallbacks($Txt);
        XML_DeleteElements($Txt, array('w:proofErr', 'w:noProof', 'w:lang', 'w:lastRenderedPageBreak'));
        $this->msWordCleanSystemBookmarks($Txt);
        $this->msWordCleanRsID($Txt);
        $this->msWordCleanDuplicatedLayout($Txt);
        return $Txt;
    }

    private function msWordCleanSystemBookmarks(string $Txt): string
    {
        // Delete GoBack hidden bookmarks that appear since Office 2010. Example: <w:bookmarkStart w:id="0" w:name="_GoBack"/><w:bookmarkEnd w:id="0"/>

        $x = ' w:name="_GoBack"/><w:bookmarkEnd ';
        $x_len = strlen($x);

        $b = '<w:bookmarkStart ';
        $b_len = strlen($b);

        $nbr_del = 0;

        $p = 0;
        while (($p=strpos($Txt, $x, $p))!==false) {
            $pe = strpos($Txt, '>', $p + $x_len);
            if ($pe===false) {
                throw new MsOfficeCleanerException();
            }
            $pb = strrpos(substr($Txt, 0, $p), '<');
            if ($pb===false) {
                throw new MsOfficeCleanerException();
            }
            if (substr($Txt, $pb, $b_len)===$b) {
                $Txt = substr_replace($Txt, '', $pb, $pe - $pb + 1);
                $p = $pb;
                $nbr_del++;
            } else {
                $p = $pe +1;
            }
        }

        return $Txt;
    }

    private function msWordCleanRsID(string $Txt): string
    {
        /* Delete XML attributes relative to log of user modifications. Returns the number of deleted attributes.
        In order to insert such information, MsWord does split TBS tags with XML elements.
        After such attributes are deleted, we can concatenate duplicated XML elements. */

        $rs_lst = array('w:rsidR', 'w:rsidRPr');

        $nbr_del = 0;
        foreach ($rs_lst as $rs) {
            $rs_att = ' '.$rs.'="';
            $rs_len = strlen($rs_att);

            $p = 0;
            while ($p!==false) {
                // search the attribute
                $ok = false;
                $p = strpos($Txt, $rs_att, $p);
                if ($p!==false) {
                    // attribute found, now seach tag bounds
                    $po = strpos($Txt, '<', $p);
                    $pc = strpos($Txt, '>', $p);
                    if (($pc!==false) && ($po!==false) && ($pc<$po)) { // means that the attribute is actually inside a tag
                        $p2 = strpos($Txt, '"', $p+$rs_len); // position of the delimiter that closes the attribute's value
                        if (($p2!==false) && ($p2<$pc)) {
                            // delete the attribute
                            $Txt = substr_replace($Txt, '', $p, $p2 -$p +1);
                            $ok = true;
                            $nbr_del++;
                        }
                    }
                    if (!$ok) {
                        $p = $p + $rs_len;
                    }
                }
            }
        }

        // delete empty tags
        $Txt = str_replace('<w:rPr></w:rPr>', '', $Txt);
        $Txt = str_replace('<w:pPr></w:pPr>', '', $Txt);

        return $Txt;
    }

    /**
     * MsWord cut the source of the text when a modification is done. This is splitting TBS tags.
     * This function repare the split text by searching and delete duplicated layout.
     * Return the number of deleted dublicates.
     */
    private function msWordCleanDuplicatedLayout(string $Txt): string
    {

        $wro = '<w:r';
        $wro_len = strlen($wro);

        $wrc = '</w:r';
        $wrc_len = strlen($wrc);

        $wto = '<w:t';
        $wto_len = strlen($wto);

        $wtc = '</w:t';
        $wtc_len = strlen($wtc);

        $preserve = 'xml:space="preserve"';

        $nbr = 0;
        $wro_p = 0;
        while (($wro_p=$this->xMLFoundTagStart($Txt, $wro, $wro_p))!==false) { // next <w:r> tag
            $wto_p = $this->xMLFoundTagStart($Txt, $wto, $wro_p); // next <w:t> tag
            if ($wto_p===false) {
                throw new MsOfficeCleanerException('error in the structure of the <w:r> element');
            }
            $first = true;
            $last_att = '';
            $first_att = '';
            $p_first_att = 0;
            $superfluous = '';
            do {
                $ok = false;
                $wtc_p = $this->xMLFoundTagStart($Txt, $wtc, $wto_p); // next </w:t> tag
                if ($wtc_p===false) {
                    throw new MsOfficeCleanerException();
                }
                $wrc_p = $this->xMLFoundTagStart($Txt, $wrc, $wro_p); // next </w:r> tag (only to check inclusion)
                if ($wrc_p===false) {
                    throw new MsOfficeCleanerException();
                }
                if (($wto_p<$wrc_p) && ($wtc_p<$wrc_p)) { // if the <w:t> is actually included in the <w:r> element
                    $superfluous_len = 0;
                    if ($first) {
                        // text that is concatenated and can be simplified
                        $superfluous = '</w:t></w:r>'.substr($Txt, $wro_p, ($wto_p+$wto_len)-$wro_p); // without the last symbol, like: '</w:t></w:r><w:r>....<w:t'
                        $superfluous = str_replace('<w:tab/>', '', $superfluous); // tabs must not be deleted between parts => they nt be in the superfluous string
                        $superfluous_len = strlen($superfluous);
                        $first = false;
                        $p_first_att = $wto_p+$wto_len;
                        $p =  strpos($Txt, '>', $wto_p);
                        if ($p!==false) {
                            $first_att = substr($Txt, $p_first_att, $p-$p_first_att);
                        }
                    }
                    // if the <w:r> layout is the same than the next <w:r>, then we join them
                    $p_att = $wtc_p + $superfluous_len;
                    $x = substr($Txt, $p_att, 1); // must be ' ' or '>' if the string is the superfluous AND the <w:t> tag has or not attributes
                    if ((($x===' ') || ($x==='>')) && (substr($Txt, $wtc_p, $superfluous_len)===$superfluous)) {
                        $p_end = strpos($Txt, '>', $wtc_p+$superfluous_len); //
                        if ($p_end===false) {
                            throw new MsOfficeCleanerException('error in the structure of the <w:t> tag');
                        }
                        $last_att = substr($Txt, $p_att, $p_end-$p_att);
                        $Txt = substr_replace($Txt, '', $wtc_p, $p_end-$wtc_p+1); // delete superfluous part + <w:t> attributes
                        $nbr++;
                        $ok = true;
                    }
                }
            } while ($ok);

            // Recover the 'preserve' attribute if the last join element was having it. We check also the first one because the attribute must not be twice.
            if (($last_att!=='') && (strpos($first_att, $preserve)===false)  && (strpos($last_att, $preserve)!==false)) {
                $Txt = substr_replace($Txt, ' '.$preserve, $p_first_att, 0);
            }

            $wro_p = $wro_p + $wro_len;
        }

        return $Txt;
    }
    
    private function xMLFoundTagStart(string $Txt, string $Tag, int $PosBeg)
    {
        // Found the next tag of the asked type. (Not specific to MsWord, works for any XML)
        // Tag must be prefixed with '<' or '</'.
        $len = strlen($Tag);
        $p = $PosBeg;
        while ($p!==false) {
            $p = strpos($Txt, $Tag, $p);
            if ($p===false) {
                return false;
            }
            $x = substr($Txt, $p+$len, 1);
            if (($x===' ') || ($x==='/') || ($x==='>')) {
                return $p;
            } else {
                $p = $p+$len;
            }
        }
    }
}
