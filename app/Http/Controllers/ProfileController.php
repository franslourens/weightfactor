<?php

namespace App\Http\Controllers;

use App\Models\BodyFatEntry;
use App\Models\Profile;
use App\Models\WeightEntry;
use App\Support\BodyFat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Save the body stats used for the target weight and calorie goal.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sex' => ['required', Rule::in(['male', 'female'])],
            'age' => ['required', 'integer', 'min:15', 'max:100'],
            'height_cm' => ['required', 'numeric', 'min:120', 'max:230'],
            'body_fat_percent' => ['required', 'numeric', 'min:3', 'max:60'],
            'target_body_fat_percent' => ['required', 'numeric', 'min:3', 'max:60'],
            'activity' => ['required', Rule::in(array_keys(Profile::activities()))],
            'weekly_loss_kg' => ['required', 'numeric', 'min:0.1', 'max:1'],
        ]);

        $profile = Profile::query()->firstOrFail();
        $previous = number_format((float) $profile->body_fat_percent, 1);
        $profile->update($validated);

        if ($previous !== number_format((float) $validated['body_fat_percent'], 1)) {
            BodyFatEntry::record((float) $validated['body_fat_percent']);
        }

        return back()->with('status', 'Target updated.');
    }

    /**
     * Replace the saved body-fat percentage with the U.S. Navy estimate.
     */
    public function bodyFat(Request $request): RedirectResponse
    {
        $profile = Profile::query()->firstOrFail();
        $rules = [
            'neck_cm' => ['required', 'numeric', 'min:20', 'max:70'],
            'waist_cm' => ['required', 'numeric', 'min:40', 'max:200'],
        ];

        if ($profile->sex === 'female') {
            $rules['hip_cm'] = ['required', 'numeric', 'min:40', 'max:200'];
        }

        $measurements = $request->validate($rules);
        $neck = (float) $measurements['neck_cm'];
        $waist = (float) $measurements['waist_cm'];
        $hip = isset($measurements['hip_cm']) ? (float) $measurements['hip_cm'] : null;
        $circumference = $profile->sex === 'female' ? $waist + (float) $hip - $neck : $waist - $neck;

        if ($circumference <= 0) {
            throw ValidationException::withMessages([
                'waist_cm' => 'The waist measurement needs to be larger than the neck.',
            ]);
        }

        $navy = round(BodyFat::navy($profile->sex, (float) $profile->height_cm, $neck, $waist, $hip), 1);

        if ($navy < 3 || $navy > 60) {
            throw ValidationException::withMessages([
                'waist_cm' => 'Those measurements do not produce a usable body-fat percentage.',
            ]);
        }

        $profile->update(['body_fat_percent' => $navy]);
        BodyFatEntry::record($navy);

        $status = 'Body fat updated to '.number_format($navy, 1).'% ('.BodyFat::category($profile->sex, $navy).', U.S. Navy).';
        $latest = WeightEntry::query()->orderByDesc('recorded_on')->first();

        if ($latest) {
            $meters = (float) $profile->height_cm / 100;
            $bmi = (float) $latest->weight_kg / ($meters * $meters);
            $bmiMethod = round(BodyFat::bmiMethod($profile->sex, $bmi, (int) $profile->age), 1);
            $status .= ' BMI method estimates '.number_format($bmiMethod, 1).'%.';
        }

        return back()->with('status', $status);
    }
}
