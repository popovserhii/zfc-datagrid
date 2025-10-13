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
use ZfcDatagrid\Column\Type;
use ZfcDatagrid\FilterGroup;
use ZfcDataGrid\Column\AbstractColumn;

class Sorting
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

        $qb = $this->getQueryBuilder();

        // Minimum one sort condition given -> so reset the default orderBy
        $qb->resetDQLPart('orderBy');

        foreach ($sorts as $key => $sortCondition) {
            /* @var $col AbstractColumn */
            $col = $sortCondition['column'];

            if (! $col instanceof Column\Select) {
                throw new \Exception('This column cannot be sorted: ' . $col->getUniqueId());
            }

            /* @var $col Column\Select */
            $colString = $col->getSelectPart1();
            if ($col->getSelectPart2() != '') {
                $colString .= '.' . $col->getSelectPart2();
            }

            if ($col->getType() instanceof Type\Number) {
                $qb->addSelect('ABS(' . $colString . ') sortColumn' . $key);
                $qb->add('orderBy', new Expr\OrderBy('sortColumn' . $key, $sortCondition['sortDirection']), true);
            } else {
                $qb->add('orderBy', new Expr\OrderBy($col->getUniqueId(), $sortCondition['sortDirection']), true);
            }
        }
    }
}
