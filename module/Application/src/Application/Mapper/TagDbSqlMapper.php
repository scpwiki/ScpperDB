<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Application\Mapper;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Application\Utils\DbConsts\DbViewTags;
use Application\Utils\DbConsts\DbViewPages;

/**
 * Description of TagMapper
 *
 * @author Alexander
 */
class TagDbSqlMapper extends ZendDbSqlMapper implements TagMapperInterface
{
    /**
     * 
     * @param Sql $sql
     * @param int $siteId
     * @param int $conditions
     * @return Zend\Db\Sql\Select
     */
    protected function buildTagSelect(Sql $sql, $siteId, $deleted = false, $conditions = [])
    {
        if (is_array($conditions)) {
            $conditions = [];
        }
        $conditions[DbViewTags::SITEID.' = ?'] = $siteId;
        $conditions[DbViewTags::DELETED.' = ?'] = (int)$deleted;
        $select = $sql->select(['t' => DbViewTags::TABLE])
                ->columns([
                    DbViewTags::TAG,
                    'PageCount' => new Expression('COUNT(*)')
                ])
                ->where($conditions)
                ->group(DbViewTags::TAG);                
        return $select;        
    }
    
    /**
     * {@inheritDoc}
     */
    public function countSiteTags($siteId)
    {
        $sql = new Sql($this->dbAdapter);
        $select = $sql->select(DbViewTags::TABLE)
                ->columns([                    
                    'Number' => new Expression('COUNT(DISTINCT Tag)')
                ])
                ->where([
                    DbViewTags::SITEID.' = ?' => $siteId,
                    DbViewTags::DELETED.' <> 1'
                ]);
        $array = $this->fetchArray($sql, $select);
        if (count($array) > 0) {
            return $array[0]['Number'];
        }
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function findSiteTags($siteId, $paginated)
    {
        $sql = new Sql($this->dbAdapter);
        $select = $this->buildTagSelect($sql, $siteId, false);
        if ($paginated) {
            return $this->getPaginator($select);
        }        
        return $this->fetchResultSet($sql, $select);
    }

    /**
     * {@inheritDoc}
     */
    public function findPageTags($pageId)
    {
        $sql = new Sql($this->dbAdapter);
        $select = $sql->select(['t' => DbViewTags::TABLE])
                ->columns([
                    DbViewTags::TAG,
                    'PageCount' => new Expression('0')
                ])
                ->where([DbViewTags::PAGEID.' = ?' => $pageId])
                ->group(DbViewTags::TAG);                
        return $this->fetchResultSet($sql, $select);
    }    
    
    /**
     * {@inheritDoc}
     */
    public function findTag($siteId, $tag)
    {
        $sql = new Sql($this->dbAdapter);
        $select = $this->buildTagSelect($sql, $siteId, false, [DbViewTags::TAG.' = ?' => $tag]);
        return $this->fetchObject($sql, $select);                
    }
}
