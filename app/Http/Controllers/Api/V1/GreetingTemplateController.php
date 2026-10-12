<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\GreetingTemplateResource;
use App\Models\GreetingTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GreetingTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $occasion = $request->query('occasion');

        $templates = GreetingTemplate::query()
            ->active()
            ->when($occasion, function ($query, $occasion) {
                return $query->where('occasion', $occasion);
            })
            ->get();

        return response()->json([
            'data' => GreetingTemplateResource::collection($templates),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $template = GreetingTemplate::query()
            ->where('slug', $slug)
            ->orWhere('id', $slug)
            ->firstOrFail();

        return response()->json([
            'data' => new GreetingTemplateResource($template),
        ]);
    }
}
