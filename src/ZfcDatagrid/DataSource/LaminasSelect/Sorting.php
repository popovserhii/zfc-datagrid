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
use Laminas\Db\Sql\Sql;
use ZfcDatagrid\Column;
use ZfcDatagrid\FilterGroup;

class Sorting
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
    public function getSql(): Sql
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
     * Apply sorting
     *
     * @param $sorts
     *
     * @return void
     * @throws \Exception
     */
    public function applySorts($sorts)
    {
        if (!$sorts) {
            return;
        }

        $select = $this->getSelect();

        // Minimum one sort condition given -> so reset the default orderBy
        $select->reset(Sql\Select::ORDER);

        foreach ($sorts as $sortCondition) {
            /** @var \ZfcDataGrid\Column\AbstractColumn $col */
            $col = $sortCondition['column'];
            $select->order($col->getUniqueId() . ' ' . $sortCondition['sortDirection']);
        }
    }
}
