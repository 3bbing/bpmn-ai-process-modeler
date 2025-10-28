<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Stringable;

class SearchController extends Controller
{
    public function __construct(private SearchService $service)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $results = $this->service->search(
            $request->string('query')->toString(),
            $this->nullable($request->string('domain')),
            $this->nullable($request->string('level')),
            $this->nullable($request->string('status'))
        );

        return response()->json($results);
    }

    protected function nullable(Stringable $stringable): ?string
    {
        $value = $stringable->toString();

        return $value === '' ? null : $value;
    }
}
