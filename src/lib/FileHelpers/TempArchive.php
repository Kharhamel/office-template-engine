<?php


namespace OfficeTemplateEngine\lib\FileHelpers;

use OfficeTemplateEngine\Exceptions\OfficeTemplateEngineException;
use OfficeTemplateEngine\lib\TBSZip;

//this class represent the temp archive use to stock info the files
class TempArchive
{
    /**
     * @var bool
     */
    private $isNew;
    /**
     * @var bool
     */
    private $isStream;
    /**
     * @var string|null
     */
    public $fileName;
    /**
     * @var resource|null
     */
    public $handle;
    /**
     * @var bool|int
     */
    public $CdEndPos;
    /**
     * @var array
     */
    public $CdInfo;
    /**
     * @var DirectoryFileList
     */
    public $CdFileLst;
    public $CdPos;
    public $VisFileLst;
    public $AddInfo;
    /**
     * @var bool|int
     */
    private $LastReadComp;
    /**
     * @var bool
     */
    private $LastReadIdx;
    /**
     * @var array
     */
    public $ReplInfo;
    /**
     * @var array
     */
    public $ReplByPos;
    private $Meth8Ok;

    public function __construct($Meth8Ok)
    {
        $this->VisFileLst = [];
        $this->AddInfo = [];
        $this->CdFileLst = new DirectoryFileList();
        $this->Meth8Ok = $Meth8Ok;
    }

    //todo unused?
    public function createNew(string $ArchName = 'new.zip')
    {
        // Create a new virtual empty archive, the name will be the default name when the archive is flushed.
        $this->close(); // note that $this->ArchHnd is set to false here
        $this->fileName = $ArchName;
        $this->isNew = true;
        $bin = 'PK'.chr(05).chr(06).str_repeat(chr(0), 18);
        $this->CdEndPos = strlen($bin) - 4;
        $this->CdInfo = array('disk_num_curr'=>0, 'disk_num_cd'=>0, 'file_nbr_curr'=>0, 'file_nbr_tot'=>0, 'l_cd'=>0, 'p_cd'=>0, 'l_comm'=>0, 'v_comm'=>'', 'bin'=>$bin);
        $this->CdPos = $this->CdInfo['p_cd'];
    }

    /**
     * @param resource|string $ArchFile
     */
    public function open($ArchFile, $UseIncludePath = false): void
    {
        $this->close();
        $this->isNew = false;
        $this->isStream = (is_resource($ArchFile) && (get_resource_type($ArchFile)=='stream'));
        if ($this->isStream) {
            $this->fileName = 'from_stream.zip';
            $this->handle = $ArchFile;
        } else {
            // open the file
            $this->fileName = $ArchFile;
            $this->handle = @fopen($ArchFile, 'rb', $UseIncludePath); //todo cleaner solution
        }
        if (!$this->handle) {
            throw new OfficeTemplateEngineException('Could not init the archive');
        }
        $this->centralDirRead();
        if (!$this->fileName) {
            throw new OfficeTemplateEngineException('No filename');
        }
    }

    public function isStream(): bool
    {
        return $this->isStream;
    }
    
    private function centralDirRead(): void
    {
        $cd_info = 'PK'.chr(05).chr(06); // signature of the Central Directory
        $cd_pos = -22;
        $this->moveTo($cd_pos, SEEK_END);
        $b = $this->readData(4);
        if ($b===$cd_info) {
            $this->CdEndPos = ftell($this->handle) - 4;
        } else {
            $p = $this->findCDEnd($cd_info);
            if ($p===false) {
                throw new OfficeTemplateEngineException('The End of Central Directory Record is not found.');
            }
            $this->CdEndPos = $p;
            $this->moveTo($p+4);
        }
        $this->CdInfo = $this->centralDirReadEnd($cd_info);
        $this->CdFileLst->empty();
        $this->CdFileLst->setNumber($this->CdInfo['file_nbr_curr']);
        $this->CdPos = $this->CdInfo['p_cd'];

        if ($this->CdFileLst->getNumber()<=0) {
            throw new OfficeTemplateEngineException('No header found in the Central Directory.');
        }
        if ($this->CdPos<=0) {
            throw new OfficeTemplateEngineException('No position found for the Central Directory.');
        }

        $this->moveTo($this->CdPos);
        for ($i=0; $i<$this->CdFileLst->getNumber(); $i++) {
            $x = $this->centralDirReadFile($i);
            if ($x!==false) {
                $this->CdFileLst->set($i, $x);
            }
        }
    }

