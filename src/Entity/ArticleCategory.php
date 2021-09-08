<?php

class ArticleCategory extends ObjectModel
{
    public $id;
    public $name;
    public $active;
    public $date_add;
    public $date_upd;
    public static $definition = [
        'table' => 'article_category',
        'primary' => 'id_article_category',
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
            'active' => ['type' => self::TYPE_BOOL],
            'date_add' => ['type' => self::TYPE_DATE],
            'date_upd' => ['type' => self::TYPE_DATE],
        ]
    ];
}