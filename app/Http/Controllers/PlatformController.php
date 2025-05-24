<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\PlatformToggleRequest;
use App\Models\Platform;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PlatformController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            Log::error('No authenticated user found in PlatformController::index');
            return ApiResponse::error(Response::HTTP_UNAUTHORIZED, 'Unauthorized');
        }

        Log::info('Fetching platforms for user', ['user_id' => $user->id]);

        // Step 1: Get all available platforms
        $platforms = Platform::all();

        // Step 2: Get active platform IDs for the user
        $userPlatformIds = $user->platforms()->pluck('platform_id')->toArray();
        Log::info('User active platform IDs', [
            'user_id' => $user->id,
            'user_platform_ids' => $userPlatformIds,
        ]);

        // Step 3: Map platforms with active status
        $platforms = $platforms->map(function ($platform) use ($userPlatformIds) {
            $platform->active = in_array($platform->id, $userPlatformIds);
            return [
                'id' => $platform->id,
                'name' => $platform->name,
                'type' => $platform->type,
                'active' => $platform->active,
            ];
        })->values();

        Log::info('Platforms response', [
            'user_id' => $user->id,
            'platforms' => $platforms->toArray(),
        ]);

        return ApiResponse::success(Response::HTTP_OK, 'Platforms retrieved successfully', $platforms);
    }

    public function toggle(PlatformToggleRequest $request)
    {
        $validated = $request->validated();
        $user = Auth::user();
        Log::info('Toggling platform', [
            'user_id' => $user->id,
            'platform_id' => $validated['platform_id'],
            'active' => $validated['active'],
        ]);

        try {
            DB::beginTransaction();
            if ($validated['active']) {
                $user->platforms()->syncWithoutDetaching([$validated['platform_id'] => []]);
                Log::info('Platform attached', ['platform_id' => $validated['platform_id']]);
            } else {
                $user->platforms()->detach($validated['platform_id']);
                Log::info('Platform detached', ['platform_id' => $validated['platform_id']]);
            }
            DB::commit();

            // Verify update
            $isActive = $user->platforms()->where('platform_id', $validated['platform_id'])->exists();
            if ($isActive !== $validated['active']) {
                Log::error('Platform toggle failed to persist', [
                    'user_id' => $user->id,
                    'platform_id' => $validated['platform_id'],
                    'expected_active' => $validated['active'],
                    'actual_active' => $isActive,
                ]);
                return ApiResponse::error(Response::HTTP_INTERNAL_SERVER_ERROR, 'Failed to update platform status');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Platform toggle exception', [
                'user_id' => $user->id,
                'platform_id' => $validated['platform_id'],
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error(Response::HTTP_INTERNAL_SERVER_ERROR, 'Failed to toggle platform');
        }

        return ApiResponse::success(Response::HTTP_OK, 'Platform toggled successfully', [
            'platform_id' => $validated['platform_id'],
            'active' => $validated['active'],
        ]);
    }
}