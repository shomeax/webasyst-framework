<?php

class waAppViewHelper
{
    /**
     * @var waSystem
     */
    protected $wa;

    public function __construct($system)
    {
        $this->wa = $system;
    }

    public function pages($parent_id = 0, $with_params = true)
    {
        if (is_bool($parent_id)) {
            $with_params = $parent_id;
            $parent_id = 0;
        }
        try {
            $page_model = $this->getPageModel();
            $sql = "SELECT id, parent_id, name, title, full_url, url, create_datetime, update_datetime FROM ".$page_model->getTableName().'
                    WHERE status = 1 AND domain = s:domain AND route = s:route ORDER BY sort';
            $pages = $page_model->query($sql, array(
                'domain' => wa()->getRouting()->getDomain(null, true),
                'route' => wa()->getRouting()->getRoute('url')))->fetchAll('id');

            if ($with_params) {
                $page_params_model = $page_model->getParamsModel();
                $data = $page_params_model->getByField('page_id', array_keys($pages), true);
                foreach ($data as $row) {
                    if (isset($pages[$row['page_id']])) {
                        $pages[$row['page_id']][$row['name']] = $row['value'];
                    }
                }
            }
            // get current rool url
            $url = $this->wa->getAppUrl(null, true);

            foreach ($pages as &$page) {
                $page['url'] = $url.$page['full_url'];
                if (!isset($page['title']) || !$page['title']) {
                    $page['title'] = $page['name'];
                }
                foreach ($page as $k => $v) {
                    if ($k != 'content') {
                        $page[$k] = htmlspecialchars($v);
                    }
                }
            }
            unset($page);
            // make tree
            foreach ($pages as $page_id => $page) {
                if ($page['parent_id'] && isset($pages[$page['parent_id']])) {
                    $pages[$page['parent_id']]['childs'][] = &$pages[$page_id];
                }
            }
            foreach ($pages as $page_id => $page) {
                if ($page['parent_id']) {
                    unset($pages[$page_id]);
                }
            }
            if ($parent_id) {
                return isset($pages[$parent_id]['childs']) ? $pages[$parent_id]['childs'] : array();
            }
            return $pages;
        } catch (Exception $e) {
            return array();
        }
    }

    public function page($id)
    {
        $page_model = $this->getPageModel();
        $page = $page_model->getById($id);
        $page['content'] = $this->wa->getView()->fetch('string:'.$page['content']);

        $page_params_model = new sitePageParamsModel();
        $page += $page_params_model->getById($id);

        return $page;
    }


    /**
     * @return waPageModel
     */
    protected function getPageModel()
    {
        $class = $this->wa->getApp().'PageModel';
        return new $class();
    }

}