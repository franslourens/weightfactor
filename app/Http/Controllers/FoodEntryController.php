<?php

namespace App\Http\Controllers;

use App\Models\FoodEntry;
use App\Support\FoodWeek;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FoodEntryController extends Controller
{
    /**
     * Log a food and its calories.
     */
    public function store(Request $request): RedirectResponse
    {
        $week = FoodWeek::current();

        $validated = $request->validate([
            'eaten_on' => ['required', 'date', 'after_or_equal:'.$week->start->toDateString(), 'before_or_equal:'.$week->end->toDateString()],
            'name' => ['required', 'string', 'max:120'],
            'calories' => ['required', 'integer', 'min:0', 'max:20000'],
            'protein_g' => ['required', 'numeric', 'min:0', 'max:500'],
            'carbs_g' => ['required', 'numeric', 'min:0', 'max:1000'],
            'fat_g' => ['required', 'numeric', 'min:0', 'max:500'],
        ], [
            'eaten_on.after_or_equal' => 'Choose a day from this Monday through Sunday.',
            'eaten_on.before_or_equal' => 'Choose a day from this Monday through Sunday.',
        ]);

        FoodEntry::query()->create($validated);

        return back()->with('status', 'Food logged.');
    }

    /**
     * Change how many servings a logged food counts as.
     */
    public function update(Request $request, FoodEntry $foodEntry): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.1', 'max:20'],
        ]);

        $foodEntry->update([
            'quantity' => round((float) $validated['quantity'], 2),
        ]);

        return back()->with('status', 'Quantity saved.');
    }

    /**
     * Remove a food log.
     */
    public function destroy(FoodEntry $foodEntry): RedirectResponse
    {
        $foodEntry->delete();

        return back()->with('status', 'Food removed.');
    }
}
