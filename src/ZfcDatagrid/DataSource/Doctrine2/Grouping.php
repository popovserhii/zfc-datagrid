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

namespace ZfcDatagrid\DataSource\Doctrine2;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM;
use Doctrine\ORM\Query\Expr;
use ZfcDatagrid\Column;
use ZfcDatagrid\FilterGroup;

class Grouping
{
    /**
     * @var QueryBuilder
     */
    private $qb;

    /**
     * @param QueryBuilder $qb
     */
    public function __construct(QueryBuilder $qb)
    {
        $this->qb = $qb;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->qb;
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

        $qb = $this->getQueryBuilder();

        // Minimum one group condition given -> so reset the default groupBy
        $qb->resetDQLPart('groupBy');

        foreach ($groups as $key => $col) {
            if (! $col instanceof Column\Select) {
                throw new \Exception('This column cannot be grouped: ' . $col->getUniqueId());
            }

            /* @var $col Column\Select */
            $colString = $col->getSelectPart1();
            if ($col->getSelectPart2() != '') {
                $colString .= '.' . $col->getSelectPart2();
            }

            //$qb->add('groupBy', new Expr\GroupBy($col->getUniqueId()), true);
            $qb->add('groupBy', new Expr\GroupBy($colString), true);
        }
    }
}
