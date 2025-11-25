<?php

namespace App\Nova\Filters;

use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;

class StatusFilter extends Filter
{
    public function name()
    {
        return 'Status';
    }

    public function apply(Request $request, $query, $value)
    {
        return $query->where('status', $value);
    }

    public function options(Request $request)
    {
        return [
            'pending' => 'Pending',
            'successful' => 'Success',
            'failed' => 'Failed'
        ];
    }
}
