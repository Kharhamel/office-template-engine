<?php

namespace OfficeTemplateEngine\lib\FileHelpers;

use OfficeTemplateEngine\Exceptions\PicturesManipulationException;

class RelsDataFinder
{
    /**
     * Return an object that represents the informations of an .rels file, but for optimization, targets are scanned only for asked directories.
     * The result is stored in a cache so that a second call will not compute again.
     * The function stores Rids of files existing in a the $TargetPrefix directory of the archive (image, ...).
     * @param $DocPath      Full path of the sub-file in the archive
     * @param $TargetPrefix Prefix of the 'Target' attribute. For example $TargetPrefix='../drawings/'
     */
    public static function OpenXML_Rels_GetObj(string $DocPath, string $TargetPrefix, &$OpenXmlRid, TempArchive $archive)
    {

        if ($OpenXmlRid===false) {
            $OpenXmlRid = array();
        }

        // Create the object if it does not exist yet
        if (!isset($OpenXmlRid[$DocPath])) {
            $o = (object) null;
            $o->RidLst = array();    // Current Rids in the template ($Target=>$Rid)
            $o->TargetLst = array(); // Current Targets in the template ($Rid=>$Target)
            $o->RidNew = array();    // New Rids to add at the end of the merge
            $o->DirLst = array();    // Processed target dir
            $o->ChartLst = false;    // Chart list, computed in another method

            $o->FicPath = PathFinder::relsGetPath($DocPath);

            $FicIdx = $archive->fileGetIdxAdd($o->FicPath);
            if ($FicIdx===false) {
                $o->FicType = 1;
                $Txt = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';
            } else {
                $o->FicIdx = $FicIdx;
                $o->FicType = 0;
                $Txt = $archive->fileRead($FicIdx, true);
            }
            $o->FicTxt = $Txt;
            $o->ParentIdx = $archive->fileGetIdxAdd($DocPath);

            $OpenXmlRid[$DocPath] = &$o;
        } else {
            $o = &$OpenXmlRid[$DocPath];
            $Txt = &$o->FicTxt;
        }

        // Feed the Rid and Target lists for the asked directory
        if (!isset($o->DirLst[$TargetPrefix])) {
            $o->DirLst[$TargetPrefix] = true;

            // read existing Rid in the file
            $zTarget = ' Target="'.$TargetPrefix;
            $zId  = ' Id="';
            $p = -1;
            while (($p = strpos($Txt, $zTarget, $p+1))!==false) {
                // Get the target name
                $p1 = $p + strlen($zTarget);
                $p2 = strpos($Txt, '"', $p1);
                if ($p2===false) {
                    throw new PicturesManipulationException("(OpenXML) end of attribute Target not found in position ".$p1." of sub-file ".$o->FicPath);
                }
                $TargetEnd = substr($Txt, $p1, $p2 -$p1);
                $Target = $TargetPrefix.$TargetEnd;
                // Get the Id
                $p1 = strrpos(substr($Txt, 0, $p), '<');
                if ($p1===false) {
                    throw new PicturesManipulationException("(OpenXML) beginning of tag not found in position ".$p." of sub-file ".$o->FicPath);
                }
                $p1 = strpos($Txt, $zId, $p1);
                if ($p1!==false) {
                    $p1 = $p1 + strlen($zId);
                    $p2 = strpos($Txt, '"', $p1);
                    if ($p2===false) {
                        throw new PicturesManipulationException("(OpenXML) end of attribute Id not found in position ".$p1." of sub-file ".$o->FicPath);
                    }
                    $Rid = substr($Txt, $p1, $p2 - $p1);
                    $o->RidLst[$Target] = $Rid;
                    $o->TargetLst[$Rid] = $Target;
                }
            }
        }

        return $o;
    }
}
