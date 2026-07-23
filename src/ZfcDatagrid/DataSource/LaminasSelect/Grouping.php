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

use Laminas\Db\Sql\Select;
use Laminas\Db\Sql;
use ZfcDatagrid\Column;
use ZfcDatagrid\FilterGroup;
use ZfcDataGrid\Column\AbstractColumn;

class Grouping
{
    /**
     * @var Sql\Sql
     */
    private $sql;

    /**
     * @var Sql\Select
     */
    private $select;

    /**
     * Filter constructor.
     * @param Sql\Sql $sql
     * @param Sql\Select $select
     */
    public function __construct(Sql\Sql $sql, Sql\Select $select)
    {
        $this->sql    = $sql;
        $this->select = $select;
    }

    /**
     * @return Sql
     */
    public function getSql(): Sql\Sql
    {
        return $this->sql;
    }

    /**
     * @return Sql\Select
     */
    public function getSelect(): Sql\Select
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
        $select->reset(Sql\Select::GROUP);

        foreach ($groups as $key => $col) {
            if (! $col instanceof Column\Select) {
                throw new \Exception('This column cannot be grouped: ' . $col->getUniqueId());
            }

            /** @var AbstractColumn $col */
            $select->group($col->getUniqueId());
        }
    }
}
