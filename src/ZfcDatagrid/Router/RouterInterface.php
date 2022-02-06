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

interface RouterInterface
{
    /**
     * Assemble the route.
     *
     * @param array $params
     * @param array $options
     *
     * @return string
     */
    public function assemble(array $params = [], array $options = []);
}
