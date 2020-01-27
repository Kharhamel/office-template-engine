<?php

/**
 * Constants to drive the plugin.
 */

use OfficeTemplateEngine\Exceptions\OfficeTemplateEngineException;
use OfficeTemplateEngine\lib\TBSXmlLoc;

define('OPENTBS_DOWNLOAD', 1);   // download (default) = TBS_OUTPUT
define('OPENTBS_NOHEADER', 4);   // option to use with DOWNLOAD: no header is sent
define('OPENTBS_FILE', 8);       // output to file   = TBSZIP_FILE
define('OPENTBS_DEBUG_XML', 16); // display the result of the current subfile
define('OPENTBS_STRING', 32);    // output to string = TBSZIP_STRING
define('OPENTBS_DEBUG_AVOIDAUTOFIELDS', 64); // avoit auto field merging during the Show() method
define('OPENTBS_INFO', 'clsOpenTBS.Info');       // command to display the archive info
define('OPENTBS_RESET', 'clsOpenTBS.Reset');      // command to reset the changes in the current archive
define('OPENTBS_ADDFILE', 'clsOpenTBS.AddFile');    // command to add a new file in the archive
define('OPENTBS_DELETEFILE', 'clsOpenTBS.DeleteFile'); // command to delete a file in the archive
define('OPENTBS_REPLACEFILE', 'clsOpenTBS.ReplaceFile'); // command to replace a file in the archive
define('OPENTBS_EDIT_ENTITY', 'clsOpenTBS.EditEntity'); // command to force an attribute
define('OPENTBS_FILEEXISTS', 'clsOpenTBS.FileExists');
define('OPENTBS_CHART', 'clsOpenTBS.Chart');
define('OPENTBS_CHART_INFO', 'clsOpenTBS.ChartInfo');
define('OPENTBS_DEFAULT', '');   // Charset
define('OPENTBS_ALREADY_XML', false);
define('OPENTBS_ALREADY_UTF8', 'already_utf8');
define('OPENTBS_DEBUG_XML_SHOW', 'clsOpenTBS.DebugXmlShow');
define('OPENTBS_DEBUG_XML_CURRENT', 'clsOpenTBS.DebugXmlCurrent');
define('OPENTBS_DEBUG_INFO', 'clsOpenTBS.DebugInfo');
define('OPENTBS_DEBUG_CHART_LIST', 'clsOpenTBS.DebugInfo'); // deprecated
define('OPENTBS_FORCE_DOCTYPE', 'clsOpenTBS.ForceDocType');
define('OPENTBS_DELETE_ELEMENTS', 'clsOpenTBS.DeleteElements');
define('OPENTBS_SELECT_SHEET', 'clsOpenTBS.SelectSheet');
define('OPENTBS_SELECT_SLIDE', 'clsOpenTBS.SelectSlide');
define('OPENTBS_SELECT_MAIN', 'clsOpenTBS.SelectMain');
define('OPENTBS_DISPLAY_SHEETS', 'clsOpenTBS.DisplaySheets');
define('OPENTBS_DELETE_SHEETS', 'clsOpenTBS.DeleteSheets');
define('OPENTBS_DELETE_COMMENTS', 'clsOpenTBS.DeleteComments');
define('OPENTBS_MERGE_SPECIAL_ITEMS', 'clsOpenTBS.MergeSpecialItems');
define('OPENTBS_CHANGE_PICTURE', 'clsOpenTBS.ChangePicture');
define('OPENTBS_COUNT_SLIDES', 'clsOpenTBS.CountSlides');
define('OPENTBS_COUNT_SHEETS', 'clsOpenTBS.CountSheets');
define('OPENTBS_SEARCH_IN_SLIDES', 'clsOpenTBS.SearchInSlides');
define('OPENTBS_DISPLAY_SLIDES', 'clsOpenTBS.DisplaySlides');
define('OPENTBS_DELETE_SLIDES', 'clsOpenTBS.DeleteSlides');
define('OPENTBS_SELECT_FILE', 'clsOpenTBS.SelectFile');
define('OPENTBS_ADD_CREDIT', 'clsOpenTBS.AddCredit');
define('OPENTBS_SYSTEM_CREDIT', 'clsOpenTBS.SystemCredit');
define('OPENTBS_RELATIVE_CELLS', 'clsOpenTBS.RelativeCells');
define('OPENTBS_FIRST', 1); //
define('OPENTBS_GO', 2);    // = TBS_GO
define('OPENTBS_ALL', 4);   // = TBS_ALL
// Types of file to select
define('OPENTBS_GET_HEADERS_FOOTERS', 'clsOpenTBS.SelectHeaderFooter');
define('OPENTBS_SELECT_HEADER', 'clsOpenTBS.SelectHeader');
define('OPENTBS_SELECT_FOOTER', 'clsOpenTBS.SelectFooter');
// Sub-types of file
define('OPENTBS_EVEN', 128);


