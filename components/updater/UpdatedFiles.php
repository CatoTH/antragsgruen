<?php

namespace app\components\updater;

class UpdatedFiles
{
    public $files_added;
    public $files_added_md5;
    public $files_updated;
    public $files_updated_md5;
    public $files_deleted;
    public $from_version;
    public $to_version;
    public $requirements;
    public $changelog;

    /**
     * UpdatedFiles constructor.
     * @param string $json
     */
    public function __construct($json)
    {
        $data                    = json_decode($json, true);
        $this->files_added       = $data['files_added'];
        $this->files_added_md5   = $data['files_added_md5'];
        $this->files_updated     = $data['files_updated'];
        $this->files_updated_md5 = $data['files_updated_md5'];
        $this->files_deleted     = $data['files_deleted'];
        $this->from_version      = $data['from_version'];
        $this->to_version        = $data['to_version'];
        $this->changelog         = $data['changelog'];
        $this->requirements      = (isset($data['requirements']) ? $data['requirements'] : []);
    }
}
