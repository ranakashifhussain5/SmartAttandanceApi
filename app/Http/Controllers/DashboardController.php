<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    public function admin(): JsonResponse
    {
        return $this->ok($this->dashboard->admin());
    }

    public function hod(Request $request): JsonResponse
    {
        return $this->ok($this->dashboard->hod($request->user()));
    }

    public function teacher(Request $request): JsonResponse
    {
        return $this->ok($this->dashboard->teacher($request->user()));
    }

    public function student(Request $request): JsonResponse
    {
        return $this->ok($this->dashboard->student($request->user()));
    }
}