function CheckArgList(string $Str): array
{
    $ArgLst = [];
    if ($Str[strlen($Str) - 1] ===')') {
        $pos = strpos($Str, '(');
        if ($pos!==false) {
            $ArgLst = explode(',', substr($Str, $pos+1, strlen($Str)-$pos-2));
            $Str = substr($Str, 0, $pos);
        }
    }
    return [$Str, $ArgLst];
}

/**
 * Delete all tags of the types given in the list.
 * @param string $Txt The text content to search into.
 * @param array $TagLst List of tag names to delete.
 * @param boolean $OnlyInner Set to true to keep the content inside the element. Set to false to delete the entire element. Default is false.
 */
function XML_DeleteElements(&$Txt, $TagLst, $OnlyInner = false): int
{
    $nb = 0;
    $Content = !$OnlyInner;
    foreach ($TagLst as $tag) {
        $p = 0;
        while ($x = TBSXmlLoc::FindElement($Txt, $tag, $p)) {
            $x->Delete($Content);
            $p = $x->PosBeg;
            $nb++;
        }
    }
    return $nb;
}

function txtPos($pos)
{
    // Return the human readable position in both decimal and hexa
    return $pos." (h:".dechex($pos).")";
}

function getBin(string $txt, int $pos, int $len): string
{
    $x = substr($txt, $pos, $len);
    $z = '';
    for ($i=0; $i<$len; $i++) {
        $asc = ord($x[$i]);
        if (isset($x[$i])) {
            for ($j=0; $j<8; $j++) {
                $z .= ($asc & pow(2, $j)) ? '1' : '0';
            }
        } else {
            $z .= '00000000';
        }
    }
    return 'b:'.$z;
}

function getDec(string $txt, int $pos, int $len): int
{
    $x = substr($txt, $pos, $len);
    $z = 0;
    for ($i=0; $i<$len; $i++) {
        $asc = ord($x[$i]);
        if ($asc>0) {
            $z = $z + $asc*pow(256, $i);
        }
    }
    return $z;
}

function getHex(string $txt, int $pos, int $len): string
{
    $x = substr($txt, $pos, $len);
    return 'h:'.bin2hex(strrev($x));
}

function XmlFormat(string $Txt): string
{
    // format an XML source the be nicely aligned

    // delete line breaks
    $Txt = str_replace("\r", '', $Txt);
    $Txt = str_replace("\n", '', $Txt);

    // init values
    $p = 0;
    $lev = 0;
    $Res = '';

    $to = true;
    while ($to!==false) {
        $to = strpos($Txt, '<', $p);
        if ($to!==false) {
            $tc = strpos($Txt, '>', $to);
            if ($tc===false) {
                $to = false; // anomaly
            } else {
                // get text between the tags
                $x = trim(substr($Txt, $p, $to-$p), ' ');
                if ($x!=='') {
                    $Res .= "\n".str_repeat(' ', max($lev, 0)).$x;
                }
                // get the tag
                $x = substr($Txt, $to, $tc-$to+1);
                if ($Txt[$to+1]==='/') {
                    $lev--;
                }
                $Res .= "\n".str_repeat(' ', max($lev, 0)).$x;
                // change the level
                if (($Txt[$to+1]!=='?') && ($Txt[$to+1]!=='/') && ($Txt[$tc-1]!=='/')) {
                    $lev++;
                }
                // next position
                $p = $tc + 1;
            }
        }
    }

    $Res = substr($Res, 1); // delete the first line break
    if ($p<strlen($Txt)) {
        $Res .= trim(substr($Txt, $p), ' '); // complete the end
    }

    return $Res;
}
