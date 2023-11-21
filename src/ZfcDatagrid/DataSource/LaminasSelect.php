<?php
namespace ZfcDatagrid\DataSource;

use Laminas\Db\Sql;
use Laminas\Db\Sql\Expression;
use Laminas\Paginator\Adapter\DbSelect as PaginatorAdapter;
use ZfcDatagrid\Column;
use function sprintf;

class LaminasSelect extends AbstractDataSource
{
    /** @var Sql\Select */
    private $select;

    /** @var Sql\Sql|null*/
    private $sqlObject;

    /**
     * @var LaminasSelect\Filter
     */
    private $filterColumn;

    /**
     * Data source.
     *
     * @param Sql\Select $data
     */
    public function __construct(Sql\Select $data)
    {
        $this->select = $data;
    }

    /**
     * @return Sql\Select
     */
    public function getData(): Sql\Select
    {
        return $this->select;
    }

    /**
     * @return LaminasSelect\Filter
     */
    public function getFilterColumn()
    {
        if (!$this->filterColumn) {
            $this->filterColumn = new LaminasSelect\Filter($this->getAdapter(), $this->select);
        }

        return $this->filterColumn;
    }

    /**
     * @param $adapterOrSqlObject
     *
     * @throws \InvalidArgumentException
     */
    public function setAdapter($adapterOrSqlObject)
    {
        if ($adapterOrSqlObject instanceof Sql\Sql) {
            $this->sqlObject = $adapterOrSqlObject;
        } elseif ($adapterOrSqlObject instanceof \Laminas\Db\Adapter\Adapter) {
            $this->sqlObject = new Sql\Sql($adapterOrSqlObject);
        } else {
            throw new \InvalidArgumentException('Object of "Laminas\Db\Sql\Sql" or "Laminas\Db\Adapter\Adapter" needed.');
        }

        return $this;
    }

    /**
     * @return Sql\Sql
     */
    public function getAdapter(): ?Sql\Sql
    {
        return $this->sqlObject;
    }

    /**
     * @throws \Exception
     */
    public function execute()
    {
        if ($this->getAdapter() === null || ! $this->getAdapter() instanceof \Laminas\Db\Sql\Sql) {
            throw new \Exception('Object "Laminas\Db\Sql\Sql" is missing, please call setAdapter() first!');
        }

        $platform = $this->getAdapter()
            ->getAdapter()
            ->getPlatform();

        $select = $this->getData();

        /*
         * Step 1) Apply needed columns
         */
        $selectColumns = [];
        foreach ($this->getColumns() as $col) {
            if (! $col instanceof Column\Select) {
                continue;
            }

            $colString = $col->getSelectPart1();
            if ($col->getSelectPart2() != '') {
                $colString = new Expression(
                    sprintf(
                        '%s%s%s',
                        $platform->quoteIdentifier($colString),
                        $platform->getIdentifierSeparator(),
                        $platform->quoteIdentifier($col->getSelectPart2())
                    )
                );
            }

            $selectColumns[$col->getUniqueId()] = $colString;
        }
        $select->columns($selectColumns, false);

        $joins = $select->getRawState('joins');
        $select->reset('joins');
        foreach ($joins as $join) {
            $select->join($join['name'], $join['on'], [], $join['type']);
        }

        /*
       * Step 2) Apply grouping
       */
        if (! empty($this->getGroupConditions())) {
            // Minimum one group condition given -> so reset the default groupBy
            $select->reset(Sql\Select::GROUP);

            foreach ($this->getGroupConditions() as $key => $col) {
                if (! $col instanceof Column\Select) {
                    throw new \Exception('This column cannot be grouped: ' . $col->getUniqueId());
                }

                /** @var \ZfcDataGrid\Column\AbstractColumn $col */
                $select->group($col->getUniqueId());
            }
        }

        /*
         * Step 2) Apply sorting
         */
        if (! empty($this->getSortConditions())) {
            // Minimum one sort condition given -> so reset the default orderBy
            $select->reset(Sql\Select::ORDER);

            foreach ($this->getSortConditions() as $sortCondition) {
                /** @var \ZfcDataGrid\Column\AbstractColumn $col */
                $col = $sortCondition['column'];
                $select->order($col->getUniqueId() . ' ' . $sortCondition['sortDirection']);
            }
        }

        /*
         * Step 3) Apply filters
         */
        $filterColumn = $this->getFilterColumn();
//        foreach ($this->getFilterGroup() as $filter) {
//            if ($filter->isColumnFilter() === true) {
//                $filterColumn->applyFilter($filter);
//            }
//        }
        $filterColumn->applyFilters($this->getFilterGroup());

        /*
         * Step 4) Pagination
         */
        $this->setPaginatorAdapter(new PaginatorAdapter($select, $this->getAdapter()));
    }
}
