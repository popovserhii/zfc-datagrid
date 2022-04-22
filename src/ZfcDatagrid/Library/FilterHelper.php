<?php
namespace ZfcDatagrid\Library;

use Laminas\Stdlib\ArrayUtils;

class FilterHelper
{
    public static function merge($originFilters, $newFilters)
    {
        return ArrayUtils::merge(
            $originFilters ?? [],
            ['groupOp' => $originFilters['groupOp'] ?? 'AND', 'rules' => $newFilters]
        );
    }
}
