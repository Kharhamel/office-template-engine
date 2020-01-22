<?php


namespace OfficeTemplateEngine\lib;

/*
TbsZip version 2.16
Date    : 2014-04-08
Author  : Skrol29 (email: http://www.tinybutstrong.com/onlyyou.html)
Licence : LGPL
This class is independent from any other classes and has been originally created for the OpenTbs plug-in
for TinyButStrong Template Engine (TBS). OpenTbs makes TBS able to merge OpenOffice and Ms Office documents.
Visit http://www.tinybutstrong.com
*/


use OfficeTemplateEngine\Exceptions\OfficeTemplateEngineException;
use OfficeTemplateEngine\lib\FileHelpers\DirectoryFileList;
use OfficeTemplateEngine\lib\FileHelpers\TempArchive;

class TBSZip
{
    public const TBSZIP_DOWNLOAD = 1;// download (default)
    public const TBSZIP_NOHEADER = 4;// option to use with DOWNLOAD: no header is sent
    public const TBSZIP_FILE = 8;// output to file  , or add from file
    public const TBSZIP_STRING = 32;// output to string, or add from string
    /**
     * @var bool
     */
    protected $Meth8Ok;
    /**
     * @var string
     */
    protected $OutputSrc;
    /**
     * @var bool|int
     */
    protected $LastReadComp;
    /**
     * @var bool
     */
    private $LastReadIdx;
    /**
     * @var string
     */
    private $OutputMode;
    private $OutputHandle;
    /**
     * @var TempArchive
     */
    protected $archive;

    public function __construct()
    {
        $this->Meth8Ok = extension_loaded('zlib'); // check if Zlib extension is available. This is need for compress and uncompress with method 8.
        $this->archive = new TempArchive($this->Meth8Ok);
    }

    public function fileAdd($Name, $Data, $DataType = self::TBSZIP_STRING, $Compress = true)
    {
        return $this->archive->fileAdd($Name, $Data, $DataType, $Compress);
    }

    public function raiseError(string $Msg, $NoErrMsg = false)
    {
        throw new OfficeTemplateEngineException($Msg);
    }

    function FileExists($NameOrIdx)
    {
        return ($this->FileGetIdx($NameOrIdx)!==false);
    }

    function FileGetIdx($NameOrIdx)
    {
        return $this->archive->CdFileLst->fileGetIdx($NameOrIdx);
    }

    function FileGetIdxAdd($Name)
    {
        return $this->archive->fileGetIdxAdd($Name);
    }

    function FileRead($NameOrIdx, $Uncompress = true)
    {
        return $this->archive->fileRead($NameOrIdx, $Uncompress);
    }

    function FileReplace($NameOrIdx, $Data, $DataType = self::TBSZIP_STRING, $Compress = true)
    {
        return $this->archive->fileReplace($NameOrIdx, $Data, $DataType, $Compress);
    }

    /**
     * Return the state of the file.
     * @return {string} 'u'=unchanged, 'm'=modified, 'd'=deleted, 'a'=added, false=unknown
     */
    function FileGetState($NameOrIdx)
    {
        return $this->archive->fileGetState($NameOrIdx);
    }
    
