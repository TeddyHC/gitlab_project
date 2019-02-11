<?php

namespace gitUpdate\src;

use gitUpdate\config\Config;
use gitUpdate\config\MyConfig;

/**
 * Class: gitUpdate.
 *
 * @Author HongChao <hongchao@mafengwo.com>
 */
class GitUpdate
{
    private $client;
    private $config;
    private $ignoreRules;

    /**
     * __construct.
     */
    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client();
        $this->_setConfig();
        $this->ignoreRules = $this->config->getIgnoreRules();
    }

    /**
     * _setConfig
     * 读取配置文件,自己的配置写到\config\MyConfig,继承Config.
     */
    private function _setConfig()
    {
        if (class_exists('gitUpdate\config\MyConfig', true)) {
            $this->config = new MyConfig();
        } else {
            $this->config = new Config();
        }
    }

    /**
     * @param null $target
     */
    public function run($namespace = null, $project = null)
    {
        // 检查存放目录是否存在
        $this->checkStock();

        $this->getProjects($namespace, $project);
    }

    /**
     * 检查有没有这个文件夹
     * 没有先创建.
     */
    private function checkStock()
    {
        if (!file_exists($this->config->storePath)) {
            shell_exec('mkdir -p '.$this->config->storePath);
        }
    }

    /**
     * @param $target
     */
    private function getProjects($namespace, $project)
    {
        $projectRepos = $this->getProjectRepos($namespace, $project);

        // 存在则更新，不存在clone
        $existedProjects = $newProject = [];
        foreach ($projectRepos as $namespace => $repo) {
            if ($this->_projectIsExist($namespace)) {
                $existedProjects[$namespace] = $repo;
            } else {
                $newProject[$namespace] = $repo;
            }
        }
        $this->updateExistedProjects($existedProjects);
        $this->cloneNewProjects($newProject);
    }

    private function getProjectRepos($namespace, $target)
    {
        // 获取所有可用项目
        $projects = $this->getGitLabProjects($target);

        return $this->_getProjectRepos($projects, $namespace);
    }

    /**
     * 获取所有项目.
     */
    private function getAll()
    {
        // 获取所有可用项目
        $projects = $this->getProjects();
        $projectRepos = $this->_getProjectRepos($projects);

        // 存在则更新，不存在clone
        $existedProjects = $newProject = [];
        foreach ($projectRepos as $namespace => $repo) {
            if ($this->_projectIsExist($namespace)) {
                $existedProjects[$namespace] = $repo;
            } else {
                $newProject[$namespace] = $repo;
            }
        }
        $this->updateExistedProjects($existedProjects);
        $this->cloneNewProjects($newProject);
    }

    /**
     * @param array $projects
     */
    private function updateExistedProjects(array $projects)
    {
        foreach ($projects as $namespace => $repo) {
            var_dump($namespace);
            $gitStatus = exec('cd '.$this->config->storePath.'/'.$namespace.'; git status');
            if ('nothing to commit, working tree clean' == $gitStatus
                || 'no changes added to commit (use "git add" and/or "git commit -a")' == $gitStatus
                || 'nothing added to commit but untracked files present (use "git add" to track)' == $gitStatus
            ) {
                $result = shell_exec('cd '.$this->config->storePath.'/'.$namespace.'; git checkout master; git pull');
                if ('Already up to date.' != $result) {
                    echo $result."\n";
                }
            }
        }
    }

    private function cloneNewProjects(array $projects)
    {
        foreach ($projects as $namespace => $repo) {
            passthru('cd '.$this->config->storePath.'; git clone '.$repo.' ./'.$namespace);
        }
    }

    private function _projectIsExist($project)
    {
        return file_exists($this->config->storePath.'/'.$project);
    }


    /**
     * 根据project接口返回值，拿到项目的vendor, projectName, ssh_url.
     *
     * @param array $projects 接口返回的项目数组
     *
     * @return array [vendor/projectName => $ssh_url, ...]
     */
    private function _getProjectRepos(array $projects, $namespace)
    {
        $ignores = $this->config->getIgnoreProjects();
        $projectNames = [];
        foreach ($projects as $project) {
            $vendor = $project['namespace'];
            if ($vendor['name'] != $namespace) {
                continue;
            }

            if (in_array($project['path_with_namespace'], $ignores)) {
                continue;
            }
            $repo = $project['ssh_url_to_repo'];

            // 根据ssh config 替换url
            if ($this->config->sshAlias) {
                $repo = str_replace(
                    'git@ssh.'.$this->config->baseUrl.':'.$this->config->sshPort,
                    $this->config->sshAlias,
                    $project['ssh_url_to_repo']
                );
            }
            $projectNames[$project['path_with_namespace']] = $repo;
        }

        return $projectNames;
    }

    public function needUpdate($vendor, $project)
    {
        foreach ($this->ignoreRules as $rule) {
            if (in_array($vendor)) {
            }
            if (1) {
            }
        }
        return true;
    }

    /**
     * 获取所有可见项目.
     *
     * @param null $target
     *
     * @return mixed
     */
    private function getGitLabProjects($target = null)
    {
        $url = $this->getUrl();
        if ($target) {
            $this->addSearch($url, $target);
        }
        $projects = [];

        // 循环取所有页数据
        try {
            $page = 1;
            do {
                $list = $this->_send($url, $page);
                $projects = array_merge($projects, $list);
                ++$page;
            } while (count($list));
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            var_dump($e);
        }

        return $projects;
    }

    /**
     * Get Url.
     *
     * @return string
     */
    private function getUrl()
    {
        $url = $this->config->apiUrl.'projects?';
        $this->_addPrivateToken($url);
        $this->addIsSimpleResponse($url);
        $this->_addOrderLimit($url);

        return $url;
    }

    /**
     * 请求一页数据
     * (为了不丢失数据，不提供pageSize参数, 保证每页请求条数一致).
     *
     * @param string $url  URL
     * @param int    $page 页号
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return mixed
     */
    private function _send($url, $page)
    {
        $this->addPageInfo($url, $page);
        $response = $this->client->request('GET', $url);

        return json_decode($response->getBody(), true);
    }

    /**
     * Url中加上在gitlab中生成的privateToken
     * (类似身份认证).
     *
     * @param string $url URL
     */
    private function _addPrivateToken(&$url)
    {
        $url .= '&private_token='.$this->config->privateToken;
    }

    /**
     * addIsSimpleResponse.
     *
     * @param string $url      URL
     * @param string $isSimple 返回一个简略的结果
     */
    private function addIsSimpleResponse(&$url, $isSimple = 'true')
    {
        $url .= '&simple='.$isSimple;
    }

    /**
     * addPageInfo.
     *
     * @param mixed $url
     * @param int   $page
     * @param int   $pageSize
     */
    private function addPageInfo(&$url, $page = 1, $pageSize = 100)
    {
        $url .= '&page='.$page.'&per_page='.$pageSize;
    }

    /**
     * _addOrderLimit.
     *
     * @param string $url     URL
     * @param string $orderBy 项目排序规则
     * @param string $sort    生序/降序 (asc/desc)
     */
    private function _addOrderLimit(&$url, $orderBy = 'id', $sort = 'asc')
    {
        $url .= '&order_by='.$orderBy.'&sort='.$sort;
    }

    /**
     * Search for projects by name.
     *
     * @param $url
     * @param $content
     */
    private function addSearch(&$url, $content)
    {
        $url .= '&search='.urlencode($content);
    }
}
