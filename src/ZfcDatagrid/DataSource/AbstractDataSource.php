<?php
namespace ZfcDatagrid\DataSource;

use Laminas\Paginator\Adapter\AdapterInterface as PaginatorAdapterInterface;
use ZfcDatagrid\Column;
use ZfcDatagrid\Filter;
use ZfcDatagrid\FilterGroup;

abstract class AbstractDataSource implements DataSourceInterface
{
    /** @var Column\AbstractColumn[] */
    protected $columns = [];

    /** @var array */
    protected $groupConditions = [];

    /** @var array */
    protected $sortConditions = [];

    /** @var FilterGroup */
    protected $filterGroup = [];

    /**
     * The data result.
     *
     * @var PaginatorAdapterInterface|null
     */
    protected $paginatorAdapter;

    /**
     * Set the columns.
     *
     * @param Column\AbstractColumn[] $columns
     *
     * @return $this
     */
    public function setColumns(array $columns): DataSourceInterface
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @return Column\AbstractColumn[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Add sort condition.
     *
     * @param Column\AbstractColumn $column
     * @param string                $sortDirection
     *
     * @return $this
     */
    public function addSortCondition(Column\AbstractColumn $column, string $sortDirection = 'ASC'): DataSourceInterface
    {
        $this->sortConditions[] = [
            'column'        => $column,
            'sortDirection' => $sortDirection,
        ];

        return $this;
    }

    /**
     * @param array $sortConditions
     *
     * @return $this
     */
    public function setSortConditions(array $sortConditions): self
    {
        $this->sortConditions = $sortConditions;

        return $this;
    }

    /**
     * @return array
     */
    public function getSortConditions(): array
    {
        return $this->sortConditions;
    }

    /**
     * Add group condition.
     *
     * @param Column\AbstractColumn $column
     *
     * @return $this
     */
    public function addGroupCondition(Column\AbstractColumn $column): DataSourceInterface
    {
        $this->groupConditions[] = $column;

        return $this;
    }

    /**
     * @param array $groupConditions
     *
     * @return $this
     */
    public function setGroupConditions(array $groupConditions): self
    {
        $this->groupConditions = $groupConditions;

        return $this;
    }

    /**
     * @return array
     */
    public function getGroupConditions(): array
    {
        return $this->groupConditions;
    }

    /**
     * Add a filter rule.
     *
     * @param FilterGroup $filterGroup
     *
     * @return $this
     */
    public function setFilterGroup(FilterGroup $filterGroup): DataSourceInterface
    {
        $this->filterGroup = $filterGroup;

        return $this;
    }

    /**
     * @return FilterGroup
     */
    public function getFilterGroup()//: array
    {
        return $this->filterGroup;
    }

    /**
     * @param PaginatorAdapterInterface|null $paginator
     *
     * @return $this
     */
    public function setPaginatorAdapter(?PaginatorAdapterInterface $paginator): self
    {
        $this->paginatorAdapter = $paginator;

        return $this;
    }

    /**
     * @return PaginatorAdapterInterface
     */
    public function getPaginatorAdapter(): ?PaginatorAdapterInterface
    {
        return $this->paginatorAdapter;
    }

    /**
     * Get the data back from construct.
     *
     * @return mixed
     */
    abstract public function getData();

    /**
     * Execute the query and set the paginator
     * - with sort statements
     * - with filters statements.
     */
    abstract public function execute();
}
