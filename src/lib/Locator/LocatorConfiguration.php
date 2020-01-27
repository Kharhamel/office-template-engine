<?php


namespace OfficeTemplateEngine\lib\Locator;

class LocatorConfiguration
{
    public const ITEM_CONVERSION_MODE = -1; // special mode for item list conversion
    public const FORMAT_MODE = 0; // special mode for item list conversion
    public const NORMAL_MODE = 1;
    public const STRING_CONVERSION = 2; //Special string conversion
    
    public $ConvMode = self::NORMAL_MODE;
    /**
     * @var bool
     */
    public $ConvEsc;
    /**
     * @var bool
     */
    public $ConvWS;
    /**
     * @var bool
     */
    public $ConvJS;
    /**
     * @var bool
     */
    public $ConvUrl;
    /**
     * @var bool
     */
    public $ConvUtf8;

    public function confSpe(): void
    {
        if ($this->ConvMode!== self::STRING_CONVERSION) {
            $this->ConvMode = self::STRING_CONVERSION;
            $this->ConvEsc = false;
            $this->ConvWS = false;
            $this->ConvJS = false;
            $this->ConvUrl = false;
            $this->ConvUtf8 = false;
        }
    }
}
