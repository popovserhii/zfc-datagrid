<?php
namespace ZfcDatagrid;

class Module
{
    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\ClassMapAutoloader' => [
                __DIR__ . '/../../autoload_classmap.php',
            ],

            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ],
            ],
        ];
    }

    public function getConfig()
    {
        $config = include __DIR__ . '/../../config/module.config.php';
        if ($config['ZfcDatagrid']['renderer']['bootstrapTable']['daterange']['enabled'] === true) {
            $configNoCache = include __DIR__ . '/../../config/daterange.config.php';

            $config = array_merge_recursive($config, $configNoCache);
        }

        return $config;
    }
}
