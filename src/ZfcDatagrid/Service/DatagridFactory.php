<?php
namespace ZfcDatagrid\Service;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Cache\Storage\AdapterPluginManager;
use ZfcDatagrid\Datagrid;
use ZfcDatagrid\Middleware\RequestHelper;
use ZfcDatagrid\Router\RouterInterface;
use ZfcDatagrid\Translator\TranslatorInterface;

class DatagridFactory
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param array|null         $options
     *
     * @return Datagrid
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Datagrid
    {
        $config = $container->has('config')
            ? $container->get('config')
            : $container->getParameter('config');

        if (! isset($config['ZfcDatagrid'])) {
            throw new InvalidArgumentException('Config key "ZfcDatagrid" is missing');
        }

        /** @var RequestHelper $requestHelper */
        $requestHelper = $container->get(RequestHelper::class);

        $request = $requestHelper->getRequest();
        $router = $container->get(RouterInterface::class);

        $grid = new Datagrid();
        $grid->setOptions($config['ZfcDatagrid']);
        $grid->setRequest($request);
        $grid->setRouter($router);

        #$this->setCache(Cache\StorageFactory::factory($options['cache']));
        $cacheConfig = $config['ZfcDatagrid']['cache'];
//        $cacheConfig['adapter'] = $cacheConfig['adapter']['name'];
//        $adapterPluginManager = new AdapterPluginManager($container);
//        $cache = $adapterPluginManager->get(
//            $cacheConfig['adapter'],
//            $cacheConfig
//        );

        //$cache = new \Laminas\Cache\Storage\Adapter\Filesystem([], null);
        $cache = new \Laminas\Cache\Storage\Adapter\Filesystem();
        $cache->addPlugin(new \Laminas\Cache\Storage\Plugin\Serializer());

        $grid->setCache($cache);


        if (true === $container->has(TranslatorInterface::class)) {
            $grid->setTranslator($container->get(TranslatorInterface::class));
        }

        $grid->setRendererService($container->get('zfcDatagrid.renderer.' . $grid->getRendererName()));
        $grid->init();

        return $grid;
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @return Datagrid
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return $this($serviceLocator, Datagrid::class);
    }
}
