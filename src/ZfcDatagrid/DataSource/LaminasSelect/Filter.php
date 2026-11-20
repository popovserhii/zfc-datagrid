<?php
namespace ZfcDatagrid\DataSource\LaminasSelect;

use InvalidArgumentException;
use Laminas\Db\Sql\Predicate\Predicate;
use Laminas\Db\Sql\Predicate\PredicateSet;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
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
    }
    
    /**
     * @param FilterGroup $filterGroup
     *
     * @return Select|false
     * @throws \Exception
     */
    public function applyFilter(/*DatagridFilter*/ $filterGroup)
    {
        $predicates = $this->buildPredicates($filterGroup);
        if (!$predicates) {
            return false;
        }

        $select = $this->getSelect();
        foreach ($predicates as $type => $predicate) {
            $select->{$type}->andPredicate($predicate);
        }

        return $select;
    }

    /**
     * @return array<string, PredicateSet>
     */
    private function buildPredicates(FilterGroup $filterGroup): array
    {
        $adapter = $this->getSql()->getAdapter();
        $qi = function ($name) use ($adapter) {
            return $adapter->getPlatform()->quoteIdentifier($name);
        };

        $clauses = [];
        foreach ($filterGroup->getFilters() as $filter) {
            $column = $filter->getColumn();
            if (!$column instanceof Column\Select) {
                throw new \Exception('This column cannot be filtered: ' . $column->getUniqueId());
            }

            $clauseType = $column->getFilterSelectСlause();
            if (!in_array($clauseType, [Column\Select::SELECT_СLAUSE_WHERE, Column\Select::SELECT_СLAUSE_HAVING], true)) {
                throw new InvalidArgumentException('Unsupported filter clause: ' . $clauseType);
            }

            $colString = $column->getSelectPart1();
            if ($column->getSelectPart2() != '') {
                $colString .= '.' . $column->getSelectPart2();
            }

            if ($column->hasFilterSelectExpression()) {
                $colString = sprintf($column->getFilterSelectExpression(), $colString);
            }

            $values = $filter->getValues();
            foreach ($values as $value) {
                $clause = new Predicate();
                switch ($filter->getOperator()) {
                    case DatagridFilter::LIKE:
                        $clauses[$clauseType][] = $clause->like($colString, '%' . $value . '%');
                        break;
                    case DatagridFilter::LIKE_LEFT:
                        $clauses[$clauseType][] = $clause->like($colString, '%' . $value);
                        break;
                    case DatagridFilter::LIKE_RIGHT:
                        $clauses[$clauseType][] = $clause->like($colString, $value . '%');
                        break;
                    case DatagridFilter::NOT_LIKE:
                        $clauses[$clauseType][] = $clause->literal($qi($colString) . 'NOT LIKE ?', [
                            '%' . $value . '%',
                        ]);
                        break;
                    case DatagridFilter::NOT_LIKE_LEFT:
                        $clauses[$clauseType][] = $clause->literal($qi($colString) . 'NOT LIKE ?', [
                            '%' . $value,
                        ]);
                        break;
                    case DatagridFilter::NOT_LIKE_RIGHT:
                        $clauses[$clauseType][] = $clause->literal($qi($colString) . 'NOT LIKE ?', [
                            $value . '%',
                        ]);
                        break;
                    case DatagridFilter::EQUAL:
                        $clauses[$clauseType][] = $clause->equalTo($colString, $value);
                        break;
                    case DatagridFilter::NOT_EQUAL:
                        $clauses[$clauseType][] = $clause->notEqualTo($colString, $value);
                        break;
                    case DatagridFilter::GREATER_EQUAL:
                        $clauses[$clauseType][] = $clause->greaterThanOrEqualTo($colString, $value);
                        break;
                    case DatagridFilter::GREATER:
                        $clauses[$clauseType][] = $clause->greaterThan($colString, $value);
                        break;
                    case DatagridFilter::LESS_EQUAL:
                        $clauses[$clauseType][] = $clause->lessThanOrEqualTo($colString, $value);
                        break;
                    case DatagridFilter::LESS:
                        $clauses[$clauseType][] = $clause->lessThan($colString, $value);
                        break;
                    case DatagridFilter::IN:
                        $clauses[$clauseType][] = $clause->in($colString, $values);
                        break 2;
                    case DatagridFilter::NOT_IN:
                        $clauses[$clauseType][] = $clause->notIn($colString, $values);
                        break 2;
                    case DatagridFilter::BETWEEN:
                        $clauses[$clauseType][] = $clause->between($colString, $values[0], $values[1]);
                        break 2;
                    case DatagridFilter::NOT_NULL:
                        $clauses[$clauseType][] = $clause->isNotNull($colString);
                        break;
                    case DatagridFilter::NULL:
                        $clauses[$clauseType][] = $clause->isNull($colString);
                        break;
                    default:
                        throw new InvalidArgumentException('This operator is currently not supported: ' . $filter->getOperator());
                        break;
                }
            }
        }

        foreach ($filterGroup->getGroups() as $group) {
            foreach ($this->buildPredicates($group) as $type => $predicate) {
                $clauses[$type][] = $predicate;
            }
        }

        $combination = FilterGroup::COND_AND === $filterGroup->getCondition()
            ? PredicateSet::OP_AND
            : PredicateSet::OP_OR;
        if ($combination === PredicateSet::OP_OR && count($clauses) > 1) {
            throw new InvalidArgumentException('A filter group cannot combine WHERE and HAVING predicates with OR.');
        }

        $predicates = [];
        foreach ($clauses as $type => $subClauses) {
            $predicates[$type] = new PredicateSet($subClauses, $combination);
        }

        return $predicates;
    }
}
