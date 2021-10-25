<?php

namespace ZfcDatagrid;

class FilterGroup
{
    const COND_AND = 'AND';
    const COND_OR = 'OR';

    protected $condition = self::COND_AND;

    protected $filters = [];

    protected $groups = [];

    /**
     * @param string $condition
     *
     * @return FilterGroup
     */
    public function setCondition(string $condition): FilterGroup
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * @return string
     */
    public function getCondition(): string
    {
        return $this->condition;
    }

    public function addFilter(Filter $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * @return Filter[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function addGroup(FilterGroup $filterGroup)
    {
        $this->groups[] = $filterGroup;
    }

    public function getGroups()
    {
        return $this->groups;
    }
}
