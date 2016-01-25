<?php

namespace Application\Factory\Mapper;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\Hydrator\ClassMethods;
use Application\Mapper\PageDbSqlMapper;
use Application\Model\Page;
use Application\Utils\DbConsts\DbViewPages;

class PageDbSqlMapperFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator) {        
        $dbAdapter = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $hydrator = new ClassMethods();
        $namingStrat = new \Zend\Stdlib\Hydrator\NamingStrategy\MapNamingStrategy(array(
            DbViewPages::PAGEID => 'id',
            DbViewPages::PAGENAME => 'name',
            DbViewPages::REVISIONS => 'revisionCount'
        ));
        $hydrator->setNamingStrategy($namingStrat);
        $hydrator->addStrategy(DbViewPages::CREATIONDATE, new \Zend\Stdlib\Hydrator\Strategy\DateTimeFormatterStrategy('Y-m-d H:i:s'));
        $authorMapper = $serviceLocator->get('AuthorshipMapper');
        $revisionMapper = $serviceLocator->get('RevisionMapper');
        $voteMapper = $serviceLocator->get('VoteMapper');
        $prototype = new Page($authorMapper, $revisionMapper, $voteMapper);
        return new PageDbSqlMapper($dbAdapter, $hydrator, $prototype, DbViewPages::TABLE, DbViewPages::PAGEID);
    }
}
