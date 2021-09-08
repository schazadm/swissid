<?php

namespace OSR\Training\Grid\QueryBuilder;

use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Core\Grid\Query\AbstractDoctrineQueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

class ArticleQueryBuilder extends AbstractDoctrineQueryBuilder
{
    private $contextLangId;

    public function __construct(Connection $connection, $dbPrefix, $contextLangId)
    {
        parent::__construct($connection, $dbPrefix);
        $this->contextLangId = $contextLangId;
    }

    /**
     * @inheritDoc
     */
    public function getSearchQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        return $this->getBaseQuery($searchCriteria->getFilters());
    }

    /**
     * @inheritDoc
     */
    public function getCountQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        return $this->getBaseQuery($searchCriteria->getFilters());
    }

    private function getBaseQuery(array $filters)
    {
        return $this->connection->createQueryBuilder()
            ->select('ta.id_training_article, tal.name')
            ->from($this->dbPrefix . 'training_article', 'ta')
            ->leftJoin(
                'ta',
                $this->dbPrefix . 'training_article_lang',
                'tal',
                'ta.id_training_article = tal.id_training_article AND tal.id_lang = :context_lang_id'
            )
            ->setParameter('context_lang_id', $this->contextLangId);
    }
}