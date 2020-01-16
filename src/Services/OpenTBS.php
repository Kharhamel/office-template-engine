<?php

namespace OpenTBS\Services;

/**
 * Service for OpenTBS Bundle
 */
class OpenTBS extends \clsTinyButStrong
{
    public function __construct()
    {
        // construct the TBS class
        parent::__construct();

        // load the OpenTBS plugin
        $this->Plugin(TBS_INSTALL, OPENTBS_PLUGIN);
    }
}
