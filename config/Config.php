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
        'foo/bar'
    ];

    public function __construct()
    {
        $this->apiUrl = 'https://'.$this->baseUrl.'/api/'.$this->apiVersion.'/';
    }

    public function getIgnoreProjects()
    {
        $ignores = [];
        foreach ($this->ignoreProjects as $vendor => $projects) {
            if (is_array($projects)) {
                foreach ($projects as $project) {
                    $ignores[] = $vendor.'/'.$project;
                }
            } else {
                $ignores[] = $projects;
            }
        }

        return $ignores;
    }
}
