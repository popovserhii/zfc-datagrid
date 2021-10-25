<?php
namespace ZfcDatagrid\Renderer\JqGrid;

//use Laminas\Http\PhpEnvironment\Request as HttpRequest;
use Psr\Http\Message\ServerRequestInterface as HttpRequestInterface;
use Laminas\View\Model\JsonModel;
use ZfcDatagrid\Column;
use ZfcDatagrid\Renderer\AbstractRenderer;
use function explode;
use function count;
use function strtoupper;
use function implode;

class Renderer extends AbstractRenderer
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'jqGrid';
    }

    /**
     * @return bool
     */
    public function isHtml(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isExport(): bool
    {
        return false;
    }

    /**
     * @return HttpRequestInterface
     *
     * @throws \Exception
     */
    public function getRequest(): HttpRequestInterface
    {
        $request = parent::getRequest();
        if (! $request instanceof HttpRequestInterface) {
            throw new \Exception(
                'Request must be an instance of Laminas\Http\PhpEnvironment\Request for HTML rendering'
            );
        }

        return $request;
    }

    public function getRequestParam($paramName)
    {
        $request = $this->getRequest();

        $optionsRenderer = $this->getOptionsRenderer();
        $parameterNames  = $optionsRenderer['parameterNames'];

        $postParams = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $sortColumns = $postParams[$parameterNames[$paramName]]
            ?? $queryParams[$parameterNames[$paramName]]
            ?? null;

        return $sortColumns;
    }

    public function getSortColumns()
    {
        return $this->getRequestParam('sortColumns');
    }

    public function getSortDirections()
    {
        return $this->getRequestParam('sortDirections');
    }

    /**
     * @see \ZfcDatagrid\Renderer\AbstractRenderer::getSortConditions()
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getSortConditions(): array
    {
        if (!empty($this->sortConditions)) {
            return $this->sortConditions;
        }

        $sortColumns = $this->getSortColumns();
        $sortDirections = $this->getSortDirections();

        if ($sortColumns != '') {
            $sortColumns    = explode(',', $sortColumns);
            $sortDirections = explode(',', $sortDirections);

            if (count($sortColumns) !== count($sortDirections)) {
                throw new \Exception('Count missmatch order columns/direction');
            }

            foreach ($sortColumns as $key => $sortColumn) {
                $sortDirection = strtoupper($sortDirections[$key]);

                if ($sortDirection != 'ASC' && $sortDirection != 'DESC') {
                    $sortDirection = 'ASC';
                }

                foreach ($this->getColumns() as $column) {
                    /* @var $column \ZfcDatagrid\Column\AbstractColumn */
                    if ($column->getUniqueId() == $sortColumn) {
                        $sortConditions[] = [
                            'sortDirection' => $sortDirection,
                            'column'        => $column,
                        ];

                        $column->setSortActive($sortDirection);
                    }
                }
            }
        }

        if (! empty($sortConditions)) {
            $this->sortConditions = $sortConditions;
        } else {
            // No user sorting -> get default sorting
            $this->sortConditions = $this->getSortConditionsDefault();
        }

        return $this->sortConditions;
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    public function getFilters()//: array
    {
        if (!empty($this->filters)) {
            // set from cache! (for export)
            return $this->filters;
        }


        $optionsRenderer = $this->getOptionsRenderer();
        $parameterNames  = $optionsRenderer['parameterNames'];

        $request  = $this->getRequest();
        $postParams = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $isSearch = $postParams[$parameterNames['isSearch']]
            ?? $queryParams[$parameterNames['isSearch']]
            ?? null;

        $filters = [];
        #$isSearch = $request->getPost($parameterNames['isSearch'], $request->getQuery($parameterNames['isSearch']));
        if ('true' == $isSearch) {
            // User filtering
            foreach ($this->getColumns() as $column) {
                $values = $postParams['filters'] ?? $queryParams['filters'] ?? [];
                $extendedFilters = $this->prepareFilter(json_decode($values ?? [], true));

                $simpleFilter = $postParams[$column->getUniqueId()] ?? $queryParams[$column->getUniqueId()] ?? null;
                #$value = $request->getPost($column->getUniqueId(), $request->getQuery($column->getUniqueId()));
                /* @var $column \ZfcDatagrid\Column\AbstractColumn */
                if ($simpleFilter != '') {
                    $filters[] = $this->createFilter($column, $simpleFilter);
                } elseif ($extendedFilters !== null && isset($extendedFilters[$column->getUniqueId()])) {
                    $simpleFilter = implode(',', $extendedFilters[$column->getUniqueId()]['values']);
                    $filter = $this->createFilter($column, $simpleFilter);
                    $filters[] = $filter;

                    $column->setFilterActive($filter->getDisplayColumnValue());
                }
            }
        }

        if (empty($filters)) {
            // No user sorting -> get default sorting
            $filters = $this->getFiltersDefault();
        }

        $this->filters = $filters;

        return $this->filters;
    }

    public function createFilter($column, $value)
    {
        /* @var $column \ZfcDatagrid\Column\AbstractColumn */
        $filter = new \ZfcDatagrid\Filter();
        $filter->setFromColumn($column, $value);
        $column->setFilterActive($filter->getDisplayColumnValue());

        return $filter;
    }

    public function prepareFilter($rawFilters)
    {
        if (!$rawFilters) {
            return null;
        }

        static $fields = [];
        foreach ($rawFilters as $key => $values) {
            if ($values && $key === 'rules') {
                foreach ($values as $rule) {
                    if (!isset($fields[$rule['field']])) {
                        $fields[$rule['field']] = [];
                    }
                    $fields[$rule['field']]['values'][] = $rule['data'];
                }
            }
            if ($values && $key === 'groups') {
                foreach ($values as $sub) {
                    $this->prepareFilter($sub);
                }
            }
        }

        return $fields;
    }

    public function getCurrentPageNumber(): int
    {
        $optionsRenderer = $this->getOptionsRenderer();
        $parameterNames = $optionsRenderer['parameterNames'];

        $request = $this->getRequest();
        if ($request instanceof HttpRequestInterface) {
            $postParams = $request->getParsedBody();
            $queryParams = $request->getQueryParams();

            $currentPage = $postParams[$parameterNames['currentPage']]
                ?? $queryParams[$parameterNames['currentPage']]
                ?? null;
            if ($currentPage != '') {
                $this->currentPageNumber = (int) $currentPage;
            }
        }

        return (int) $this->currentPageNumber;
    }

    /**
     * @return null|JsonModel|\Laminas\View\Model\ViewModel
     * @throws \Exception
     */
    public function execute()
    {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest() === true &&
            $request->getPost('nd', $request->getQuery('nd')) != ''
        ) {
            // AJAX Request...load only data...
            $viewModel = new JsonModel();
            $viewModel->setVariable('data', $this->getDataJqGrid());
        } else {
            $viewModel = $this->getViewModel();
            $viewModel->setTemplate($this->getTemplate());
            $viewModel->setVariable('data', $this->getDataJqGrid());

            $columnsRowClickDisabled = [];
            $columns = $viewModel->getVariable('columns');
            foreach ($columns as $column) {
                /* @var $column \ZfcDatagrid\Column\AbstractColumn */

                if ($column->isRowClickEnabled() !== true) {
                    $columnsRowClickDisabled[] = $column->getUniqueId();
                }
            }

            $viewModel->setVariable('columnsRowClickDisabled', $columnsRowClickDisabled);
        }

        return $viewModel;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $data = parent::getData();

        foreach ($data as &$row) {
            foreach ($this->getColumns() as $column) {
                if ($column instanceof Column\Select) {
                    // $row[$column->getUniqueId()] = nl2br($row[$column->getUniqueId()], true);
                } elseif ($column instanceof Column\Action) {
                    /* @var $column \ZfcDatagrid\Column\Action */

                    if ($column->getActions()) {
                        $actions = [];
                        foreach ($column->getActions() as $action) {
                            /* @var $action \ZfcDatagrid\Column\Action\AbstractAction */
                            if ($action->isDisplayed($row) === true) {
                                $action->setTitle($this->translate($action->getTitle()));
                                $actions[] = $action->toHtml($row);
                            }
                        }
                        $row[$column->getUniqueId()] = implode(' ', $actions);
                    }
                } elseif ($column instanceof Column\Action\Icon) {
                    $row[$column->getUniqueId()] = $column->getIconClass();
                }
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    private function getDataJqGrid(): array
    {
        return [
            'rows'    => $this->getData(),
            'page'    => $this->getPaginator()->getCurrentPageNumber(),
            'total'   => $this->getPaginator()->count(),
            'records' => $this->getPaginator()->getTotalItemCount(),
        ];
    }
}
