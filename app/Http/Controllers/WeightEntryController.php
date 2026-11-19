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

        $entry = WeightEntry::query()
            ->whereDate('recorded_on', $validated['recorded_on'])
            ->first();

        if ($entry) {
            $entry->update(['weight_kg' => $validated['weight_kg']]);

            return back()->with('status', 'Weight saved.');
        }

        WeightEntry::query()->create($validated);

        return back()->with('status', 'Weight saved.');
    }

    /**
     * Remove a weigh-in.
     */
    public function destroy(WeightEntry $weightEntry): RedirectResponse
    {
        $weightEntry->delete();

        return back()->with('status', 'Weight removed.');
    }
}
