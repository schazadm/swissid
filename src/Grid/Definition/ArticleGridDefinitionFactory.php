<?php

namespace OSR\Training\Grid\Definition;

use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\AbstractFilterableGridDefinitionFactory;

class ArticleGridDefinitionFactory extends AbstractFilterableGridDefinitionFactory
{

    /**
     * @inheritDoc
     */
    protected function getId()
    {
        return _DB_PREFIX_ . 'training_article';
    }

    /**
     * @inheritDoc
     */
    protected function getName()
    {
        return 'Articles';
    }

    /**
     * @inheritDoc
     */
    protected function getColumns()
    {
        return (new ColumnCollection())
            ->add((new DataColumn('id_training_article'))
                ->setName('ID')
                ->setOptions([
                    'field' => 'id_training_article'
                ])
            )
            ->add((new DataColumn('name'))
                ->setName('Name')
                ->setOptions([
                    'field' => 'name'
                ])
            );
    }
}