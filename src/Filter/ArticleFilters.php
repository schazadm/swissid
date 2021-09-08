<?php

namespace OSR\Training\Filter;

use PrestaShop\PrestaShop\Core\Search\Filters;

class ArticleFilters extends Filters
{
    public static function getDefaults()
    {
        return [
            'limit' => 10,
            'offset' => 0,
            'orderBy' => 'id_training_article',
            'sortOrder' => 'DESC',
            'filters' => []
        ];
    }
}