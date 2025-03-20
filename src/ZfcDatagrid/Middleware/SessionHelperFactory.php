<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-helpers for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-helpers/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZfcDatagrid\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Session\Container as SessionContainer;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Kernel;

class SessionHelperFactory
{
    /**
     * Create a RequestHelper instance.
     */
    public function __invoke(ContainerInterface $container)
    {
        /** @var RequestHelper $requestHelper */
        $requestHelper = $container->get(RequestHelper::class);
        
        $request = $requestHelper->getRequest();
        $session = RetrieveSession::fromRequest($request);

        return new RequestHelper($request);
    }
}
