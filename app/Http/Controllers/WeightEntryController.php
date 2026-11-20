<?php

namespace App\Http\Controllers;

use App\Models\WeightEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WeightEntryController extends Controller
{
    /**
     * Save the weigh-in for a day, replacing one already stored for that date.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recorded_on' => ['required', 'date'],
            'weight_kg' => ['required', 'numeric', 'min:20', 'max:400'],
        ]);

        $entry = $request->user()->weights()
            ->whereDate('recorded_on', $validated['recorded_on'])
            ->first();

        if ($entry) {
            $entry->update(['weight_kg' => $validated['weight_kg']]);

            return back()->with('status', 'Weight saved.');
        }

        $request->user()->weights()->create($validated);

        return back()->with('status', 'Weight saved.');
    }

    /**
     * Remove a weigh-in.
     */
    public function destroy(Request $request, WeightEntry $weightEntry): RedirectResponse
    {
        abort_unless((int) $weightEntry->user_id === (int) $request->user()->id, 404);

        $weightEntry->delete();

        return back()->with('status', 'Weight removed.');
    }
}
