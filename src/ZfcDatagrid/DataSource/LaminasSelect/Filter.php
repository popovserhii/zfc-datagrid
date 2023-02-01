<?php
namespace ZfcDatagrid\DataSource\LaminasSelect;

use Laminas\Db\Sql\Predicate\Operator;
use Laminas\Db\Sql\Predicate\Predicate;
use Laminas\Db\Sql\Predicate\PredicateSet;
//use Laminas\Db\Sql\Predicate\Expression;
//use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Where;
use Laminas\Db\Sql\Having;
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
     * Mappting of select clauses to real classes.
     *
     * @var string[]
     */
    private $clauses = [
        Column\Select::SELECT_СLAUSE_WHERE => Where::class,
        Column\Select::SELECT_СLAUSE_HAVING => Having::class,
    ];

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

        $clauses = [];
        foreach ($filters as $filter) {
            $column = $filter->getColumn();
            $clauseType = $column->getFilterSelectСlause();
            $clauseClass = $this->clauses[$clauseType];
            /** @var Predicate $clause */
            $clause = new $clauseClass();
            
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
                        throw new \InvalidArgumentException(
                            'This operator is currently not supported: ' . $filter->getOperator()
                        );
                        break;
                }
            }
        }

        if (!empty($clauses)) {
            //$set = new PredicateSet($wheres, PredicateSet::OP_OR);
            //$select->where->andPredicate($set);


            if ($groups = $filterGroup->getGroups()) {
                foreach ($groups as $group) {
                    $this->applyFilter($group);
                }
            }

            foreach ($clauses as $type => $subClauses) {
                $set = FilterGroup::COND_AND === $filterGroup->getCondition()
                    ? new PredicateSet($subClauses, PredicateSet::OP_AND)
                    : new PredicateSet($subClauses, PredicateSet::OP_OR);
                $select->{$type}->andPredicate($set);
            }
        }

        return $select;
    }
}
