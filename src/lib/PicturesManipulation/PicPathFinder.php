<?php


namespace OfficeTemplateEngine\lib\PicturesManipulation;

use OfficeTemplateEngine\Exceptions\PicturesManipulationException;
use OfficeTemplateEngine\lib\FileHelpers\PathFinder;
use OfficeTemplateEngine\lib\FileHelpers\TempArchive;
use OfficeTemplateEngine\lib\RelsManipulation\RelsDataCollection;
use OfficeTemplateEngine\lib\RelsManipulation\RelsDataFinder;

class PicPathFinder
{

    /**
     * Return the absolute internal path of a target for a given Rid used in the current file.
     * @param RelsDataCollection[] $OpenXmlRid
     * @return string
     */
    public static function getInternalPicPath(string $Rid, string $otbsCurrFile,array  &$OpenXmlRid, TempArchive $archive, string $fullName): string
    {
        $TargetDir = self::getMediaRelativeToCurrent($otbsCurrFile);
        $o = RelsDataFinder::createDataCollectionObject($otbsCurrFile, $TargetDir, $OpenXmlRid, $archive);
        if (isset($o->TargetLst[$Rid])) {
            $x = $o->TargetLst[$Rid]; // relative path
            return PathFinder::getAbsolutePath($x, $otbsCurrFile);
        }
        throw new PicturesManipulationException('The picture to merge with field ['.$fullName.'] cannot be found. Value=' . $Rid);
    }

    private static function getMediaRelativeToCurrent(string $otbsCurrFile): string
    {
        $file = $otbsCurrFile;
        $x = explode('/', $file);
        $dir = $x[0] . '/media';
        return PathFinder::getRelativePath($dir, $file);
    }
}