    public function flush($Render = self::TBSZIP_DOWNLOAD, $File = '', $ContentType = ''): bool
    {

        if (($File!=='') && ($this->archive->fileName===$File) && ($Render==self::TBSZIP_FILE)) {
            $this->raiseError('Method Flush() cannot overwrite the current opened archive: \''.$File.'\''); // this makes corrupted zip archives without PHP error.
            return false;
        }

        $ArchPos = 0;
        $Delta = 0;
        $FicNewPos = array();
        $DelLst = array(); // idx of deleted files
        $DeltaCdLen = 0; // delta of the CD's size

        $now = time();
        $date  = $this->_MsDos_Date($now);
        $time  = $this->_MsDos_Time($now);

        if (!$this->OutputOpen($Render, $File, $ContentType)) {
            return false;
        }

        // output modified zipped files and unmodified zipped files that are beetween them
        ksort($this->archive->ReplByPos);
        foreach ($this->archive->ReplByPos as $ReplPos => $ReplIdx) {
            // output data from the zip archive which is before the data to replace
            $this->OutputFromArch($ArchPos, $ReplPos);
            // get current file information
            if (!isset($this->archive->VisFileLst[$ReplIdx])) {
                $this->archive->readFile($ReplIdx, false);
            }
            $FileInfo =& $this->archive->VisFileLst[$ReplIdx];
            $b1 = $FileInfo['bin'];
            if (isset($FileInfo['desc_bin'])) {
                $b2 = $FileInfo['desc_bin'];
            } else {
                $b2 = '';
            }
            $info_old_len = strlen($b1) + $this->archive->CdFileLst->getPropertyFromId($ReplIdx, 'l_data_c') + strlen($b2); // $FileInfo['l_data_c'] may have a 0 value in some archives
            // get replacement information
            $ReplInfo =& $this->archive->ReplInfo[$ReplIdx];
            if ($ReplInfo===false) {
                // The file is to be deleted
                $Delta = $Delta - $info_old_len; // headers and footers are also deleted
                $DelLst[$ReplIdx] = true;
            } else {
                // prepare the header of the current file
                $this->_DataPrepare($ReplInfo); // get data from external file if necessary
                $this->_PutDec($b1, $time, 10, 2); // time
                $this->_PutDec($b1, $date, 12, 2); // date
                $this->_PutDec($b1, $ReplInfo['crc32'], 14, 4); // crc32
                $this->_PutDec($b1, $ReplInfo['len_c'], 18, 4); // l_data_c
                $this->_PutDec($b1, $ReplInfo['len_u'], 22, 4); // l_data_u
                if ($ReplInfo['meth']!==false) {
                    $this->_PutDec($b1, $ReplInfo['meth'], 8, 2); // meth
                }
                // prepare the bottom description if the zipped file, if any
                if ($b2!=='') {
                    $d = (strlen($b2)==16) ? 4 : 0; // offset because of the signature if any
                    $this->_PutDec($b2, $ReplInfo['crc32'], $d+0, 4); // crc32
                    $this->_PutDec($b2, $ReplInfo['len_c'], $d+4, 4); // l_data_c
                    $this->_PutDec($b2, $ReplInfo['len_u'], $d+8, 4); // l_data_u
                }
                // output data
                $this->OutputFromString($b1.$ReplInfo['data'].$b2);
                unset($ReplInfo['data']); // save PHP memory
                $Delta = $Delta + $ReplInfo['diff'] + $ReplInfo['len_c'];
            }
            // Update the delta of positions for zipped files which are physically after the currently replaced one
            for ($i=0; $i<$this->archive->CdFileLst->getNumber(); $i++) {
                if ($this->archive->CdFileLst->getPropertyFromId($i, 'p_loc')>$ReplPos) {
                    $FicNewPos[$i] = $this->archive->CdFileLst->getPropertyFromId($i, 'p_loc') + $Delta;
                }
            }
            // Update the current pos in the archive
            $ArchPos = $ReplPos + $info_old_len;
        }

        // Ouput all the zipped files that remain before the Central Directory listing
        if ($this->archive->handle!==false) {
            $this->OutputFromArch($ArchPos, $this->archive->CdPos); // ArchHnd is false if CreateNew() has been called
        }
        $ArchPos = $this->archive->CdPos;

        // Output file to add
        $AddNbr = count($this->archive->AddInfo);
        $AddDataLen = 0; // total len of added data (inlcuding file headers)
        if ($AddNbr>0) {
            $AddPos = $ArchPos + $Delta; // position of the start
            $AddLst = array_keys($this->archive->AddInfo);
            foreach ($AddLst as $idx) {
                $n = $this->_DataOuputAddedFile($idx, $AddPos);
                $AddPos += $n;
                $AddDataLen += $n;
            }
        }

        // Modifiy file information in the Central Directory for replaced files
        $b2 = '';
        $old_cd_len = 0;
        for ($i=0; $i<$this->archive->CdFileLst->getNumber(); $i++) {
            $b1 = $this->archive->CdFileLst->getPropertyFromId($i, 'bin');
            $old_cd_len += strlen($b1);
            if (!isset($DelLst[$i])) {
                if (isset($FicNewPos[$i])) {
                    $this->_PutDec($b1, $FicNewPos[$i], 42, 4);   // p_loc
                }
                if (isset($this->archive->ReplInfo[$i])) {
                    $ReplInfo =& $this->archive->ReplInfo[$i];
                    $this->_PutDec($b1, $time, 12, 2); // time
                    $this->_PutDec($b1, $date, 14, 2); // date
                    $this->_PutDec($b1, $ReplInfo['crc32'], 16, 4); // crc32
                    $this->_PutDec($b1, $ReplInfo['len_c'], 20, 4); // l_data_c
                    $this->_PutDec($b1, $ReplInfo['len_u'], 24, 4); // l_data_u
                    if ($ReplInfo['meth']!==false) {
                        $this->_PutDec($b1, $ReplInfo['meth'], 10, 2); // meth
                    }
                }
                $b2 .= $b1;
            }
        }
        $this->OutputFromString($b2);
        $ArchPos += $old_cd_len;
        $DeltaCdLen =  $DeltaCdLen + strlen($b2) - $old_cd_len;

        // Output until "end of central directory record"
        if ($this->archive->handle!==false) {
            $this->OutputFromArch($ArchPos, $this->archive->CdEndPos); // ArchHnd is false if CreateNew() has been called
        }

        // Output file information of the Central Directory for added files
        if ($AddNbr>0) {
            $b2 = '';
            foreach ($AddLst as $idx) {
                $b2 .= $this->archive->AddInfo[$idx]['bin'];
            }
            $this->OutputFromString($b2);
            $DeltaCdLen += strlen($b2);
        }

        // Output "end of central directory record"
        $b2 = $this->archive->CdInfo['bin'];
        $DelNbr = count($DelLst);
        if (($AddNbr>0) or ($DelNbr>0)) {
            // total number of entries in the central directory on this disk
            $n = getDec($b2, 8, 2);
            $this->_PutDec($b2, $n + $AddNbr - $DelNbr, 8, 2);
            // total number of entries in the central directory
            $n = getDec($b2, 10, 2);
            $this->_PutDec($b2, $n + $AddNbr - $DelNbr, 10, 2);
            // size of the central directory
            $n = getDec($b2, 12, 4);
            $this->_PutDec($b2, $n + $DeltaCdLen, 12, 4);
            $Delta = $Delta + $AddDataLen;
        }
        $this->_PutDec($b2, $this->archive->CdPos+$Delta, 16, 4); // p_cd  (offset of start of central directory with respect to the starting disk number)
        $this->OutputFromString($b2);

        $this->OutputClose();

        return true;
    }

