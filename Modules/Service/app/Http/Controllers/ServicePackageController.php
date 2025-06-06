<?php

namespace Modules\Service\Http\Controllers;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Service\Models\ServicePackage;
use Modules\Service\Services\ServicePackageService;
use Modules\Service\Http\Requests\ServicePackageRequest;
use Modules\Service\Http\Requests\ServicePackageFilterRequest;
use Symfony\Component\HttpFoundation\Response;

class ServicePackageController extends Controller
{
    public function __construct(
        private ServicePackageService $service
    ) {
        $this->middleware('permission:service_packages.view')->only(['index', 'show']);
        $this->middleware('permission:service_packages.create')->only(['store']);
        $this->middleware('permission:service_packages.update')->only(['update']);
        $this->middleware('permission:service_packages.delete')->only(['destroy']);
    }

    public function index(ServicePackageFilterRequest $request): JsonResponse
    {
        $packages = $this->service->getAllPackages(
            $request->validated(),
            $request->get('per_page', 10)
        );

        return response()->json($packages);
    }

    public function store(ServicePackageRequest $request): JsonResponse
    {
        $package = $this->service->createPackage($request->validated());

        return response()->json($package, Response::HTTP_CREATED);
    }

    public function show(ServicePackage $package): JsonResponse
    {
        return response()->json($package->load('features'));
    }

    public function update(ServicePackageRequest $request, string $id): JsonResponse
    {
        $package = $this->service->updatePackage($id, $request->validated());

        return response()->json($package);
    }

    public function destroy(string $id): JsonResponse
    {
        if (!$this->service->getPackageById($id)) {
            return response()->json(['message' => __("service::messages.package.not_found")], 404);
        }
        $this->service->deletePackage($id);
        return response()->json([
            'message' => __("service::messages.package.deleted")
        ], 201);
    }
}