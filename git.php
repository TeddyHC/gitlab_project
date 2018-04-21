<?php
/**********************************************************
 * File Name: git.php
 * Author: Chao Hong <teddy.hongchao@gmail.com>
 * Program:
 * History: 一  3/12 20:00:17 2018
 **********************************************************/

namespace init;

require 'vendor/autoload.php';

// TODO:
// conposer 找一个git操作库
// 找一个v4接口，封装一个v4的接口库
// mac 定时任务模版&& md
// 记录切换前branch
// 暂存代码的模块
//

/**
 * Class: gitUpdate
 *
 * @Author HongChao <hongchao@mafengwo.com>
 */
class gitUpdate
{
    private $client;

    /**
     * __construct
     */
    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client();
    }

    public function run()
    {
        // 获取所有可用项目
        $projects = $this->_getProjects();
        $projectRepos = $this->_getProjectRepos($projects);

        foreach ($projectRepos as $namespace => $repo) {
            passthru('cd $HOME/code/gitlab; git clone '.$repo.' ./'.$namespace);
        }
        // 检查本地项目
        // 存在则更新，不存在clone
        /* $this->_updateProjects($projects); */
        /* $this->_cloneProjects($projects); */
    }

    private function _getProjectRepos($projects)
    {
        $projectNames = [];
        foreach ($projects as $project) {
            $repo = str_replace(
                'git@ssh.gitlab.mfwdev.com:333',
                'gitlab_mfw',
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
    private function _addPrivateToken(&$url, $privateToken = 'S-yzbrmjDF1N9WxjzuME')
    {
        $url .= '&private_token='.$privateToken;
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
        $baseUrl = 'https://gitlab.mfwdev.com/api/v4/';

        $url = $baseUrl.'projects?';
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

