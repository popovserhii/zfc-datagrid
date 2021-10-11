<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-helpers for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-helpers/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZfcDatagrid\Middleware;

use Psr\Container\ContainerInterface;
use Laminas\Psr7Bridge\Psr7ServerRequest;
use Laminas\Http\Request as LaminasRequest;

class RequestHelperFactory
{
    /**
     * Create a RequestHelper instance.
     */
    public function __invoke(ContainerInterface $container)
    {
        $request = $container->has('application')
            ? $container->get('application')->getMvcEvent()->getRequest()
            : null;

        if ($request instanceof LaminasRequest) {
            $request = Psr7ServerRequest::fromLaminas($request);
        }

        return new RequestHelper($request);
    }
}