    // ----------------
    // output functions
    // ----------------

    function OutputOpen($Render, $File, $ContentType)
    {

        if (($Render & self::TBSZIP_FILE)==self::TBSZIP_FILE) {
            $this->OutputMode = self::TBSZIP_FILE;
            if (''.$File=='') {
                $File = basename($this->archive->fileName).'.zip';
            }
            $this->OutputHandle = @fopen($File, 'w');
            if ($this->OutputHandle===false) {
                return $this->raiseError('Method Flush() cannot overwrite the target file \''.$File.'\'. This may not be a valid file path or the file may be locked by another process or because of a denied permission.');
            }
        } elseif (($Render & self::TBSZIP_STRING)==self::TBSZIP_STRING) {
            $this->OutputMode = self::TBSZIP_STRING;
            $this->OutputSrc = '';
        } elseif (($Render & self::TBSZIP_DOWNLOAD)==self::TBSZIP_DOWNLOAD) {
            $this->OutputMode = self::TBSZIP_DOWNLOAD;
            // Output the file
            if (''.$File=='') {
                $File = basename($this->archive->fileName);
            }
            if (($Render & self::TBSZIP_NOHEADER)==self::TBSZIP_NOHEADER) {
            } else {
                header('Pragma: no-cache');
                if ($ContentType!='') {
                    header('Content-Type: '.$ContentType);
                }
                header('Content-Disposition: attachment; filename="'.$File.'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Cache-Control: public');
                header('Content-Description: File Transfer');
                header('Content-Transfer-Encoding: binary');
                $Len = $this->archive->estimateNewArchSize();
                if ($Len!==false) {
                    header('Content-Length: '.$Len);
                }
            }
        } else {
            return $this->raiseError('Method Flush is called with a unsupported render option.');
        }

        return true;
    }

