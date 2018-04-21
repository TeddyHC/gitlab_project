<?php

namespace gitUpdate\config;

class Config
{
    public $baseUrl = 'www.gitlab.com';

    public $sshPort = '22';

    public $privateToken = '';

    public $apiVersion = 'v4';
    public $apiUrl;

    public $sshAlias = '';

    public $storePath = '$HOME/gitlab';

    public function __construct()
    {
        $this->apiUrl = 'https://'.$this->baseUrl.'/api/'.$this->apiVersion.'/';
    }
}
