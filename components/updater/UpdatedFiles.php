<?php

namespace app\components\updater;

class UpdatedFiles
{
    public array $files_added;
    public array $files_added_md5;
    public array $files_updated;
    public array $files_updated_md5;
    public array $files_deleted;
    public string $from_version;
    public string $to_version;
    public array $requirements;
    public string $changelog;

    public function __construct(string $json)
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
        $this->requirements      = $data['requirements'] ?? [];
    }
}
