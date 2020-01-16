<?php


namespace OpenTBS\lib;

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
    public $PrmLst = array();
    public $PrmIfNbr = false;
    public $MagnetId = false;
    public $BlockFound = false;
    public $FirstMerge = true;
    public $ConvProtect = true;
    public $ConvStr = true;
    public $ConvMode = 1; // Normal
    public $ConvBr = true;
}
