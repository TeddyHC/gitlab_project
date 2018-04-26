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

    public $storePath = '/code';

    public $ignoreProjects = [
        'test' => [
            'foo',
        ],
    ];

    public function __construct()
    {
        $this->apiUrl = 'https://'.$this->baseUrl.'/api/'.$this->apiVersion.'/';
    }

    public function getIgnoreProjects()
    {
        $ignores = [];
        foreach ($ignoreProjects as $vendor => $projects) {
            foreach ($projects as $project) {
                $ignores[] = $vendor.'/'.$project;
            }
        }

        return $ignores;
    }
}