    function OutputFromArch($pos, $pos_stop)
    {
        $len = $pos_stop - $pos;
        if ($len<0) {
            return;
        }
        $this->archive->moveTo($pos);
        $block = 1024;
        while ($len>0) {
            $l = min($len, $block);
            $x = $this->readData($l);
            $this->OutputFromString($x);
            $len = $len - $l;
        }
        unset($x);
    }

    function OutputFromString($data)
    {
        if ($this->OutputMode===self::TBSZIP_DOWNLOAD) {
            echo $data; // donwload
        } elseif ($this->OutputMode===self::TBSZIP_STRING) {
            $this->OutputSrc .= $data; // to string
        } elseif ($this->OutputMode===self::TBSZIP_FILE) {
            fwrite($this->OutputHandle, $data); // to file
        }
    }

    function OutputClose()
    {
        if (($this->OutputMode===self::TBSZIP_FILE) && ($this->OutputHandle!==false)) {
            fclose($this->OutputHandle);
            $this->OutputHandle = false;
        }
    }

    // ----------------
    // Reading functions
    // ----------------

    private function readData(int $len): string
    {
        return $this->archive->readData($len);
    }

    // ----------------
    // Put info into binary data
    // ----------------

    function _PutDec(&$txt, $val, $pos, $len)
    {
        $x = '';
        for ($i=0; $i<$len; $i++) {
            if ($val==0) {
                $z = 0;
            } else {
                $z = intval($val % 256);
                if (($val<0) && ($z!=0)) { // ($z!=0) is very important, example: val=-420085702
                    // special opration for negative value. If the number id too big, PHP stores it into a signed integer. For example: crc32('coucou') => -256185401 instead of  4038781895. NegVal = BigVal - (MaxVal+1) = BigVal - 256^4
                    $val = ($val - $z)/256 -1;
                    $z = 256 + $z;
                } else {
                    $val = ($val - $z)/256;
                }
            }
            $x .= chr($z);
        }
        $txt = substr_replace($txt, $x, $pos, $len);
    }

    function _MsDos_Date($Timestamp = false)
    {
        // convert a date-time timstamp into the MS-Dos format
        $d = ($Timestamp===false) ? getdate() : getdate($Timestamp);
        return (($d['year']-1980)*512) + ($d['mon']*32) + $d['mday'];
    }
    function _MsDos_Time($Timestamp = false)
    {
        // convert a date-time timstamp into the MS-Dos format
        $d = ($Timestamp===false) ? getdate() : getdate($Timestamp);
        return ($d['hours']*2048) + ($d['minutes']*32) + intval($d['seconds']/2); // seconds are rounded to an even number in order to save 1 bit
    }

    function _MsDos_Debug($date, $time)
    {
        // Display the formated date and time. Just for debug purpose.
        // date end time are encoded on 16 bits (2 bytes) : date = yyyyyyymmmmddddd , time = hhhhhnnnnnssssss
        $y = ($date & 65024)/512 + 1980;
        $m = ($date & 480)/32;
        $d = ($date & 31);
        $h = ($time & 63488)/2048;
        $i = ($time & 1984)/32;
        $s = ($time & 31) * 2; // seconds have been rounded to an even number in order to save 1 bit
        return $y.'-'.str_pad($m, 2, '0', STR_PAD_LEFT).'-'.str_pad($d, 2, '0', STR_PAD_LEFT).' '.str_pad($h, 2, '0', STR_PAD_LEFT).':'.str_pad($i, 2, '0', STR_PAD_LEFT).':'.str_pad($s, 2, '0', STR_PAD_LEFT);
    }

