<?php


namespace OfficeTemplateEngine\lib\PicturesManipulation;

use OfficeTemplateEngine\Exceptions\PicturesManipulationException;
use OfficeTemplateEngine\lib\FileHelpers\PathFinder;
use OfficeTemplateEngine\lib\FileHelpers\RelsDataFinder;
use OfficeTemplateEngine\lib\FileHelpers\TempArchive;

class PicPathFinder
{
    /**
     * @var TempArchive
     */
    private $archive;

    public function __construct(TempArchive $archive)
    {
        $this->archive = $archive;
    }

    /**
     * Return the absolute internal path of a target for a given Rid used in the current file.
     */
    public function OpenXML_GetInternalPicPath($Rid, $otbsCurrFile, &$OpenXmlRid)
    {
        // $this->OpenXML_CTypesPrepareExt($InternalPicPath, '');
        $TargetDir = $this->OpenXML_GetMediaRelativeToCurrent($otbsCurrFile);
        $o = RelsDataFinder::OpenXML_Rels_GetObj($otbsCurrFile, $TargetDir, $OpenXmlRid, $this->archive);
        if (isset($o->TargetLst[$Rid])) {
            $x = $o->TargetLst[$Rid]; // relative path
            return PathFinder::getAbsolutePath($x, $otbsCurrFile);
        } else {
            return false;
        }
    }

    private function OpenXML_GetMediaRelativeToCurrent($otbsCurrFile)
    {
        $file = $otbsCurrFile;
        $x = explode('/', $file);
        $dir = $x[0] . '/media';
        return PathFinder::getRelativePath($dir, $file);
    }
}
