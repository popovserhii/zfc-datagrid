<?php
/**
 * The MIT License (MIT)
 * Copyright (c) 2025 Serhii Popov
 * This source file is subject to The MIT License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @category ZfcDatagrid
 * @author Serhii Popov <popow.serhii@gmail.com>
 * @license https://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace ZfcDatagrid\DataSource\LaminasSelect;

use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use ZfcDatagrid\Column;
use ZfcDatagrid\FilterGroup;

class Grouping
{
    /**
     * @var Sql
     */
    private $sql;

    /**
     * @var Select
     */
    private $select;

    /**
     * Filter constructor.
     * @param Sql $sql
     * @param Select $select
     */
    public function __construct(Sql $sql, Select $select)
    {
        $this->sql    = $sql;
        $this->select = $select;
    }

    /**
     * @return Sql
     */
    public function getSql(): ql
    {
        return $this->sql;
    }

    /**
     * @return Select
     */
    public function getSelect(): Select
    {
        return $this->select;
    }

    /**
     * Apply grouping
     *
     * @param $groups
     *
     * @return void
     * @throws \Exception
     */
    public function applyGroups($groups)
    {
        if (!$groups) {
            return;
        }

        $select = $this->getSelect();

        // Minimum one group condition given -> so reset the default groupBy
        $select->reset(Select::GROUP);

        foreach ($groups as $key => $col) {
            if (! $col instanceof Column\Select) {
                throw new \Exception('This column cannot be grouped: ' . $col->getUniqueId());
            }

            /** @var \ZfcDataGrid\Column\AbstractColumn $col */
            $select->group($col->getUniqueId());
        }
    }
}
