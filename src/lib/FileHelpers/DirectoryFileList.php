<?php


namespace OfficeTemplateEngine\lib\FileHelpers;

//this class represent the list of files in the central directory
class DirectoryFileList
{
    /**
     * @var array
     */
    private $fileList;
    private $fileListByName;
    /**
     * @var int
     */
    private $fileNumber;

    public function __construct($fileList = [])
    {
        $this->fileList = $fileList;
        $this->fileListByName = [];
        $this->fileNumber = 0;
    }
    
    public function setNumber(int $number): void
    {
        $this->fileNumber = $number;
    }
    
    public function getNumber(): int
    {
        return $this->fileNumber;
    }
    
    public function empty(): void
    {
        $this->fileList = [];
        $this->fileListByName = [];
        $this->fileNumber = 0;
    }
    
    public function set($i, array $x): void
    {
        $this->fileList[$i] = $x;
        $this->fileListByName[$x['v_name']] = $i;
    }
    
    public function get($i): array
    {
        return $this->fileList[$i];
    }
    
    public function has($NameOrIdx): bool
    {
        return isset($this->fileList[$NameOrIdx]);
    }
    
    public function getPropertyFromId($idx, string $prop)
    {
        return $this->fileList[$idx][$prop];
    }

    public function getList(): array
    {
        return $this->fileList;
    }
    
    public function getByNameList(): array
    {
        return $this->fileListByName;
    }
    
    
    
    public function fileGetIdx($NameOrIdx)
    {
        // Check if a file name, or a file index exists in the Central Directory, and return its index
        if (is_string($NameOrIdx)) {
            if (isset($this->fileListByName[$NameOrIdx])) {
                return $this->fileListByName[$NameOrIdx];
            }
            return false;
        } else {
            if ($this->has($NameOrIdx)) {
                return $NameOrIdx;
            }
            return false;
        }
    }
}
