<?php


namespace OfficeTemplateEngine\lib\PicturesManipulation;

use OfficeTemplateEngine\Exceptions\OfficeTemplateEngineException;
use OfficeTemplateEngine\Exceptions\PicturesManipulationException;
use OfficeTemplateEngine\lib\FileHelpers\TempArchive;
use OfficeTemplateEngine\lib\TBSEngine;
use OfficeTemplateEngine\lib\TBSXmlLoc;
use OfficeTemplateEngine\lib\TBSZip;

class PicturePreparer
{
    /**
     * @var TempArchive
     */
    private $archive;
    /**
     * @var PicPathFinder
     */
    private $picPathFinder;

    public function __construct(TempArchive $archive)
    {
        $this->archive = $archive;
        $this->picPathFinder = new PicPathFinder($this->archive);
    }

    // Found the relevant attribute for the image source, and then add parameter 'att' to the TBS locator.
    public function TbsPicPrepare(string &$Txt, &$Loc, bool $IsCaching, string $extType, array $extInfo, $otbsCurrFile, &$OpenXmlRid): void
    {
        $InternalPicPath = null;

        if ($Loc->PrmLst->pic_prepared) {
            return;
        }

        if ($Loc->PrmLst->att) {
            throw new PicturesManipulationException('Parameter att is used with parameter ope=changepic in the field ['.$Loc->FullName.']. changepic will be ignored');
        }

        $backward = true;

        if ($Loc->PrmLst->tagpos) {
            $tagPosition = $Loc->PrmLst->tagpos;
            if ($tagPosition==='before') {
                $backward = false;
            } elseif ($tagPosition==='inside') {
                if ($extType==='openxml') {
                    $backward = false;
                }
            }
        }

        // Find the target attribute
        $att = false;
        if ($extType==='odf') {
            $att = 'draw:image#xlink:href';
        } elseif ($extType==='openxml') {
            $att = PictureFinder::firstPicAtt($Txt, $Loc->PosBeg, $backward, $Loc->FullName);
        } else {
            throw new OfficeTemplateEngineException('Parameter ope=changepic used in the field ['.$Loc->FullName.'] is not supported with the current document type.');
        }

        // Move the field to the attribute
        // This technical works with cached fields because already cached fields are placed before the picture.
        $prefix = $backward ? '' : '+';
        $Loc->PrmLst->att = $prefix.$att;
        TBSEngine::f_Xml_AttFind($Txt, $Loc, true);

        // Delete parameter att to prevent TBS from another processing
        $Loc->PrmLst->att = null;

        // Get picture dimension information
        if ($Loc->PrmLst->adjust) {
            $FieldLen = 0;
            if ($extType==='odf') {
                $Loc->otbsDim = $this->TbsPicGetDim_ODF($Txt, $Loc->PosBeg, false, $Loc->PosBeg, $FieldLen);
            } else {
                if (strpos($att, 'v:imagedata')!==false) {
                    $Loc->otbsDim = $this->TbsPicGetDim_OpenXML_vml($Txt, $Loc->PosBeg, false, $Loc->PosBeg, $FieldLen);
                } else {
                    $Loc->otbsDim = $this->TbsPicGetDim_OpenXML_dml($Txt, $Loc->PosBeg, false, $Loc->PosBeg, $FieldLen, $otbsCurrFile, $extInfo);
                }
            }
        }

        // Set the original picture to empty
        if ($Loc->PrmLst->unique) {
            // Get the value in the template
            $Value = substr($Txt, $Loc->PosBeg, $Loc->PosEnd -  $Loc->PosBeg +1);

            if ($extType==='odf') {
                $InternalPicPath = $Value;
            } elseif ($extType==='openxml') {
                $InternalPicPath = $this->picPathFinder->OpenXML_GetInternalPicPath($Value, $otbsCurrFile, $OpenXmlRid);
                if ($InternalPicPath === false) {
                    throw new PicturesManipulationException('The picture to merge with field ['.$Loc->FullName.'] cannot be found. Value=' . $Value);
                }
            }

            // Set the picture file to empty
            $this->archive->fileReplace($InternalPicPath, '', TBSZip::TBSZIP_STRING, false);
        }

        $Loc->PrmLst->pic_prepared = true;
        return;
    }

    private function TbsPicGetDim_ODF($Txt, $Pos, $Forward, $FieldPos, $FieldLen)
    {
        // Found the attributes for the image dimensions, in an ODF file
        // unit (can be: mm, cm, in, pi, pt)
        $Offset = 0;
        $dim = $this->TbsPicGetDim_Any($Txt, $Pos, $Forward, $FieldPos, $FieldLen, $Offset, 'draw:frame', 'svg:width="', 'svg:height="', 3, false, false);
        return array($dim);
    }

    private function TbsPicGetDim_OpenXML_vml($Txt, $Pos, $Forward, $FieldPos, $FieldLen)
    {
        $Offset = 0;
        $dim = $this->TbsPicGetDim_Any($Txt, $Pos, $Forward, $FieldPos, $FieldLen, $Offset, 'v:shape', 'width:', 'height:', 2, false, false);
        return array($dim);
    }