    function _DataOuputAddedFile($Idx, $PosLoc)
    {

        $Ref =& $this->archive->AddInfo[$Idx];
        $this->_DataPrepare($Ref); // get data from external file if necessary

        // Other info
        $now = time();
        $date  = $this->_MsDos_Date($now);
        $time  = $this->_MsDos_Time($now);
        $len_n = strlen($Ref['name']);
        $purp  = 2048 ; // purpose // +8 to indicates that there is an extended local header

        // Header for file in the data section
        $b = 'PK'.chr(03).chr(04).str_repeat(' ', 26); // signature
        $this->_PutDec($b, 20, 4, 2); //vers = 20
        $this->_PutDec($b, $purp, 6, 2); // purp
        $this->_PutDec($b, $Ref['meth'], 8, 2);  // meth
        $this->_PutDec($b, $time, 10, 2); // time
        $this->_PutDec($b, $date, 12, 2); // date
        $this->_PutDec($b, $Ref['crc32'], 14, 4); // crc32
        $this->_PutDec($b, $Ref['len_c'], 18, 4); // l_data_c
        $this->_PutDec($b, $Ref['len_u'], 22, 4); // l_data_u
        $this->_PutDec($b, $len_n, 26, 2); // l_name
        $this->_PutDec($b, 0, 28, 2); // l_fields
        $b .= $Ref['name']; // name
        $b .= ''; // fields

        // Output the data
        $this->OutputFromString($b.$Ref['data']);
        $OutputLen = strlen($b) + $Ref['len_c']; // new position of the cursor
        unset($Ref['data']); // save PHP memory

        // Information for file in the Central Directory
        $b = 'PK'.chr(01).chr(02).str_repeat(' ', 42); // signature
        $this->_PutDec($b, 20, 4, 2);  // vers_used = 20
        $this->_PutDec($b, 20, 6, 2);  // vers_necess = 20
        $this->_PutDec($b, $purp, 8, 2);  // purp
        $this->_PutDec($b, $Ref['meth'], 10, 2); // meth
        $this->_PutDec($b, $time, 12, 2); // time
        $this->_PutDec($b, $date, 14, 2); // date
        $this->_PutDec($b, $Ref['crc32'], 16, 4); // crc32
        $this->_PutDec($b, $Ref['len_c'], 20, 4); // l_data_c
        $this->_PutDec($b, $Ref['len_u'], 24, 4); // l_data_u
        $this->_PutDec($b, $len_n, 28, 2); // l_name
        $this->_PutDec($b, 0, 30, 2); // l_fields
        $this->_PutDec($b, 0, 32, 2); // l_comm
        $this->_PutDec($b, 0, 34, 2); // disk_num
        $this->_PutDec($b, 0, 36, 2); // int_file_att
        $this->_PutDec($b, 0, 38, 4); // ext_file_att
        $this->_PutDec($b, $PosLoc, 42, 4); // p_loc
        $b .= $Ref['name']; // v_name
        $b .= ''; // v_fields
        $b .= ''; // v_comm

        $Ref['bin'] = $b;

        return $OutputLen;
    }

    function _DataPrepare(&$Ref)
    {
        // returns the real size of data
        if ($Ref['path']!==false) {
            $Ref['data'] = file_get_contents($Ref['path']);
            if ($Ref['crc32']===false) {
                $Ref['crc32'] = crc32($Ref['data']);
            }
            if ($Ref['len_c']===false) {
                // means the data must be compressed
                $Ref['data'] = gzdeflate($Ref['data']);
                $Ref['len_c'] = strlen($Ref['data']);
            }
        }
    }
}
