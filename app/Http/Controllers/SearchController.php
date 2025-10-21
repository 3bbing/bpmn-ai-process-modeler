<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(private SearchService $service)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $results = $this->service->search(
            $request->string('query')->toString(),
            $request->string('domain')->toNullableString(),
            $request->string('level')->toNullableString(),
            $request->string('status')->toNullableString()
        );

        return response()->json($results);
    }
}
