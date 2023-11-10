<?php

namespace Bsz\RecordTab;

class StaffViewAll extends \VuFind\RecordTab\AbstractBase
{
    protected $client;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->accessPermission = 'access.StaffViewTab';
    }

    /**
     * Get the on-screen description for this tab.
     *
     * @return string
     */
    public function getDescription()
    {
        return 'Staff View';
    }
}