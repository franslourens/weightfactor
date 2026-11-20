<x-layout>
    <section class="stats">
        <article>
            <h2>Latest weight</h2>
            <p class="figure">{{ $latest ? number_format((float) $latest->weight_kg, 2) : '—' }} <span>kg</span></p>
            <p class="meta">
                @if ($change === null)
                    Log two weigh-ins to see the change.
                @else
                    {{ $change > 0 ? '+' : '' }}{{ number_format($change, 2) }} kg since the previous weigh-in
                    @if ($fromStart !== null)
                        · {{ $fromStart > 0 ? '+' : '' }}{{ number_format($fromStart, 2) }} kg from the first
                    @endif
                @endif
            </p>
        </article>
        <article>
            <h2>Today</h2>
            <p class="figure">{{ number_format($todayCalories) }} <span>kcal</span></p>
            <p class="meta">
                {{ now()->format('l, M j') }}
                @if ($plan)
                    @php $remaining = $plan->calorieTarget() - $todayCalories; @endphp
                    · target <span id="today-target">{{ number_format($plan->calorieTarget()) }}</span>
                    · <span id="today-balance" data-eaten="{{ $todayCalories }}">{{ $remaining >= 0 ? number_format($remaining).' left' : number_format(abs($remaining)).' over' }}</span>
                @endif
            </p>
            <p class="meta">Protein {{ number_format($todayProtein, 1) }} g · Carbs {{ number_format($todayCarbs, 1) }} g · Fat {{ number_format($todayFat, 1) }} g</p>
        </article>
        <article>
            <h2>This week</h2>
            <p class="figure">{{ number_format($weekCalories) }} <span>kcal</span></p>
            <p class="meta">{{ \Illuminate\Support\Carbon::parse($weekStart)->format('D, M j') }} – {{ \Illuminate\Support\Carbon::parse($weekEnd)->format('D, M j') }}</p>
            <p class="meta">Protein {{ number_format($weekProtein, 1) }} g · Carbs {{ number_format($weekCarbs, 1) }} g · Fat {{ number_format($weekFat, 1) }} g</p>
        </article>
    </section>

    <section class="card plan">
        <h2>Target</h2>
        @if (! $plan)
            <p class="empty">Log a weigh-in to calculate the target weight.</p>
        @else
            <div class="facts">
                <div>
                    <span>Target weight</span>
                    <strong>{{ number_format($plan->targetWeightKg(), 2) }} kg</strong>
                    <p>at {{ number_format($plan->targetBodyFatPercent, 1) }}% body fat</p>
                </div>
                <div>
                    <span>To lose</span>
                    <strong>{{ number_format($plan->weightToLoseKg(), 2) }} kg</strong>
                    <p id="plan-weeks">{{ $plan->timeline() }}</p>
                </div>
                <div>
                    <span>Eat</span>
                    <strong id="plan-calories">{{ number_format($plan->calorieTarget()) }} kcal</strong>
                    <p id="plan-eat-note">{{ $plan->eatNote() }}</p>
                </div>
                <div>
                    <span>Maintenance</span>
                    <strong>{{ number_format($plan->tdee()) }} kcal</strong>
                    <p>estimated from your size and activity</p>
                </div>
            </div>
            <p class="hint">
                At {{ number_format($plan->weightKg, 2) }} kg and {{ number_format($plan->bodyFatPercent, 1) }}% fat you are carrying
                {{ number_format($plan->fatMassKg(), 2) }} kg of fat and {{ number_format($plan->leanMassKg(), 2) }} kg of everything else.
                Holding that lean mass and reaching {{ number_format($plan->targetBodyFatPercent, 1) }}% fat lands the scale at
                {{ number_format($plan->targetWeightKg(), 2) }} kg.
                Resting metabolism is about {{ number_format($plan->bmr()) }} kcal.
                {{ $plan->activityLabel() }} puts maintenance near {{ number_format($plan->tdee()) }} kcal.
                <span id="plan-pace">{{ $plan->paceSentence((int) round($plan->dailyAdjustment())) }}</span>
            </p>
            <div class="reference">
                <div>
                    <h3>Ideal weight</h3>
                    <strong>{{ $plan->idealWeightLowKg() }}–{{ $plan->idealWeightHighKg() }} kg</strong>
                    <p>From height alone. Muscle is not part of these formulas.</p>
                    <table>
                        @foreach ($plan->idealWeights() as $formula => $kilograms)
                            <tr>
                                <td>{{ $formula }}</td>
                                <td>{{ $kilograms }} kg</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                <div>
                    <h3>BMI</h3>
                    <strong>{{ number_format($plan->bmi(), 1) }}</strong>
                    <p>{{ $plan->bmiCategory() }}</p>
                    <table>
                        @foreach (['18.5 or less' => 'Underweight', '18.5–24.9' => 'Normal weight', '25–29.9' => 'Overweight', '30+' => 'Obese'] as $range => $label)
                            <tr @class(['current' => $label === $plan->bmiCategory()])>
                                <td>{{ $range }}</td>
                                <td>{{ $label }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                <div>
                    <h3>Muscular potential</h3>
                    @php $potential = $plan->muscularPotentialKg(); @endphp
                    <strong>{{ $potential[5] }} kg</strong>
                    <p>at 5% body fat, using Martin Berkhan’s height rule.</p>
                    <table>
                        <tr>
                            <td>5% body fat</td>
                            <td>{{ $potential[5] }} kg</td>
                        </tr>
                        <tr>
                            <td>10% body fat</td>
                            <td>{{ $potential[10] }} kg</td>
                        </tr>
                        <tr>
                            <td>15% body fat</td>
                            <td>{{ $potential[15] }} kg</td>
                        </tr>
                    </table>
                </div>
            </div>
        @endif

        @if ($profile)
            <form class="profile" method="post" action="{{ route('profile.update') }}" @guest onsubmit="return false" @endguest>
                @auth
                    @csrf
                    @method('PUT')
                @endauth
                @if ($plan)
                    <div class="deficit">
                        <div class="deficit-heading">
                            <label for="deficit-slider">Calorie deficit</label>
                            <strong id="deficit-readout">{{ number_format($plan->dailyAdjustment()) }} kcal</strong>
                        </div>
                        <input id="deficit-slider" type="range" min="0" max="{{ \App\Support\BodyPlan::DEFICIT_MAX }}" step="1" value="{{ $plan->nearestDeficit() }}">
                        <p class="hint">Weeks and the calories you need follow this deficit.</p>
                    </div>
                @endif
                <label>
                    Sex
                    <select name="sex" @disabled(auth()->guest())>
                        <option value="male" @selected(old('sex', $profile->sex) === 'male')>Male</option>
                        <option value="female" @selected(old('sex', $profile->sex) === 'female')>Female</option>
                    </select>
                </label>
                <label>
                    Age
                    <input type="number" name="age" min="15" max="100" value="{{ old('age', $profile->age) }}" @disabled(auth()->guest()) @required(auth()->check())>
                </label>
                <label>
                    Height (cm)
                    <input type="number" name="height_cm" min="120" max="230" step="any" value="{{ old('height_cm', $profile->height_cm) }}" @disabled(auth()->guest()) @required(auth()->check())>
                </label>
                <label>
                    Body fat %
                    <input type="number" name="body_fat_percent" min="3" max="60" step="any" value="{{ old('body_fat_percent', $profile->body_fat_percent) }}" @disabled(auth()->guest()) @required(auth()->check())>
                </label>
                <label>
                    Target body fat %
                    <input type="number" name="target_body_fat_percent" min="3" max="60" step="any" value="{{ old('target_body_fat_percent', $profile->target_body_fat_percent) }}" @disabled(auth()->guest()) @required(auth()->check())>
                </label>
                <label>
                    Activity
                    <select name="activity" @disabled(auth()->guest())>
                        @foreach (\App\Models\Profile::activities() as $key => $activity)
                            <option value="{{ $key }}" @selected(old('activity', $profile->activity) === $key)>{{ $activity['label'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Loss per week (kg)
                    @php
                        $weeklyLossValue = old('weekly_loss_kg', $profile->weekly_loss_kg);
                        $weeklyLossValue = rtrim(rtrim(number_format((float) $weeklyLossValue, 4, '.', ''), '0'), '.');
                    @endphp
                    <input type="number" name="weekly_loss_kg" min="0" max="2" step="any" value="{{ $weeklyLossValue === '' ? '0' : $weeklyLossValue }}" @disabled(auth()->guest()) @required(auth()->check())>
                </label>
                @auth
                    <button type="submit">Update target</button>
                @endauth
            </form>
            @foreach (['sex', 'age', 'height_cm', 'body_fat_percent', 'target_body_fat_percent', 'activity', 'weekly_loss_kg'] as $field)
                @error($field)
                    <p class="error">{{ $message }}</p>
                @enderror
            @endforeach
            @if ($plan)
                <script type="application/json" id="deficit-plan">@json($plan->deficitInputs())</script>
            @endif
        @endif

        <div class="body-fat-log">
            <h3>Body fat</h3>
            @if ($bodyFats->isEmpty())
                <p class="empty">No body-fat updates yet.</p>
            @else
                <ul class="log">
                    @foreach ($bodyFats as $entry)
                        <li>
                            <span>{{ $entry->recorded_at->timezone('Africa/Johannesburg')->format('M j, Y g:i A') }}</span>
                            <strong>{{ number_format((float) $entry->body_fat_percent, 1) }}%</strong>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @auth
            @if ($profile)
                <form class="body-fat" method="post" action="{{ route('body-fat.update') }}">
                    @csrf
                    <h3>Body fat calculator</h3>
                    <p class="hint">U.S. Navy method, using your saved sex, age, height, and latest weight. Measure to the nearest 0.5 cm.</p>
                    <label>
                        Neck (cm)
                        <input type="number" name="neck_cm" min="20" max="70" step="0.5" value="{{ old('neck_cm') }}" required>
                    </label>
                    <label>
                        Waist (cm)
                        <input type="number" name="waist_cm" min="40" max="200" step="0.5" value="{{ old('waist_cm') }}" required>
                    </label>
                    @if ($profile->sex === 'female')
                        <label>
                            Hip (cm)
                            <input type="number" name="hip_cm" min="40" max="200" step="0.5" value="{{ old('hip_cm') }}" required>
                        </label>
                    @endif
                    <button type="submit">Update body fat</button>
                    @foreach (['neck_cm', 'waist_cm', 'hip_cm'] as $field)
                        @error($field)
                            <p class="error">{{ $message }}</p>
                        @enderror
                    @endforeach
                </form>
            @endif
        @endauth
    </section>

    @auth
    <section class="grid">
        <form class="card" method="post" action="{{ route('weights.store') }}">
            @csrf
            <h2>Log weight</h2>
            <label>
                Date
                <input type="date" name="recorded_on" value="{{ old('recorded_on', $today) }}" required>
            </label>
            @error('recorded_on')
                <p class="error">{{ $message }}</p>
            @enderror
            <label>
                Weight (kg)
                <input type="number" name="weight_kg" min="20" max="400" step="0.01" value="{{ old('weight_kg') }}" required>
            </label>
            @error('weight_kg')
                <p class="error">{{ $message }}</p>
            @enderror
            <button type="submit">Save weight</button>
            <p class="hint">One weight per day. Saving the same date replaces it.</p>
        </form>

        <form class="card" method="post" action="{{ route('foods.store') }}">
            @csrf
            <h2>Log food</h2>
            <label>
                Date
                <input type="date" name="eaten_on" min="{{ $weekStart }}" max="{{ $weekEnd }}" value="{{ old('eaten_on', $today) }}" required>
            </label>
            @error('eaten_on')
                <p class="error">{{ $message }}</p>
            @enderror
            <p class="hint">Any day from this Sunday through Saturday.</p>
            <label class="lookup">
                Food
                <input id="food-name" type="text" name="name" maxlength="120" value="{{ old('name') }}" placeholder="Search oats, Nutella, or a barcode" autocomplete="off" required>
                <ul id="food-results" class="results" hidden></ul>
            </label>
            @error('name')
                <p class="error">{{ $message }}</p>
            @enderror
            <p id="food-serving" class="hint"></p>
            <label>
                Calories
                <input id="food-calories" type="number" name="calories" min="0" max="20000" step="1" value="{{ old('calories') }}" required>
            </label>
            @error('calories')
                <p class="error">{{ $message }}</p>
            @enderror
            <div class="macro-fields">
                <label>
                    Protein (g)
                    <input id="food-protein" type="number" name="protein_g" min="0" max="500" step="0.1" value="{{ old('protein_g') }}" required>
                </label>
                <label>
                    Carbs (g)
                    <input id="food-carbs" type="number" name="carbs_g" min="0" max="1000" step="0.1" value="{{ old('carbs_g') }}" required>
                </label>
                <label>
                    Fat (g)
                    <input id="food-fat" type="number" name="fat_g" min="0" max="500" step="0.1" value="{{ old('fat_g') }}" required>
                </label>
            </div>
            @foreach (['protein_g', 'carbs_g', 'fat_g'] as $field)
                @error($field)
                    <p class="error">{{ $message }}</p>
                @enderror
            @endforeach
            <button type="submit">Add food</button>
            <p class="hint">Search fills calories, protein, carbs, and fat. You can edit them before saving.</p>
        </form>
    </section>
    @endauth

    <section class="grid">
        <article class="card">
            <h2>Weight</h2>
            <x-trend-chart :points="$weightPoints" />
            @if ($weights->isEmpty())
                <p class="empty">No weigh-ins yet.</p>
            @else
                <ul class="log">
                    @foreach ($weights as $weight)
                        <li>
                            <span>{{ $weight->recorded_on->format('M j, Y') }}</span>
                            <strong>{{ number_format((float) $weight->weight_kg, 2) }} kg</strong>
                            @auth
                                <form method="post" action="{{ route('weights.destroy', $weight) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text">Remove</button>
                                </form>
                            @endauth
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>

        <article class="card">
            <h2>Calories, last 14 days</h2>
            <x-bar-chart :points="$caloriePoints" />
            @guest
                <p class="figure">{{ number_format(array_sum(array_column($caloriePoints, 'value'))) }} <span>kcal</span></p>
            @endguest
            @auth
                @if ($foods->isEmpty())
                    <p class="empty">No food logged yet.</p>
                @else
                    <ul class="log">
                        @foreach ($foods as $food)
                            <li>
                                <span>
                                    {{ $food->eaten_on->format('M j') }} · {{ $food->name }}
                                    <small>P {{ number_format($food->totalProtein(), 1) }} g · C {{ number_format($food->totalCarbs(), 1) }} g · F {{ number_format($food->totalFat(), 1) }} g · × {{ $food->quantityLabel() }}</small>
                                </span>
                                <strong>{{ number_format($food->totalCalories()) }} kcal</strong>
                                <div class="actions">
                                    <form class="quantity" method="post" action="{{ route('foods.update', $food) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="number" name="quantity" min="0.1" max="20" step="0.1" value="{{ $food->quantity }}" aria-label="Quantity for {{ $food->name }}" required>
                                        <button type="submit">Save</button>
                                    </form>
                                    <form method="post" action="{{ route('foods.destroy', $food) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text">Remove</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endauth
        </article>
    </section>
    @auth
        @push('scripts')
            <script src="{{ asset('js/food-search.js') }}?v={{ filemtime(public_path('js/food-search.js')) }}"></script>
        @endpush
    @endauth
    @if ($plan)
        @push('scripts')
            <script src="{{ asset('js/deficit-slider.js') }}?v={{ filemtime(public_path('js/deficit-slider.js')) }}"></script>
        @endpush
    @endif
</x-layout>
