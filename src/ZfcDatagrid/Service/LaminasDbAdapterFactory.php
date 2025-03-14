<?php
namespace ZfcDatagrid\Service;

use Psr\Container\ContainerInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\ServiceManager\Factory\FactoryInterface;

class LaminasDbAdapterFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param array|null         $options
     *
     * @return Adapter
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): mixed
    {
        $config = $container->get('config');

        return new Adapter($config['zfcDatagrid_dbAdapter']);
    }
}
