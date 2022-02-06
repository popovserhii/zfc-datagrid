<?php
/**
 * The MIT License (MIT)
 * Copyright (c) 2021 ZfcDatagrid
 * This source file is subject to The MIT License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @category ZfcDatagrid
 * @author Serhii Popov <popow.serhii@gmail.com>
 * @license https://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace ZfcDatagrid\Router;

use Laminas\Router\RouteStackInterface;

/**
 * Adapter for Laminas Router
 */
class Router implements RouterInterface
{
    /**
     * @var RouteStackInterface
     */
    protected $router;

    public function __construct(RouteStackInterface $router)
    {
        $this->router = $router;
    }

    public function assemble(array $params = [], array $options = [])
    {
        return $this->router->assemble($params, $options);
    }
}
