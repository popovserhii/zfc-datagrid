<?php
namespace ZfcDatagrid\DataSource\Doctrine2;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use ZfcDatagrid\Column;
use ZfcDatagrid\Filter as DatagridFilter;
use ZfcDatagrid\FilterGroup;
use function sprintf;
use function str_replace;

class Filter
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

    public function applyFilters($filterGroup)
    {
        if (!$filterGroup) {
            return;
        }
        
        $qb = $this->getQueryBuilder();
        if ($expr = $this->applyFilter($filterGroup)) {
            FilterGroup::COND_AND === $filterGroup->getCondition()
                ? $qb->andWhere($expr)
                : $qb->orWhere($expr);
        }
    }

    /**
     * @param FilterGroup $filterGroup
     *
     * @throws \Exception
     * @return $this
     */
    public function applyFilter(/*DatagridFilter*/ $filterGroup)
    {
        if (!($filters = $filterGroup->getFilters())) {
            return false;
        }

        $qb   = $this->getQueryBuilder();
        $expr = $qb->expr();

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
            foreach ($values as $key => $value) {
                $valueParameterName = ':' . str_replace('.', '', $column->getUniqueId() . $key);
                switch ($filter->getOperator()) {
                    case DatagridFilter::LIKE:
                        $wheres[] = $expr->like($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, '%' . $value . '%');

                        break;
                    case DatagridFilter::LIKE_LEFT:
                        $wheres[] = $expr->like($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, '%' . $value);

                        break;
                    case DatagridFilter::LIKE_RIGHT:
                        $wheres[] = $expr->like($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, $value . '%');

                        break;
                    case DatagridFilter::NOT_LIKE:
                        $wheres[] = $expr->notLike($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, '%' . $value . '%');

                        break;
                    case DatagridFilter::NOT_LIKE_LEFT:
                        $wheres[] = $expr->notLike($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, '%' . $value);

                        break;
                    case DatagridFilter::NOT_LIKE_RIGHT:
                        $wheres[] = $expr->notLike($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, $value . '%');

                        break;
                    case DatagridFilter::EQUAL:
                        $wheres[] = $expr->eq($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, $value);

                        break;
                    case DatagridFilter::NOT_EQUAL:
                        $wheres[] = $expr->neq($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, $value);

                        break;
                    case DatagridFilter::GREATER_EQUAL:
                        $wheres[] = $expr->gte($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, $value);

                        break;
                    case DatagridFilter::GREATER:
                        $wheres[] = $expr->gt($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, $value);

                        break;
                    case DatagridFilter::LESS_EQUAL:
                        $wheres[] = $expr->lte($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, $value);

                        break;
                    case DatagridFilter::LESS:
                        $wheres[] = $expr->lt($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, $value);

                        break;
                    case DatagridFilter::IN:
                        $wheres[] = $expr->in($colString, $valueParameterName);
                        $qb->setParameter($valueParameterName, $values);

                        break 2;
                    case DatagridFilter::BETWEEN:
                        $minParameterName = ':' . str_replace('.', '', $colString . '0');
                        $maxParameterName = ':' . str_replace('.', '', $colString . '1');
                        $wheres[] = $expr->between($colString, $minParameterName, $maxParameterName);
                        $qb->setParameter($minParameterName, $values[0]);
                        $qb->setParameter($maxParameterName, $values[1]);

                        break 2;
                    case DatagridFilter::NOT_NULL:
                        $wheres[] = $expr->isNotNull($colString);

                        break;
                    case DatagridFilter::NULL:
                        $wheres[] = $expr->isNull($colString);

                        break;
                    default:
                        throw new \InvalidArgumentException(
                            'This operator is currently not supported: ' . $filter->getOperator()
                        );
                }
            }
        }

        if (! empty($wheres)) {
            if ($groups = $filterGroup->getGroups()) {
                foreach ($groups as $group) {
                    //$exp->add($this->applyFilter($group));
                    $wheres[] = $this->applyFilter($group);
                }
            }

            $expr = FilterGroup::COND_AND === $filterGroup->getCondition()
                ? $expr->andX()
                : $expr->orX();

            $expr->addMultiple($wheres);
        }

        return $expr;
    }
}
