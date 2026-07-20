<?php

namespace App\Modules\ApplicationTracking\Http\Controllers;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

class Controller extends BaseController
{
    /**
     * Unlike the app-wide ok(Resource::collection($paginator)) pattern
     * (which discards pagination metadata because the ResourceCollection is
     * embedded inside another array rather than returned directly), this
     * module's list endpoints return real meta so an approvals queue can
     * show total counts and page through results.
     */
    protected function paginated(mixed $resource, LengthAwarePaginator $paginator, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $resource,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
