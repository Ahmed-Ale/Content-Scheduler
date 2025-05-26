<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\PlatformToggleRequest;
use App\Models\Platform;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PlatformController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/platforms",
     *     summary="Get all platforms with user's active status",
     *     tags={"Platforms"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Platforms retrieved successfully",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(
     *
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Facebook"),
     *                 @OA\Property(property="type", type="string", example="social"),
     *                 @OA\Property(property="active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index()
    {
        $user = Auth::user();
        if (! $user) {
            return ApiResponse::error(Response::HTTP_UNAUTHORIZED, 'Unauthorized');
        }

        $platforms = Cache::remember('platforms_list', now()->addHours(24), function () {
            return Platform::select('id', 'name', 'type')->get();
        });

        $userPlatformIds = Cache::remember("user_platforms_{$user->id}", now()->addHours(1), function () use ($user) {
            return $user->platforms()->pluck('platform_id')->toArray();
        });

        $platforms = $platforms->map(function ($platform) use ($userPlatformIds) {
            return [
                'id' => $platform->id,
                'name' => $platform->name,
                'type' => $platform->type,
                'active' => in_array($platform->id, $userPlatformIds),
            ];
        })->values();

        return ApiResponse::success(Response::HTTP_OK, 'Platforms retrieved successfully', $platforms);
    }

    /**
     * @OA\Post(
     *     path="/api/platforms/toggle",
     *     summary="Toggle a platform's active status for the authenticated user",
     *     tags={"Platforms"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/PlatformToggleRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Platform toggled successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="platform_id", type="integer", example=2),
     *             @OA\Property(property="active", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to toggle platform"
     *     )
     * )
     */
    public function toggle(PlatformToggleRequest $request)
    {
        $validated = $request->validated();
        $user = Auth::user();

        try {
            DB::beginTransaction();
            if ($validated['active']) {
                $user->platforms()->syncWithoutDetaching([$validated['platform_id'] => []]);
            } else {
                $user->platforms()->detach($validated['platform_id']);
            }
            DB::commit();

            // Verify update
            $isActive = $user->platforms()->where('platform_id', $validated['platform_id'])->exists();
            if ($isActive !== $validated['active']) {
                return ApiResponse::error(Response::HTTP_INTERNAL_SERVER_ERROR, 'Failed to update platform status');
            }

            Cache::forget("user_platforms_{$user->id}");
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::error(Response::HTTP_INTERNAL_SERVER_ERROR, 'Failed to toggle platform', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return ApiResponse::success(Response::HTTP_OK, 'Platform toggled successfully', [
            'platform_id' => $validated['platform_id'],
            'active' => $validated['active'],
        ]);
    }
}
