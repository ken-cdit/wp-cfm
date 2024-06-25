<?php

class WPCFM_ReadWriteOverrides extends WPCFM_Readwrite {

    function __construct()
    {
        $this->folder = WPCFM_OVERRIDES_DIR;
    }

    // Disable functionality that writes to filesystem.
    function push_bundle($bundle_name)
    {
        return;
    }

    function write_file($bundle_name, $data) {
        return;
    }

    function delete_file($bundle_name) {
        return;
    }
}