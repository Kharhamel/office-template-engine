<?php


namespace OfficeTemplateEngine\lib;

use OfficeTemplateEngine\lib\Locator\LocatorConfiguration;
use OfficeTemplateEngine\lib\PicturesManipulation\PicVariable;

class TBSLocator
{
    public $PosBeg = false;
    public $PosEnd = false;
    public $Enlarged = false;
    public $FullName = false;
    public $SubName = '';
    public $SubOk = false;
    public $SubLst = array();
    public $SubNbr = 0;
    /**
     * @var PicVariable
     */
    public $PrmLst;
    public $PrmIfNbr = false;
    public $MagnetId = false;
    public $BlockFound = false;
    public $FirstMerge = true;
    public $ConvProtect = true;
    public $ConvStr = true;
    /**
     * @var LocatorConfiguration
     */
    public $configuration;
    public $ConvBr = true;
    public $PosBeg0;
    public $DelPos;
    public $IsRecInfo;
    public $RecInfo;
    public $OnFrmInfo;
    /**
     * @var array
     */
    public $OnFrmArg;
    /**
     * @var array
     */
    public $OpeAct;
    /**
     * @var bool
     */
    public $Ope;
    /**
     * @var array
     */
    public $OpeArg;
    /**
     * @var bool
     */
    public $OpeUtf8;
    public $OpePrm;
    /**
     * @var bool
     */
    public $MSave;
    /**
     * @var string
     */
    public $OpeEnd;
    /**
     * @var array
     */
    public $OpeMKO;
    public $PosDefBeg;
    public $PosBeg2;
    public $PosEnd2;
    public $BlockSrc;
    public $PosDefEnd;
    /**
     * @var bool
     */
    public $P1;
    /**
     * @var bool
     */
    public $FieldOutside;
    /**
     * @var bool
     */
    public $FOStop;
    /**
     * @var array
     */
    public $BDefLst;
    /**
     * @var bool
     */
    public $HeaderFound;
    /**
     * @var bool
     */
    public $Special;
    /**
     * @var bool
     */
    public $FooterFound;
    /**
     * @var bool
     */
    public $SerialEmpty;
    /**
     * @var bool
     */
    public $GrpBreak;
    /**
     * @var bool
     */
    public $WhenFound;
    /**
     * @var bool
     */
    public $WhenDefault;
    /**
     * @var int
     */
    public $SectionNbr;
    /**
     * @var array
     */
    public $SectionLst;
    /**
     * @var bool
     */
    public $NoData;
    public $PrmIfVar;

    public function __construct()
    {
        $this->configuration = new LocatorConfiguration();
        $this->PrmLst = new PicVariable();
    }
}
