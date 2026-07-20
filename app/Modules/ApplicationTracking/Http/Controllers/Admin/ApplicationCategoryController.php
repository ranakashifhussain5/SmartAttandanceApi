<?php

namespace App\Modules\ApplicationTracking\Http\Controllers\Admin;

use App\Modules\ApplicationTracking\Http\Controllers\Controller;
use App\Modules\ApplicationTracking\Http\Requests\ApplicationCategory\StoreApplicationCategoryRequest;
use App\Modules\ApplicationTracking\Http\Requests\ApplicationCategory\UpdateApplicationCategoryRequest;
use App\Modules\ApplicationTracking\Http\Resources\ApplicationCategoryResource;
use App\Modules\ApplicationTracking\Models\ApplicationCategory;
use Illuminate\Http\JsonResponse;

class ApplicationCategoryController extends Controller
{
    // Listing is shared with every authenticated role via
    // ApplicationController::categories() (admins see every category,
    // everyone else only active ones they're eligible for) — this
    // controller only holds the admin-only mutating actions.

    public function store(StoreApplicationCategoryRequest $request): JsonResponse
    {
        $category = ApplicationCategory::create($request->validated());

        return $this->ok(ApplicationCategoryResource::make($category->load('workflowTemplate.steps')), 'Application category created', 201);
    }

    public function show(ApplicationCategory $applicationCategory): JsonResponse
    {
        return $this->ok(ApplicationCategoryResource::make($applicationCategory->load('workflowTemplate.steps')));
    }

    public function update(UpdateApplicationCategoryRequest $request, ApplicationCategory $applicationCategory): JsonResponse
    {
        $applicationCategory->update($request->validated());

        return $this->ok(ApplicationCategoryResource::make($applicationCategory->load('workflowTemplate.steps')), 'Application category updated');
    }
}
