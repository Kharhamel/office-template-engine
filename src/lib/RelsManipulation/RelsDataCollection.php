<?php


namespace OfficeTemplateEngine\lib\RelsManipulation;

//this class storer the data of a .rels file
class RelsDataCollection
{

    /**
     * @var bool|array
     */
    public $ChartLst;
    /**
     * @var array
     */
    public $RidLst;
    /**
     * @var array
     */
    public $RidNew;
    /**
     * @var array
     */
    public $DirLst;
    /**
     * @var array
     */
    public $TargetLst;
    /**
     * @var string
     */
    public $FicPath;
    /**
     * @var int
     */
    public $FicType;
    public $FicTxt;
    public $ParentIdx;
    public $FicIdx;

    public function __construct()
    {
        $this->RidLst = [];    // Current Rids in the template ($Target=>$Rid)
        $this->TargetLst = []; // Current Targets in the template ($Rid=>$Target)
        $this->RidNew = [];    // New Rids to add at the end of the merge
        $this->DirLst = [];    // Processed target dir
        $this->ChartLst = false;    // Chart list, computed in another method
    }
}
