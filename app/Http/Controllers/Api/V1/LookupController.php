<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EducationLevel;
use App\Models\Location;
use App\Models\Profession;
use App\Models\Religion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lookup data (blueprint §13). Public so the frontend can build filter panels
 * and onboarding forms without authentication.
 */
class LookupController extends Controller
{
    public function divisions(): JsonResponse
    {
        return response()->json([
            'data' => Location::where('type', 'division')->orderBy('name_en')->get(['id', 'name_en', 'name_bn']),
        ]);
    }

    public function districts(Request $request): JsonResponse
    {
        $query = Location::where('type', 'district');
        if ($request->filled('division_id')) {
            $query->where('parent_id', $request->integer('division_id'));
        }

        return response()->json(['data' => $query->orderBy('name_en')->get(['id', 'parent_id', 'name_en', 'name_bn'])]);
    }

    public function upazilas(Request $request): JsonResponse
    {
        $query = Location::where('type', 'upazila');
        if ($request->filled('district_id')) {
            $query->where('parent_id', $request->integer('district_id'));
        }

        return response()->json(['data' => $query->orderBy('name_en')->get(['id', 'parent_id', 'name_en', 'name_bn'])]);
    }

    public function religions(): JsonResponse
    {
        return response()->json(['data' => Religion::orderBy('id')->get(['id', 'key', 'name_en', 'name_bn'])]);
    }

    public function professions(): JsonResponse
    {
        return response()->json(['data' => Profession::orderBy('name_en')->get(['id', 'key', 'name_en', 'name_bn'])]);
    }

    public function educationLevels(): JsonResponse
    {
        return response()->json(['data' => EducationLevel::orderBy('rank')->get(['id', 'key', 'name_en', 'name_bn', 'rank'])]);
    }
}
