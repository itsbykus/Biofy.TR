<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class ApiController extends Controller
{
    /** Tenant isolation: another user's id answers 404 (no hint that it exists). */
    protected function ownedProfile(Request $request, int $id): Profile
    {
        $profile = Profile::query()->whereKey($id)->where('user_id', $request->user()->id)->first();
        abort_if($profile === null, 404);

        return $profile;
    }

    protected function data(mixed $data, int $status = 200, array $meta = []): JsonResponse
    {
        $body = ['data' => $data];

        if ($meta !== []) {
            $body['meta'] = $meta;
        }

        return response()->json($body, $status);
    }

    protected function paginated(LengthAwarePaginator $page, callable $map): JsonResponse
    {
        return $this->data(array_map($map, $page->items()), 200, [
            'current_page' => $page->currentPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
            'last_page' => $page->lastPage(),
        ]);
    }

    protected function perPage(Request $request): int
    {
        return max(1, min(100, (int) $request->query('per_page', 20)));
    }
}
