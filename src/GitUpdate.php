<?php

namespace gitUpdate\src;

use gitUpdate\config\Config;
use gitUpdate\config\MyConfig;

/**
 * Class: gitUpdate
 *
 * @Author HongChao <hongchao@mafengwo.com>
 */
class GitUpdate
{
    private $client;
    private $config;

    /**
     * __construct
     */
    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client();
        $this->_setConfig();
    }

    private function _setConfig()
    {
        if (class_exists('gitUpdate\config\MyConfig', true)) {
            $this->config = new MyConfig();
        } else {
            $this->config = new Config();
        }
    }

    public function run()
    {
        // 获取所有可用项目
        $projects = $this->_getProjects();
        $projectRepos = $this->_getProjectRepos($projects);

        foreach ($projectRepos as $namespace => $repo) {
            var_dump($namespace.' '.$repo);
            /* passthru('cd $HOME/code/gitlab; git clone '.$repo.' ./'.$namespace); */
        }
        // 检查本地项目
        // 存在则更新，不存在clone
        /* $this->_updateProjects($projects); */
        /* $this->_cloneProjects($projects); */
    }

    private function _projectIsExist($repo)
    {
        return file_exists($this->config->storePath.'/'.$repo);
    }

    public function checkStock()
    {
        if (!file_exists($this->config->storePath)) {
            shell_exec('mkdir -p '.$this->config->storePath);
        }
    }

    private function _getProjectRepos($projects)
    {
        $projectNames = [];
        foreach ($projects as $project) {
            $repo = str_replace(
                'git@ssh.'.$this->config->baseUrl.':'.$this->config->sshPort,
                $this->config->sshAlias,
                $project['ssh_url_to_repo']
            );
            $projectNames[$project['path_with_namespace']] = $repo;
        }

        return $projectNames;
    }

    private function _getExistProject()
    {
        $projectString = shell_exec('cd ..; ls -d *');
        $projects = explode(PHP_EOL, $projectString);
        //delete last null string
        array_pop($projects);

        return $projects;
    }

    private function _updateProjects($projects)
    {
        foreach ($projects as $project) {
            $command = 'cd ../'.$project.'; git pull; cd -';
            passthru($command);
        }
    }

    /**
     * _addPrivateToken
     *
     * @param mixed $url
     * @param string $privateToken
     *
     * @return mixed
     */
    private function _addPrivateToken(&$url)
    {
        $url .= '&private_token='.$this->config->privateToken;
    }

    /**
     * _addIsSimpleResponse
     *
     * @param mixed $url
     * @param string $isSimple
     *
     * @return mixed
     */
    private function _addIsSimpleResponse(&$url, $isSimple = 'true')
    {
        $url .= '&simple='.$isSimple;
    }

    /**
     * _addPageInfo
     *
     * @param mixed $url
     * @param int $page
     * @param int $pageSize
     *
     * @return mixed
     */
    private function _addPageInfo(&$url, $page = 1, $pageSize = 100)
    {
        $url .= '&page='.$page.'&per_page='.$pageSize;
    }

    private function _addOrderLimit(&$url, $orderBy = 'id', $sort = 'asc')
    {
        $url .= '&order_by='.$orderBy.'&sort='.$sort;
    }

    private function _getUrl()
    {
        $url = $this->config->apiUrl.'projects?';
        $this->_addPrivateToken($url);
        $this->_addIsSimpleResponse($url);
        $this->_addOrderLimit($url);

        return $url;
    }

    private function _getProjects()
    {
        $url = $this->_getUrl();
        $projects = [];

        $page = 1;
        do {
            $list = $this->_send($url, $page);
            $projects = array_merge($projects, $list);
            ++$page;
        } while (count($list));

        return $projects;
    }

    private function _send($url, $page)
    {
        $this->_addPageInfo($url, $page);
        $response = $this->client->request('GET', $url);

        return json_decode($response->getBody(), true);
    }
}

