<?php

namespace app\components\updater;

class UpdatedFiles
{
    public $files_updated;
    public $files_added;
    public $files_deleted;
    public $from_version;
    public $to_version;
    public $changelog;

    /**
     * UpdatedFiles constructor.
     * @param string $json
     */
    public function __construct($json)
    {
        $data                = json_decode($json, true);
        $this->files_added   = $data['files_added'];
        $this->files_updated = $data['files_updated'];
        $this->files_deleted = $data['files_deleted'];
        $this->from_version  = $data['from_version'];
        $this->to_version    = $data['to_version'];
        $this->changelog     = $data['changelog'];
    }
}