    private function TbsPicGetDim_OpenXML_dml($Txt, $Pos, $Forward, $FieldPos, $FieldLen, $otbsCurrFile, array $extInfo)
    {

        $Offset = 0;

        // Try to find the drawing element
        if (isset($extInfo['pic_entity'])) {
            $tag = $extInfo['pic_entity'];
            $Loc = TBSXmlLoc::FindElement($Txt, $tag, $Pos, false);
            if ($Loc) {
                $Txt = $Loc->GetSrc();
                $Pos = 0;
                $Forward = true;
                $Offset = $Loc->PosBeg;
            }
        }

        $dim_shape = $this->TbsPicGetDim_Any($Txt, $Pos, $Forward, $FieldPos, $FieldLen, $Offset, 'wp:extent', 'cx="', 'cy="', 0, 12700, false);
        $dim_inner = $this->TbsPicGetDim_Any($Txt, $Pos, $Forward, $FieldPos, $FieldLen, $Offset, 'a:ext', 'cx="', 'cy="', 0, 12700, 'uri="');
        $dim_drawing = $this->TbsPicGetDim_Drawings($Txt, $Pos, $FieldPos, $FieldLen, $Offset, $dim_inner, $otbsCurrFile); // check for XLSX

        // dims must be sorted in reverse order of location
        $result = array();
        if ($dim_shape!==false) {
            $result[$dim_shape['wb']] = $dim_shape;
        }
        if ($dim_inner!==false) {
            $result[$dim_inner['wb']] = $dim_inner;
        }
        if ($dim_drawing!==false) {
            $result[$dim_drawing['wb']] = $dim_drawing;
        }
        krsort($result);

        return $result;
    }

    // Found the attributes for the image dimensions, in an ODF file
    private function TbsPicGetDim_Any($Txt, $Pos, $Forward, $FieldPos, $FieldLen, $Offset, $Element, $AttW, $AttH, $AllowedDec, $CoefToPt, $IgnoreIfAtt)
    {

        while (true) {
            $p = TBSEngine::f_Xml_FindTagStart($Txt, $Element, true, $Pos, $Forward, true);
            if ($p===false) {
                return false;
            }

            $pe = strpos($Txt, '>', $p);
            if ($pe===false) {
                return false;
            }

            $x = substr($Txt, $p, $pe -$p);

            if (($IgnoreIfAtt===false) || (strpos($x, $IgnoreIfAtt)===false)) {
                $att_lst = array('w'=>$AttW, 'h'=>$AttH);
                $res_lst = array();

                foreach ($att_lst as $i => $att) {
                    $l = strlen($att);
                    $b = strpos($x, $att);
                    if ($b===false) {
                        return false;
                    }
                    $b = $b + $l;
                    $e = strpos($x, '"', $b);
                    $e2 = strpos($x, ';', $b); // in case of VML format, width and height are styles separted by ;
                    if ($e2!==false) {
                        $e = min($e, $e2);
                    }
                    if ($e===false) {
                        return false;
                    }
                    $lt = $e - $b;
                    $t = substr($x, $b, $lt);
                    $pu = $lt; // unit first char
                    while (($pu>1) && (!is_numeric($t[$pu-1]))) {
                        $pu--;
                    }
                    $u = ($pu>=$lt) ? '' : substr($t, $pu);
                    $v = floatval(substr($t, 0, $pu));
                    $beg = $Offset+$p+$b;
                    if ($beg>$FieldPos) {
                        $beg = $beg - $FieldLen;
                    }
                    $res_lst[$i.'b'] = $beg; // start position in the main string
                    $res_lst[$i.'l'] = $lt; // length of the text
                    $res_lst[$i.'u'] = $u; // unit
                    $res_lst[$i.'v'] = $v; // value
                    $res_lst[$i.'t'] = $t; // text
                    $res_lst[$i.'o'] = 0; // offset
                }

                $res_lst['r'] = ($res_lst['hv']==0) ? 0.0 : $res_lst['wv']/$res_lst['hv']; // ratio W/H
                $res_lst['dec'] = $AllowedDec; // save the allowed decimal for this attribute
                $res_lst['cpt'] = $CoefToPt;
                return $res_lst;
            } else {
                // Next try
                $Pos = $p + (($Forward) ? +1 : -1);
            }
        }
    }

    // Get Dim in an OpenXML Drawing (pictures in an XLSX)
    private function TbsPicGetDim_Drawings($Txt, $Pos, $FieldPos, $FieldLen, $Offset, $dim_inner, $otbsCurrFile)
    {

        // The <a:ext> coordinates must have been found previously.
        if ($dim_inner===false) {
            return false;
        }
        // The current file must be an XLSX drawing sub-file.
        if (strpos($otbsCurrFile, 'xl/drawings/')!==0) {
            return false;
        }

        if ($Pos==0) {
            // The parent element has already been found
            $PosEl = 0;
        } else {
            // Found  parent element
            $loc = TBSXmlLoc::FindStartTag($Txt, 'xdr:twoCellAnchor', $Pos, false);
            if ($loc===false) {
                return false;
            }
            $PosEl = $loc->PosBeg;
        }

        $loc = TBSXmlLoc::FindStartTag($Txt, 'xdr:to', $PosEl, true);
        if ($loc===false) {
            return false;
        }
        $p = $loc->PosBeg;

        $res = array();

        $el_lst = array('w'=>'xdr:colOff', 'h'=>'xdr:rowOff');
        foreach ($el_lst as $i => $el) {
            $loc = TBSXmlLoc::FindElement($Txt, $el, $p, true);
            if ($loc===false) {
                return false;
            }
            $beg =  $Offset + $loc->GetInnerStart();
            if ($beg>$FieldPos) {
                $beg = $beg - $FieldLen;
            }
            $val = $dim_inner[$i.'v'];
            $tval = $loc->GetInnerSrc();
            $res[$i.'b'] = $beg;
            $res[$i.'l'] = $loc->GetInnerLen();
            $res[$i.'u'] = '';
            $res[$i.'v'] = $val;
            $res[$i.'t'] = $tval;
            $res[$i.'o'] = intval($tval) - $val;
        }

        $res['r'] = ($res['hv']==0) ? 0.0 : $res['wv']/$res['hv']; // ratio W/H;
        $res['dec'] = 0;
        $res['cpt'] = 12700;

        return $res;
    }
}
