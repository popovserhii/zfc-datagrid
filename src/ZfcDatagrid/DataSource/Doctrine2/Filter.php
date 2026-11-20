<?php
namespace ZfcDatagrid\DataSource\Doctrine2;

use Exception;
use InvalidArgumentException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;
use ZfcDatagrid\Column;
use ZfcDatagrid\Filter as DatagridFilter;
use ZfcDatagrid\FilterGroup;
use function sprintf;

class Filter
{
    /**
     * @var QueryBuilder
     */
    private $qb;

    private int $parameterIndex = 0;

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

    public function applyFilters($filterGroup)
    {
        if (!$filterGroup) {
            return;
        }

        if ($expr = $this->applyFilter($filterGroup)) {
            $this->getQueryBuilder()->andWhere($expr);
        }
    }

    /**
     * @param FilterGroup $filterGroup
     *
     * @throws Exception
     * @return Expr\Andx|Expr\Orx|false
     */
    public function applyFilter(/*DatagridFilter*/ $filterGroup)
    {
        $qb   = $this->getQueryBuilder();
        $expr = $qb->expr();

        $clauses = [];
        foreach ($filterGroup->getFilters() as $filter) {
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
                $valueParameterName = $this->nextParameterName();
                switch ($filter->getOperator()) {
                    case DatagridFilter::LIKE:
                        $clauses[] = $expr->like($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, '%' . $value . '%');

                        break;
                    case DatagridFilter::LIKE_LEFT:
                        $clauses[] = $expr->like($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, '%' . $value);

                        break;
                    case DatagridFilter::LIKE_RIGHT:
                        $clauses[] = $expr->like($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, $value . '%');

                        break;
                    case DatagridFilter::NOT_LIKE:
                        $clauses[] = $expr->notLike($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, '%' . $value . '%');

                        break;
                    case DatagridFilter::NOT_LIKE_LEFT:
                        $clauses[] = $expr->notLike($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, '%' . $value);

                        break;
                    case DatagridFilter::NOT_LIKE_RIGHT:
                        $clauses[] = $expr->notLike($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, $value . '%');

                        break;
                    case DatagridFilter::EQUAL:
                        $clauses[] = $expr->eq($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, $value);

                        break;
                    case DatagridFilter::NOT_EQUAL:
                        $clauses[] = $expr->neq($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, $value);

                        break;
                    case DatagridFilter::GREATER_EQUAL:
                        $clauses[] = $expr->gte($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, $value);

                        break;
                    case DatagridFilter::GREATER:
                        $clauses[] = $expr->gt($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, $value);

                        break;
                    case DatagridFilter::LESS_EQUAL:
                        $clauses[] = $expr->lte($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, $value);

                        break;
                    case DatagridFilter::LESS:
                        $clauses[] = $expr->lt($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, $value);

                        break;
                    case DatagridFilter::IN:
                        $clauses[] = $expr->in($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, $values);

                        break 2;
                    case DatagridFilter::NOT_IN:
                        $clauses[] = $expr->notIn($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, $values);

                        break 2;
                    case DatagridFilter::BETWEEN:
                        $minParameterName = $valueParameterName;
                        $maxParameterName = $this->nextParameterName();
                        $clauses[] = $expr->between($colString, $minParameterName, $maxParameterName);
                        $qb->setParameter($minParameterName, $values[0]);
                        $qb->setParameter($maxParameterName, $values[1]);

                        break 2;
                    case DatagridFilter::NOT_NULL:
                        $clauses[] = $expr->isNotNull($colString);

                        break;
                    case DatagridFilter::NULL:
                        $clauses[] = $expr->isNull($colString);

                        break;
                    default:
                        throw new InvalidArgumentException(
                            'This operator is currently not supported: ' . $filter->getOperator()
                        );
                }
            }
        }

        foreach ($filterGroup->getGroups() as $group) {
            if ($child = $this->applyFilter($group)) {
                $clauses[] = $child;
            }
        }

        if (!$clauses) {
            return false;
        }

        return FilterGroup::COND_AND === $filterGroup->getCondition()
            ? $expr->andX(...$clauses)
            : $expr->orX(...$clauses);
    }

    private function nextParameterName(): string
    {
        do {
            $name = 'datagridFilter' . $this->parameterIndex++;
        } while ($this->qb->getParameter($name) !== null);

        return ':' . $name;
    }
}
