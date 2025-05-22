<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\PlatformToggleRequest;
use App\Models\Platform;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PlatformController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $userPlatformIds = $user->platforms()->pluck('platform_id')->toArray();

        $platforms = Platform::all()->map(function ($platform) use ($userPlatformIds) {
            $platform->active = in_array($platform->id, $userPlatformIds);
            return $platform;
        });

        return ApiResponse::success(Response::HTTP_OK,'Platforms retrieved successfully', $platforms);
    }

    public function toggle(PlatformToggleRequest $request)
    {
        $validated = $request->validated();
        $user = Auth::user();

        if ($validated['active']) {
            $user->platforms()->syncWithoutDetaching([$validated['platform_id'] => []]);
        } else {
            $user->platforms()->detach($validated['platform_id']);
        }

        return ApiResponse::success(Response::HTTP_OK, 'Platform toggled successfully', [
            'platform_id' => $validated['platform_id'],
            'active' => $validated['active'],
        ]);
    }
}