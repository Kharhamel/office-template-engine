<?php

namespace OfficeTemplateEngine\Services;

use OfficeTemplateEngine\lib\OpenTBSPlugin;
use OfficeTemplateEngine\lib\TBSEngine;

/**
 * Service for OpenTBS Bundle
 */
class OpenTBS extends TBSEngine
{
    public function __construct()
    {
        // construct the TBS class
        parent::__construct();

        // load the OpenTBS plugin
        $this->Plugin(self::TBS_INSTALL, OpenTBSPlugin::class);
    }
}