    public function readData(int $len): string
    {
        if (!($len>0)) {
            return '';
        }
        $x = fread($this->handle, $len);
        if ($x === false) {
            throw new OfficeTemplateEngineException('Could not read from the handle');
        }
        return $x;
    }

    public function moveTo($pos, $relative = SEEK_SET): void
    {
        fseek($this->handle, $pos, $relative);
    }

    /**
     * Search the record of end of the Central Directory.
     * Return the position of the record in the file.
     * Return false if the record is not found. The comment cannot exceed 65335 bytes (=FFFF).
     * The method is read backwards a block of 256 bytes and search the key in this block.
     */
    private function findCDEnd($cd_info)
    {
        $nbr = 1;
        $p = false;
        $pos = ftell($this->handle) - 4 - 256;
        while (($p===false) && ($nbr<256)) {
            if ($pos<=0) {
                $pos = 0;
                $nbr = 256; // in order to make this a last check
            }
            $this->moveTo($pos);
            $x = $this->readData(256);
            $p = strpos($x, $cd_info);
            if ($p===false) {
                $nbr++;
                $pos = $pos - 256 - 256;
            } else {
                return $pos + $p;
            }
        }
        return false;
    }
    
    private function centralDirReadEnd($cd_info): array
    {
        $b = $cd_info.$this->readData(18);
        $x = [];
        $x['disk_num_curr'] = getDec($b, 4, 2);  // number of this disk
        $x['disk_num_cd'] = getDec($b, 6, 2);    // number of the disk with the start of the central directory
        $x['file_nbr_curr'] = getDec($b, 8, 2);  // total number of entries in the central directory on this disk
        $x['file_nbr_tot'] = getDec($b, 10, 2);  // total number of entries in the central directory
        $x['l_cd'] = getDec($b, 12, 4);          // size of the central directory
        $x['p_cd'] = getDec($b, 16, 4);          // position of start of central directory with respect to the starting disk number
        $x['l_comm'] = getDec($b, 20, 2);        // .ZIP file comment length
        $x['v_comm'] = $this->readData($x['l_comm']); // .ZIP file comment
        $x['bin'] = $b.$x['v_comm'];
        return $x;
    }

    private function centralDirReadFile(int $idx): array
    {

        $b = $this->readData(46);

        $x = getHex($b, 0, 4);
        if ($x!=='h:02014b50') {
            throw new OfficeTemplateEngineException("Signature of Central Directory Header #$idx (file information) expected but not found at position ".txtPos(ftell($this->handle) - 46). '.');
        }

        $x = array();
        $x['vers_used'] = getDec($b, 4, 2);
        $x['vers_necess'] = getDec($b, 6, 2);
        $x['purp'] = getBin($b, 8, 2);
        $x['meth'] = getDec($b, 10, 2);
        $x['time'] = getDec($b, 12, 2);
        $x['date'] = getDec($b, 14, 2);
        $x['crc32'] = getDec($b, 16, 4);
        $x['l_data_c'] = getDec($b, 20, 4);
        $x['l_data_u'] = getDec($b, 24, 4);
        $x['l_name'] = getDec($b, 28, 2);
        $x['l_fields'] = getDec($b, 30, 2);
        $x['l_comm'] = getDec($b, 32, 2);
        $x['disk_num'] = getDec($b, 34, 2);
        $x['int_file_att'] = getDec($b, 36, 2);
        $x['ext_file_att'] = getDec($b, 38, 4);
        $x['p_loc'] = getDec($b, 42, 4);
        $x['v_name'] = $this->readData($x['l_name']);
        $x['v_fields'] = $this->readData($x['l_fields']);
        $x['v_comm'] = $this->readData($x['l_comm']);

        $x['bin'] = $b.$x['v_name'].$x['v_fields'].$x['v_comm'];

        return $x;
    }

