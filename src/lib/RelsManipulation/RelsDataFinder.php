<?php

namespace OfficeTemplateEngine\lib\RelsManipulation;

use OfficeTemplateEngine\Exceptions\OfficeTemplateEngineException;
use OfficeTemplateEngine\lib\FileHelpers\PathFinder;
use OfficeTemplateEngine\lib\FileHelpers\TempArchive;

class RelsDataFinder
{
    /**
     * Return an object that represents the informations of an .rels file, but for optimization, targets are scanned only for asked directories.
     * The result is stored in a cache so that a second call will not compute again.
     * The function stores Rids of files existing in a the $TargetPrefix directory of the archive (image, ...).
     *
     * @param string $DocPath Full path of the sub-file in the archive
     * @param string $TargetPrefix Prefix of the 'Target' attribute. For example $TargetPrefix='../drawings/'
     * @param RelsDataCollection[] $openXmlRid
     */
    public static function createDataCollectionObject(string $DocPath, string $TargetPrefix, array &$openXmlRid, TempArchive $archive): RelsDataCollection
    {

        // Create the object if it does not exist yet
        if (!isset($openXmlRid[$DocPath])) {
            $o = new RelsDataCollection();
            $o->FicPath = PathFinder::relsGetPath($DocPath);

            $FicIdx = $archive->CdFileLst->fileGetIdx($o->FicPath);
            if ($FicIdx===false) {
                $o->FicType = 1;
                $Txt = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';
            } else {
                $o->FicIdx = $FicIdx;
                $o->FicType = 0;
                $Txt = $archive->fileRead($FicIdx, true);
            }
            $o->FicTxt = $Txt;
            $o->ParentIdx = $archive->CdFileLst->fileGetIdx($DocPath);

            $openXmlRid[$DocPath] = &$o;
        } else {
            $o = &$openXmlRid[$DocPath];
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
                    throw new OfficeTemplateEngineException("(OpenXML) end of attribute Target not found in position ".$p1." of sub-file ".$o->FicPath);
                }
                $TargetEnd = substr($Txt, $p1, $p2 -$p1);
                $Target = $TargetPrefix.$TargetEnd;
                // Get the Id
                $p1 = strrpos(substr($Txt, 0, $p), '<');
                if ($p1===false) {
                    throw new OfficeTemplateEngineException("(OpenXML) beginning of tag not found in position ".$p." of sub-file ".$o->FicPath);
                }
                $p1 = strpos($Txt, $zId, $p1);
                if ($p1!==false) {
                    $p1 = $p1 + strlen($zId);
                    $p2 = strpos($Txt, '"', $p1);
                    if ($p2===false) {
                        throw new OfficeTemplateEngineException("(OpenXML) end of attribute Id not found in position ".$p1." of sub-file ".$o->FicPath);
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
