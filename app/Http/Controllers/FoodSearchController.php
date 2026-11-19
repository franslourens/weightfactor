<?php

namespace App\Http\Controllers;

use App\Support\FoodLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FoodSearchController extends Controller
{
    /**
     * Search foods and return calories with protein, carbs, and fat.
     */
    public function __invoke(Request $request, FoodLookup $lookup): JsonResponse
    {
        return response()->json($lookup->search($request->string('q')->toString()));
    }
}
