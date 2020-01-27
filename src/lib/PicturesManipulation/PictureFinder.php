<?php


namespace OfficeTemplateEngine\lib\PicturesManipulation;

use OfficeTemplateEngine\Exceptions\PicturesManipulationException;

class PictureFinder
{
    public static function firstPicAtt(string $Txt, $Pos, bool $Backward, string $fullName): string
    {
        // search the first image element in the given direction. Two types of image can be found. Return the value required for "att" parameter.
        $TypeVml = '<v:imagedata ';
        $TypeDml = '<a:blip ';

        if ($Backward) {
            // search the last image position this code is compatible with PHP 4
            $p = -1;
            $pMax = -1;
            $t_curr = $TypeVml;
            $t = '';
            do {
                $p = strpos($Txt, $t_curr, $p+1);
                if (($p===false) || ($p>=$Pos)) {
                    if ($t_curr===$TypeVml) {
                        // we take a new search for the next type of image
                        $t_curr = $TypeDml;
                        $p = -1;
                    } else {
                        $p = false;
                    }
                } elseif ($p>$pMax) {
                    $pMax = $p;
                    $t = $t_curr;
                }
            } while ($p!==false);
        } else {
            $p1 = strpos($Txt, $TypeVml, $Pos);
            $p2 = strpos($Txt, $TypeDml, $Pos);
            if (($p1===false) && ($p2===false)) {
                $t = '';
            } elseif ($p1===false) {
                $t = $TypeDml;
            } elseif ($p2===false) {
                $t = $TypeVml;
            } else {
                $t = ($p1<$p2) ? $TypeVml : $TypeDml;
            }
        }

        if ($t===$TypeVml) {
            return 'v:imagedata#r:id';
        } elseif ($t===$TypeDml) {
            return 'a:blip#r:embed';
        } else {
            throw new PicturesManipulationException("Parameter ope=changepic used in the field [$fullName] has failed to found the picture.");
        }
    }
}