    public function close(): void
    {
        if (isset($this->handle) && ($this->handle!==false)) {
            fclose($this->handle);
        }
        $this->fileName = null;
        $this->handle = null;
        $this->CdInfo = [];
        $this->CdFileLst->empty();
        $this->VisFileLst = array();
        $this->archCancelModif();
    }

    public function archCancelModif()
    {
        $this->LastReadComp = false; // compression of the last read file (1=compressed, 0=stored not compressed, -1= stored compressed but read uncompressed)
        $this->LastReadIdx = false;  // index of the last file read
        $this->ReplInfo = array();
        $this->ReplByPos = array();
        $this->AddInfo = array();
    }
    
    public function readFile($idx, $ReadData)
    {
        // read the file header (and maybe the data ) in the archive, assuming the cursor in at a new file position

        $b = $this->readData(30);

        $x = getHex($b, 0, 4);
        if ($x!=='h:04034b50') {
            return new OfficeTemplateEngineException("Signature of Local File Header #$idx (data section) expected but not found at position ".txtPos(ftell($this->handle)-30).".");
        }

        $x = array();
        $x['vers'] = getDec($b, 4, 2);
        $x['purp'] = getBin($b, 6, 2);
        $x['meth'] = getDec($b, 8, 2);
        $x['time'] = getDec($b, 10, 2);
        $x['date'] = getDec($b, 12, 2);
        $x['crc32'] = getDec($b, 14, 4);
        $x['l_data_c'] = getDec($b, 18, 4);
        $x['l_data_u'] = getDec($b, 22, 4);
        $x['l_name'] = getDec($b, 26, 2);
        $x['l_fields'] = getDec($b, 28, 2);
        $x['v_name'] = $this->readData($x['l_name']);
        $x['v_fields'] = $this->readData($x['l_fields']);

        $x['bin'] = $b.$x['v_name'].$x['v_fields'];

        // Read Data
        if ($this->CdFileLst->has($idx)) {
            $len_cd = $this->CdFileLst->getPropertyFromId($idx, 'l_data_c');
            if ($x['l_data_c']==0) {
                // Sometimes, the size is not specified in the local information.
                $len = $len_cd;
            } else {
                $len = $x['l_data_c'];
                if ($len!=$len_cd) {
                    //echo "TbsZip Warning: Local information for file #".$idx." says len=".$len.", while Central Directory says len=".$len_cd.".";
                }
            }
        } else {
            $len = $x['l_data_c'];
            if ($len==0) {
                throw new OfficeTemplateEngineException("File Data #".$idx." cannt be read because no length is specified in the Local File Header and its Central Directory information has not been found.");
            }
        }

        $Data = null;
        if ($ReadData) {
            $Data = $this->readData($len);
        } else {
            $this->moveTo($len, SEEK_CUR);
        }

        // Description information
        $desc_ok = ($x['purp'][2+3]=='1');
        if ($desc_ok) {
            $b = $this->readData(12);
            $s = getHex($b, 0, 4);
            $d = 0;
            // the specification says the signature may or may not be present
            if ($s=='h:08074b50') {
                $b .= $this->readData(4);
                $d = 4;
                $x['desc_bin'] = $b;
                $x['desc_sign'] = $s;
            } else {
                $x['desc_bin'] = $b;
            }
            $x['desc_crc32']    = getDec($b, 0+$d, 4);
            $x['desc_l_data_c'] = getDec($b, 4+$d, 4);
            $x['desc_l_data_u'] = getDec($b, 8+$d, 4);
        }

        // Save file info without the data
        $this->VisFileLst[$idx] = $x;

        // Return the info
        if ($ReadData) {
            return $Data;
        } else {
            return true;
        }
    }

