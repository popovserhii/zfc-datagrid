<?php
namespace ZfcDatagrid\DataSource;

use Doctrine\ORM;
use Doctrine\ORM\Query\Expr;
use ZfcDatagrid\Column;
use ZfcDatagrid\Column\Type;
use ZfcDatagrid\DataSource\Doctrine2\Paginator as PaginatorAdapter;

class Doctrine2 extends AbstractDataSource
{
    /** @var ORM\QueryBuilder */
    private $qb;

    /**
     * @var Doctrine2\Filter
     */
    private $filterColumn;

    /**
     * Data source.
     *
     * @param ORM\QueryBuilder $data
     */
    public function __construct(ORM\QueryBuilder $data)
    {
        $this->qb = $data;
    }

    /**
     * @return ORM\QueryBuilder
     */
    public function getData(): ORM\QueryBuilder
    {
        return $this->qb;
    }

    public function getFilterColumn()
    {
        if (!$this->filterColumn) {
            $this->filterColumn = new Doctrine2\Filter($this->qb);
        }

        return $this->filterColumn;
    }

    /**
     * @throws \Exception
     */
    public function execute()
    {
        $qb = $this->getData();

        /*
         * Step 1) Apply needed columns
         */
        $selectColumns = [];
        foreach ($this->getColumns() as $column) {
            if (! $column instanceof Column\Select) {
                continue;
            }

            $colString = $column->getSelectPart1();
            if ($column->getSelectPart2() != '') {
                $colString .= '.' . $column->getSelectPart2();
            }
            $colString .= ' ' . $column->getUniqueId();

            $selectColumns[] = $colString;
        }
        $qb->resetDQLPart('select');
        $qb->select($selectColumns);

        /*
         * Step 2) Apply grouping
         */
        if (! empty($this->getGroupConditions())) {
            // Minimum one group condition given -> so reset the default groupBy
            $qb->resetDQLPart('groupBy');

            foreach ($this->getGroupConditions() as $key => $groupCondition) {
                $qb->add('groupBy', new Expr\GroupBy($groupCondition), true);
            }
        }

        /*
         * Step 3) Apply sorting
         */
        if (! empty($this->getSortConditions())) {
            // Minimum one sort condition given -> so reset the default orderBy
            $qb->resetDQLPart('orderBy');

            foreach ($this->getSortConditions() as $key => $sortCondition) {
                /* @var $col \ZfcDatagrid\Column\AbstractColumn */
                $col = $sortCondition['column'];

                if (! $col instanceof Column\Select) {
                    throw new \Exception('This column cannot be sorted: ' . $col->getUniqueId());
                }

                /* @var $col \ZfcDatagrid\Column\Select */
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

        /*
         * Step 4) Apply filters
         */
        $filterColumn = $this->getFilterColumn();
        /*foreach ($this->getFilters() as $filter) {
            if ($filter->isColumnFilter() === true) {
                $filterColumn->applyFilter($filter);
            }
        }*/
        //$filterColumn->applyFilter($this->getFilters());
        $filterColumn->applyFilters($this->getFilterGroup());

        /*
         * Step 5) Pagination
         */
        $this->setPaginatorAdapter(new PaginatorAdapter($qb));
    }
}
