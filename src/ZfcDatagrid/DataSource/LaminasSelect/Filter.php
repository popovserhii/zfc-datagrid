<?php
namespace ZfcDatagrid\DataSource\LaminasSelect;

use Laminas\Db\Sql\Predicate\PredicateSet;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Where;
use ZfcDatagrid\Column;
use ZfcDatagrid\Filter as DatagridFilter;
use ZfcDatagrid\FilterGroup;
use function sprintf;

class Filter
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

    public function applyFilters($filterGroup)
    {
        if (!$filterGroup) {
            return;
        }

        $this->applyFilter($filterGroup);

        /*$qb = $this->getQueryBuilder();
        if ($expr = $this->applyFilter($filterGroup)) {
            FilterGroup::COND_AND === $filterGroup->getCondition()
                ? $qb->andWhere($expr)
                : $qb->orWhere($expr);
        }*/
    }
    
    /**
     * @param FilterGroup $filterGroup
     *
     * @return $this
     * @throws \Exception
     */
    public function applyFilter(/*DatagridFilter*/ $filterGroup)
    {
        if (!($filters = $filterGroup->getFilters())) {
            return false;
        }

        $select = $this->getSelect();

        $adapter = $this->getSql()->getAdapter();
        $qi = function ($name) use ($adapter) {
            return $adapter->getPlatform()->quoteIdentifier($name);
        };

        $wheres = [];
        foreach ($filters as $filter) {
            $column = $filter->getColumn();
            if (!$column instanceof Column\Select) {
                throw new \Exception('This column cannot be filtered: ' . $column->getUniqueId());
            }

            $colString = $column->getSelectPart1();
            if ($column->getSelectPart2() != '') {
                $colString .= '.' . $column->getSelectPart2();
            }

            if ($column instanceof Column\Select && $column->hasFilterSelectExpression()) {
                $colString = sprintf($column->getFilterSelectExpression(), $colString);
            }

            $values = $filter->getValues();
            foreach ($values as $value) {
                $where = new Where();
                switch ($filter->getOperator()) {
                    case DatagridFilter::LIKE:
                        $wheres[] = $where->like($colString, '%' . $value . '%');
                        break;
                    case DatagridFilter::LIKE_LEFT:
                        $wheres[] = $where->like($colString, '%' . $value);
                        break;
                    case DatagridFilter::LIKE_RIGHT:
                        $wheres[] = $where->like($colString, $value . '%');
                        break;
                    case DatagridFilter::NOT_LIKE:
                        $wheres[] = $where->literal($qi($colString) . 'NOT LIKE ?', [
                            '%' . $value . '%',
                        ]);
                        break;
                    case DatagridFilter::NOT_LIKE_LEFT:
                        $wheres[] = $where->literal($qi($colString) . 'NOT LIKE ?', [
                            '%' . $value,
                        ]);
                        break;
                    case DatagridFilter::NOT_LIKE_RIGHT:
                        $wheres[] = $where->literal($qi($colString) . 'NOT LIKE ?', [
                            $value . '%',
                        ]);
                        break;
                    case DatagridFilter::EQUAL:
                        $wheres[] = $where->equalTo($colString, $value);
                        break;
                    case DatagridFilter::NOT_EQUAL:
                        $wheres[] = $where->notEqualTo($colString, $value);
                        break;
                    case DatagridFilter::GREATER_EQUAL:
                        $wheres[] = $where->greaterThanOrEqualTo($colString, $value);
                        break;
                    case DatagridFilter::GREATER:
                        $wheres[] = $where->greaterThan($colString, $value);
                        break;
                    case DatagridFilter::LESS_EQUAL:
                        $wheres[] = $where->lessThanOrEqualTo($colString, $value);
                        break;
                    case DatagridFilter::LESS:
                        $wheres[] = $where->lessThan($colString, $value);
                        break;
                    case DatagridFilter::IN:
                        $wheres[] = $where->in($colString, $values);
                        break 2;
                    case DatagridFilter::BETWEEN:
                        $wheres[] = $where->between($colString, $values[0], $values[1]);
                        break 2;
                    case DatagridFilter::NOT_NULL:
                        $wheres[] = $where->isNotNull($colString);
                        break;
                    case DatagridFilter::NULL:
                        $wheres[] = $where->isNull($colString);
                        break;
                    default:
                        throw new \InvalidArgumentException(
                            'This operator is currently not supported: ' . $filter->getOperator()
                        );
                        break;
                }
            }
        }

        if (!empty($wheres)) {
            //$set = new PredicateSet($wheres, PredicateSet::OP_OR);
            //$select->where->andPredicate($set);


            if ($groups = $filterGroup->getGroups()) {
                foreach ($groups as $group) {
                    //$exp->add($this->applyFilter($group));
                    $wheres[] = $this->applyFilter($group);
                }
            }

            $set = FilterGroup::COND_AND === $filterGroup->getCondition()
                ? new PredicateSet($wheres, PredicateSet::OP_AND)
                : new PredicateSet($wheres, PredicateSet::OP_OR);

            $select->where->andPredicate($set);

            //$set->addMultiple($wheres);
        }

        return $select;
    }
}