    public function estimateNewArchSize($Optim = true)
    {
        // Return the size of the new archive, or false if it cannot be calculated (because of external file that must be compressed before to be insered)

        if ($this->isNew) {
            $Len = strlen($this->CdInfo['bin']);
        } elseif ($this->isStream) {
            $x = fstat($this->handle);
            $Len = $x['size'];
        } else {
            $Len = filesize($this->fileName);
        }

        // files to replace or delete
        foreach ($this->ReplByPos as $i) {
            $Ref =& $this->ReplInfo[$i];
            if ($Ref===false) {
                // file to delete
                $Info =& $this->CdFileLst->get($i);
                if (!isset($this->VisFileLst[$i])) {
                    if ($Optim) {
                        return false; // if $Optimization is set to true, then we d'ont rewind to read information
                    }
                    $this->moveTo($Info['p_loc']);
                    $this->readFile($i, false);
                }
                $Vis =& $this->VisFileLst[$i];
                $Len += -strlen($Vis['bin']) -strlen($Info['bin']) - $Info['l_data_c'];
                if (isset($Vis['desc_bin'])) {
                    $Len += -strlen($Vis['desc_bin']);
                }
            } elseif ($Ref['len_c']===false) {
                return false; // information not yet known
            } else {
                // file to replace
                $Len += $Ref['len_c'] + $Ref['diff'];
            }
        }

        // files to add
        $i_lst = array_keys($this->AddInfo);
        foreach ($i_lst as $i) {
            $Ref =& $this->AddInfo[$i];
            if ($Ref['len_c']===false) {
                return false; // information not yet known
            } else {
                $Len += $Ref['len_c'] + $Ref['diff'];
            }
        }

        return $Len;
    }

    public function editFileByExt(string $Ext): void
    {
        $this->fileName = str_replace('.zip', '.'.$Ext, $this->fileName);
    }
    
    public function fileRead($NameOrIdx, $Uncompress = true)
    {
        $this->LastReadComp = false; // means the file is not found
        $this->LastReadIdx = false;

        $idx = $this->CdFileLst->FileGetIdx($NameOrIdx);
        if ($idx===false) {
            throw new OfficeTemplateEngineException('File "'.$NameOrIdx.'" is not found in the Central Directory.');
        }

        $pos = $this->CdFileLst->getPropertyFromId($idx, 'p_loc');
        $this->moveTo($pos);

        $this->LastReadIdx = $idx; // Can be usefull to get the idx

        $Data = $this->readFile($idx, true);

        // Manage uncompression
        $Comp = 1; // means the contents stays compressed
        $meth = $this->CdFileLst->getPropertyFromId($idx, 'meth');
        if ($meth==8) {
            if ($Uncompress) {
                if ($this->Meth8Ok) {
                    $Data = gzinflate($Data);
                    $Comp = -1; // means uncompressed
                } else {
                    throw new OfficeTemplateEngineException('Unable to uncompress file "'.$NameOrIdx.'" because extension Zlib is not installed.');
                }
            }
        } elseif ($meth==0) {
            $Comp = 0; // means stored without compression
        } elseif ($Uncompress) {
            throw new OfficeTemplateEngineException('Unable to uncompress file "'.$NameOrIdx.'" because it is compressed with method '.$meth.'.');
        }
        $this->LastReadComp = $Comp;

        return $Data;
    }

    public function fileGetState($NameOrIdx)
    {

        $idx = $this->CdFileLst->fileGetIdx($NameOrIdx);
        if ($idx===false) {
            $idx = $this->fileGetIdxAdd($NameOrIdx);
            if ($idx===false) {
                return false;
            } else {
                return 'a';
            }
        } elseif (isset($this->ReplInfo[$idx])) {
            if ($this->ReplInfo[$idx]===false) {
                return 'd';
            } else {
                return 'm';
            }
        } else {
            return 'u';
        }
    }
    
    public function fileCancelModif($NameOrIdx, $ReplacedAndDeleted = true): int
    {
        // cancel added, modified or deleted modifications on a file in the archive
        // return the number of cancels

        $nbr = 0;

        if ($ReplacedAndDeleted) {
            // replaced or deleted files
            $idx = $this->CdFileLst->fileGetIdx($NameOrIdx);
            if ($idx!==false) {
                if (isset($this->ReplInfo[$idx])) {
                    $pos = $this->CdFileLst->getPropertyFromId($idx, 'p_loc');
                    unset($this->ReplByPos[$pos]);
                    unset($this->ReplInfo[$idx]);
                    $nbr++;
                }
            }
        }

        // added files
        $idx = $this->fileGetIdxAdd($NameOrIdx);
        if ($idx!==false) {
            unset($this->AddInfo[$idx]);
            $nbr++;
        }

        return $nbr;
    }
    
