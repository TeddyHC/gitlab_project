<?php

namespace gitUpdate\config;

class Config
{
    public $baseUrl = 'www.gitlab.com';

    public $apiVersion = 'v4';

    public $privateToken = '';

    // use default port (22)
    public $sshPort = '';

    public $sshAlias = '';

    public $storePath = '/code';

    public $ignoreProjects = [
        'test' => [
            'foo',
        ],
        'foo/bar'
    ];

    public $ignoreRules = [
        'foo/*',
        '*/bar',
    ];

    public $apiUrl;

    public function __construct()
    {
        $this->apiUrl = 'https://'.$this->baseUrl.'/api/'.$this->apiVersion.'/';
    }

    /**
     * @return array
     */
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

    public function getIgnoreRules()
    {
        $ignoreRules = [];
        foreach ($this->ignoreRules as $vendor => $rules) {
            if (is_array($rules)) {
                $ignoreRules[] = $vendor.'/'.$rules;
            } else {
                $ignoreRules[] = $rules;
            }
        }

        return $ignoreRules;
    }
}
