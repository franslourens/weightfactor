<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Throwable;

class StepEntryController extends Controller
{
    /**
     * Save this person's steps for the current month. Blank days are left empty.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'steps' => ['nullable', 'array'],
            'steps.*' => ['nullable', 'integer', 'min:0', 'max:200000'],
        ]);

        $start = now()->startOfMonth()->startOfDay();
        $end = now()->endOfMonth()->endOfDay();
        $days = [];

        foreach ($validated['steps'] ?? [] as $date => $count) {
            $day = $this->day($date);

            if ($day === null || $day->lt($start) || $day->gt($end)) {
                throw ValidationException::withMessages([
                    'steps' => 'Choose a day in this month.',
                ]);
            }

            $days[$day->toDateString()] = $count;
        }

        foreach ($days as $date => $count) {
            $entry = $request->user()->steps()->whereDate('recorded_on', $date)->first();

            if ($count === null) {
                $entry?->delete();

                continue;
            }

            if ($entry) {
                $entry->update(['steps' => $count]);
            } else {
                $request->user()->steps()->create([
                    'recorded_on' => $date,
                    'steps' => $count,
                ]);
            }
        }

        return back()->with('status', 'Steps saved.');
    }

    private function day(mixed $date): ?Carbon
    {
        if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        try {
            $day = Carbon::createFromFormat('!Y-m-d', $date)?->startOfDay();
        } catch (Throwable) {
            return null;
        }

        if ($day === null || $day->format('Y-m-d') !== $date) {
            return null;
        }

        return $day;
    }
}
