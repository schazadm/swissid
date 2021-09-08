<?php

class TrainingArticle extends ObjectModel
{
    public $name;
    public $description;
    public $type;
    public $id_product;
    public static $definition = [
        'table' => 'training_article',
        'primary' => 'id_training_article',
        'multilang' => 'true',
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
            'description' => ['type' => self::TYPE_HTML, 'lang' => true, 'size' => 255],
            'type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 100],
            'id_product' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'size' => 10],
        ]
    ];
}