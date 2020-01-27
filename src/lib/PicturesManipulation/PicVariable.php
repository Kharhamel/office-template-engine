<?php


namespace OfficeTemplateEngine\lib\PicturesManipulation;

//this class represent a picture variable with all the data needed for its injection
use ArrayAccess;

class PicVariable implements ArrayAccess
{
    /**
     * @var string|null
     */
    public $ope;
    /**
     * @var bool
     */
    public $pic_prepared;
    /**
     * @var string|null
     */
    public $from;
    public $default;
    public $att;
    /**
     * @var string | null
     */
    public $as;
    public $colnum;
    public $colshift;
    public $cellok;
    public $tagpos;
    public $adjust;
    public $unique;

    public function __construct($data = [])
    {
        /*$this->ope = $data['ope'] ?? null;
        $this->pic_prepared = (bool) ($data['pic_prepared'] ?? false);
        $this->from = $data['from'] ?? null;
        $this->default = $data['default'] ?? null;
        $this->att = $data['att'] ?? null;
        $this->as = $data['as'] ?? null;
        $this->colnum = $data['colnum'] ?? null;
        $this->colshift = $data['colshift'] ?? null;*/
        foreach ($data as $key => $datum) {
            $this->{$key} = $datum;
        }
    }
    
    public function offsetExists($offset)
    {
        return isset($this->{$offset});
    }
    
    public function offsetGet($offset)
    {
        return $this->{$offset};
    }

    public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }

    public function offsetUnset($offset)
    {
        $this->{$offset} = null;
    }
}
