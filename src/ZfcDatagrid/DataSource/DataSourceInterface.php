<?php
namespace ZfcDatagrid\DataSource;

use Laminas\Paginator\Adapter\AdapterInterface;
use ZfcDatagrid\Column;
use ZfcDatagrid\Filter;
use ZfcDatagrid\FilterGroup;

interface DataSourceInterface
{
    /**
     * Get the data back from construct.
     *
     * @return mixed
     */
    public function getData();

    /**
     * Execute the query and set the paginator
     * - with sort statements
     * - with filters statements.
     */
    public function execute();

    /**
     * Set the columns.
     *
     * @param array $columns
     *
     * @return $this
     */
    public function setColumns(array $columns): self;

    /**
     * Add sort condition.
     *
     * @param Column\AbstractColumn $column
     * @param string                $sortDirection
     *
     * @return $this
     */
    public function addSortCondition(Column\AbstractColumn $column, string $sortDirection = 'ASC'): self;

    /**
     * @param FilterGroup $filterGroup
     *
     * @return $this
     */
    public function setFilterGroup(FilterGroup $filterGroup): self;

    /**
     * @return AdapterInterface
     */
    public function getPaginatorAdapter(): ?AdapterInterface;
}
