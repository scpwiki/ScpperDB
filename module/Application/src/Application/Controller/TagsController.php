<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Application\Service\HubServiceInterface;
use Application\Factory\Component\PaginatedTableFactory;
use Application\Utils\DbConsts\DbViewPages;
use Application\Utils\Order;


/**
 * Description of TagsController
 *
 * @author Alexander
 */
class TagsController extends AbstractActionController 
{

    /**
     *
     * @var Application\Service\ServiceHubInterface
     */    
    protected $services;

    protected function getPagesTable($siteId, $includeTags, $excludeTags, $all, $orderBy, $order, $page, $perPage)
    {
        $pages = $this->services->getPageService()->findPagesByTags($siteId, $includeTags, $excludeTags, $all, false, [$orderBy => $order], true, $page, $perPage);
        $pages->setCurrentPageNumber($page);
        $pages->setItemCountPerPage($perPage);
        $table = PaginatedTableFactory::createPagesTable($pages);
        $table->getColumns()->setOrder($orderBy, $order === Order::ASCENDING);        
        return $table;
    }    
    
    protected function extractTagParameters(&$method, &$tags, &$includeTags, &$excludeTags)
    {
        $method = $this->params()->fromQuery('method', 'and');
        $tagString = $this->params()->fromQuery('tags', '');
        $includeTags = [];
        $excludeTags = [];        
        if (strlen($tagString) > 0) {
            $tags = explode(',', $tagString);
            foreach ($tags as &$tag) {
                $tag = trim($tag);
                if (strlen($tag) === 0)
                    continue;
                if ($tag[0] === '+') {
                    $includeTags[]=substr($tag, 1);
                } else if ($tag[0] === '-') {
                    $excludeTags[]=substr($tag, 1);
                } else {
                    $includeTags[]=$tag;
                }
            }            
        } else {
            $tags = [];
        }        
    }
    
    public function __construct(HubServiceInterface $services) 
    {
        $this->services = $services;
    }
    
    public function tagsAction()
    {
        $siteId = $this->services->getUtilityService()->getSiteId();        
        $tagsResultSet = $this->services->getTagService()->findSiteTags($siteId);
        $this->extractTagParameters($method, $searchTags, $includeTags, $excludeTags);
        $pages = $this->getPagesTable($siteId, $includeTags, $excludeTags, $method==='and', DbViewPages::CLEANRATING, Order::DESCENDING, 1, 10);
        return new ViewModel([
            'siteId' => $siteId,
            'tags' => $tagsResultSet,
            'table' => $pages,
            'method' => $method,
            'searchTags' => $searchTags
        ]);
    }

    public function apiSearchAction()
    {
        $result = ['pages' => []];
        $siteName = $this->params()->fromQuery('site', 'en');        
        try {
            $site = $this->services->getSiteService()->findByShortName($siteName);
        } catch (\InvalidArgumentException $e) {
            $result['error'] = 'Invalid site';
            return new JsonModel($result);
        }
        $this->extractTagParameters($method, $searchTags, $includeTags, $excludeTags);
        $limit = filter_var($this->params()->fromQuery('limit', 50), FILTER_VALIDATE_INT);
        if (!$limit || ($limit < 1) || ($limit > 50)) {
            $limit = 50;
        }
        $randomize = $this->params()->fromQuery('random', 0);                
        if ($randomize) {
            $orderBy = 'random';
        } else {
            $orderBy = [DbViewPages::CLEANRATING => Order::DESCENDING];
        }
        $pages = $this->services->getPageService()->findPagesByTags($site->getId(), $includeTags, $excludeTags, $method==='and', false, $orderBy, true, 1, $limit);
        // $pages->setItemCountPerPage($limit);
        foreach ($pages as $page) {
            $result['pages'][] = $page->toArray();
        }
        return new JsonModel($result);
    }
       
    
    public function pageListAction()
    {
        $result = ['success' => false];
        $siteId = (int)$this->params()->fromQuery('siteId', $this->services->getUtilityService()->getSiteId());
        $page = (int)$this->params()->fromQuery('page', 1);
        $perPage = (int)$this->params()->fromQuery('perPage', 10);
        $orderBy = $this->params()->fromQuery('orderBy', DbViewPages::CLEANRATING);
        $order = $this->params()->fromQuery('ascending', true);
        if ($order) {
            $order = Order::ASCENDING;
        } else {
            $order = Order::DESCENDING;
        }
        $this->extractTagParameters($method, $searchTags, $includeTags, $excludeTags);
        $table = $this->getPagesTable($siteId, $includeTags, $excludeTags, $method==='and', $orderBy, $order, $page, $perPage);
        $renderer = $this->getServiceLocator()->get('ViewHelperManager')->get('partial');
        if ($renderer) {
            $result['success'] = true;                
            $result['content'] = $renderer(
                'partial/tables/default/table.phtml', 
                [
                    'table' => $table, 
                    'data' => ['siteId' => $siteId]
                ]
            );
        }
        return new JsonModel($result);        
    }
}
