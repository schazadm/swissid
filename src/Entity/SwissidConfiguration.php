<?php


class SwissidConfiguration extends ObjectModel
{
    /**
     * @var string Client identifier
     */
    public $client_id;

    /**
     * @var string Client secret
     */
    public $client_secret;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'swissid_configuration',
        'primary' => 'id_swissid_configuration',
        'fields' => [
            'client_id' => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 256],
            'client_secret' => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 256]
        ]
    ];
}