    public function fileReplace($NameOrIdx, $Data, $DataType = TBSZip::TBSZIP_STRING, $Compress = true)
    {
        // Store replacement information.

        $idx = $this->CdFileLst->FileGetIdx($NameOrIdx);
        if ($idx===false) {
            throw new OfficeTemplateEngineException('File "'.$NameOrIdx.'" is not found in the Central Directory.');
        }

        $pos = $this->CdFileLst->getPropertyFromId($idx, 'p_loc');

        if ($Data===false) {
            // file to delete
            $this->ReplInfo[$idx] = false;
            $Result = true;
        } else {
            // file to replace
            $Diff = - $this->CdFileLst->getPropertyFromId($idx, 'l_data_c');
            $Ref = $this->dataCreateNewRef($Data, $DataType, $Compress, $Diff, $NameOrIdx);
            $this->ReplInfo[$idx] = $Ref;
            $Result = $Ref['res'];
        }

        $this->ReplByPos[$pos] = $idx;

        return $Result;
    }
    
    private function dataCreateNewRef($Data, $DataType, $Compress, $Diff, $NameOrIdx): array
    {

        if (is_array($Compress)) {
            $result = 2;
            $meth = $Compress['meth'];
            $len_u = $Compress['len_u'];
            $crc32 = $Compress['crc32'];
            $Compress = false;
        } elseif ($Compress && ($this->Meth8Ok)) {
            $result = 1;
            $meth = 8;
            $len_u = false; // means unknown
            $crc32 = false;
        } else {
            $result = ($Compress) ? -1 : 0;
            $meth = 0;
            $len_u = false;
            $crc32 = false;
            $Compress = false;
        }

        if ($DataType==TBSZip::TBSZIP_STRING) {
            $path = false;
            if ($Compress) {
                // we compress now in order to save PHP memory
                $len_u = strlen($Data);
                $crc32 = crc32($Data);
                $Data = gzdeflate($Data);
                $len_c = strlen($Data);
            } else {
                $len_c = strlen($Data);
                if ($len_u===false) {
                    $len_u = $len_c;
                    $crc32 = crc32($Data);
                }
            }
        } else {
            $path = $Data;
            $Data = false;
            if (file_exists($path)) {
                $fz = filesize($path);
                if ($len_u===false) {
                    $len_u = $fz;
                }
                $len_c = ($Compress) ? false : $fz;
            } else {
                throw new OfficeTemplateEngineException("Cannot add the file '".$path."' because it is not found.");
            }
        }

        // at this step $Data and $crc32 can be false only in case of external file, and $len_c is false only in case of external file to compress
        return array('data'=>$Data, 'path'=>$path, 'meth'=>$meth, 'len_u'=>$len_u, 'len_c'=>$len_c, 'crc32'=>$crc32, 'diff'=>$Diff, 'res'=>$result);
    }

    public function fileAdd($Name, $Data, $DataType = TBSZip::TBSZIP_STRING, $Compress = true)
    {
        if ($Data===false) {
            return $this->fileCancelModif($Name, false); // Cancel a previously added file
        }

        // Save information for adding a new file into the archive
        $Diff = 30 + 46 + 2*strlen($Name); // size of the header + cd info
        $Ref = $this->dataCreateNewRef($Data, $DataType, $Compress, $Diff, $Name);
        $Ref['name'] = $Name;
        $this->AddInfo[] = $Ref;
        return $Ref['res'];
    }
    
    public function fileGetIdxAdd($Name)
    {
        // Check if a file name exists in the list of file to add, and return its index
        if (!is_string($Name)) {
            return false;
        }
        $idx_lst = array_keys($this->AddInfo);
        foreach ($idx_lst as $idx) {
            if ($this->AddInfo[$idx]['name']===$Name) {
                return $idx;
            }
        }
        return false;
    }
}
