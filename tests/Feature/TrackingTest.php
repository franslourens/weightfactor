<?php

namespace Tests\Feature;

use App\Mail\PasswordChanged;
use App\Mail\Welcome;
use App\Models\BodyFatEntry;
use App\Models\FoodEntry;
use App\Models\Profile;
use App\Models\User;
use App\Models\WeightEntry;
use App\Support\BodyFat;
use App\Support\BodyPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        config(['owner.email' => $this->owner->email]);

        Profile::query()->whereNull('user_id')->update(['user_id' => $this->owner->id]);
        WeightEntry::query()->whereNull('user_id')->update(['user_id' => $this->owner->id]);
        FoodEntry::query()->whereNull('user_id')->update(['user_id' => $this->owner->id]);
        BodyFatEntry::query()->whereNull('user_id')->update(['user_id' => $this->owner->id]);
    }

    private function asOwner(): static
    {
        return $this->actingAs($this->owner);
    }

    public function test_dashboard_loads(): void
    {
        WeightEntry::query()->create([
            'user_id' => $this->owner->id,
            'recorded_on' => '2026-11-19',
            'weight_kg' => 99.55,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Weight Factor')
            ->assertSee('99.55 kg')
            ->assertSee('Log in')
            ->assertSee('75–85 kg')
            ->assertSee('28.5')
            ->assertSee('87 kg')
            ->assertSee('Body fat %')
            ->assertSee('Loss per week (kg)')
            ->assertSee('Light exercise, 1–3 workouts a week')
            ->assertDontSee('Save weight')
            ->assertDontSee('Add food')
            ->assertDontSee('Update target')
            ->assertDontSee('Update body fat')
            ->assertDontSee('Remove');

        $this->asOwner()->get('/')
            ->assertSee('Save weight')
            ->assertSee('Add food')
            ->assertSee('Update target')
            ->assertSee('Update body fat')
            ->assertSee('Remove');
    }

    public function test_guests_cannot_change_weight_food_or_target(): void
    {
        $this->post('/weights', [
            'recorded_on' => '2026-11-19',
            'weight_kg' => '99.55',
        ])->assertRedirect('/login');

        $this->post('/foods', [
            'eaten_on' => '2026-11-19',
            'name' => 'Oats',
            'calories' => 150,
            'protein_g' => 5,
            'carbs_g' => 27,
            'fat_g' => 3,
        ])->assertRedirect('/login');

        $this->put('/profile', [
            'sex' => 'male',
            'age' => 40,
            'height_cm' => 187,
            'body_fat_percent' => 20,
            'target_body_fat_percent' => 15,
            'activity' => 'light',
            'weekly_loss_kg' => 0.5,
        ])->assertRedirect('/login');

        $this->assertSame(0, WeightEntry::query()->count());
        $this->assertSame(0, FoodEntry::query()->count());
        $this->assertSame('23.8', Profile::query()->first()->body_fat_percent);
    }

    public function test_body_fat_calculator_updates_the_saved_percentage(): void
    {
        $this->assertEqualsWithDelta(15.3, BodyFat::navy('male', 70.5 * 2.54, 19.5 * 2.54, 37.5 * 2.54), 0.05);
        $this->assertEqualsWithDelta(15.4, BodyFat::bmiMethod('male', 21.501247644515, 25), 0.05);
        $this->assertEqualsWithDelta(24.9, BodyFat::navy('female', 165, 32, 70, 95), 0.05);

        WeightEntry::query()->create([
            'user_id' => $this->owner->id,
            'recorded_on' => '2026-11-19',
            'weight_kg' => 99.55,
        ]);

        $this->post('/body-fat', [
            'neck_cm' => 40,
            'waist_cm' => 96,
        ])->assertRedirect('/login');

        $this->asOwner()->post('/body-fat', [
            'neck_cm' => 40,
            'waist_cm' => 96,
        ])->assertRedirect('/')
            ->assertSessionHas('status', 'Body fat updated to 21.4% (Average, U.S. Navy). BMI method estimates 27.2%.');

        $profile = Profile::query()->first();
        $this->assertSame('21.4', $profile->body_fat_percent);
        $this->assertSame('20.0', $profile->target_body_fat_percent);
        $this->assertSame(2, BodyFatEntry::query()->count());
        $this->get('/')
            ->assertSee('21.4%')
            ->assertSee('23.8%');
    }

    public function test_food_quantity_updates_the_logged_line(): void
    {
        $food = FoodEntry::query()->create([
            'user_id' => $this->owner->id,
            'eaten_on' => now()->toDateString(),
            'name' => 'Oats with milk',
            'calories' => 350,
            'protein_g' => 12,
            'carbs_g' => 50,
            'fat_g' => 8,
        ]);

        $this->patch(route('foods.update', $food), [
            'quantity' => 2,
        ])->assertRedirect('/login');

        $this->assertSame('1.00', $food->fresh()->quantity);

        $this->asOwner()->patch(route('foods.update', $food), [
            'quantity' => 2,
        ])->assertRedirect('/')
            ->assertSessionHas('status', 'Quantity saved.');

        $this->get('/')
            ->assertSee('× 2')
            ->assertSee('700 kcal')
            ->assertSee('P 24.0 g')
            ->assertSee('C 100.0 g')
            ->assertSee('F 16.0 g');
    }

    public function test_owner_can_log_in_and_out(): void
    {
        $this->post('/login', [
            'email' => $this->owner->email,
            'password' => 'password',
        ])->assertRedirect('/');

        $this->get('/')->assertSee('Log out');

        $this->post('/logout')->assertRedirect('/');

        $this->get('/')->assertSee('Log in')->assertDontSee('Save weight');
    }

    public function test_a_weigh_in_replaces_the_same_day(): void
    {
        $this->asOwner()->post('/weights', [
            'recorded_on' => '2026-11-19',
            'weight_kg' => '82.4',
        ])->assertRedirect('/');

        $this->asOwner()->post('/weights', [
            'recorded_on' => '2026-11-19',
            'weight_kg' => '82.1',
        ])->assertRedirect('/');

        $this->assertSame(1, WeightEntry::query()->count());
        $this->assertSame('82.10', WeightEntry::query()->first()->weight_kg);

        $this->get('/')->assertSee('82.10 kg');
    }

    public function test_food_calories_are_logged_for_the_day(): void
    {
        $this->asOwner()->post('/foods', [
            'eaten_on' => now()->toDateString(),
            'name' => 'Oats with milk',
            'calories' => 350,
            'protein_g' => 12,
            'carbs_g' => 50,
            'fat_g' => 8,
        ])->assertRedirect('/');

        $this->assertSame(1, FoodEntry::query()->count());
        $this->get('/')
            ->assertSee('Oats with milk')
            ->assertSee('350')
            ->assertSee('P 12.0 g')
            ->assertSee('C 50.0 g')
            ->assertSee('F 8.0 g');

        auth()->logout();

        $this->get('/')
            ->assertSee('Calories, last 14 days')
            ->assertSee('350')
            ->assertDontSee('Oats with milk');
    }

    public function test_food_can_be_logged_for_the_monday_week_and_totals_it(): void
    {
        Carbon::setTestNow('2026-11-19');

        try {
            $this->asOwner()->post('/foods', [
                'eaten_on' => '2026-11-16',
                'name' => 'Monday oats',
                'calories' => 200,
                'protein_g' => 10,
                'carbs_g' => 30,
                'fat_g' => 4,
            ])->assertRedirect('/');

            $this->asOwner()->post('/foods', [
                'eaten_on' => '2026-11-22',
                'name' => 'Sunday chicken',
                'calories' => 300,
                'protein_g' => 40,
                'carbs_g' => 0,
                'fat_g' => 8,
            ])->assertRedirect('/');

            $this->asOwner()->post('/foods', [
                'eaten_on' => '2026-11-15',
                'name' => 'Last week',
                'calories' => 900,
                'protein_g' => 1,
                'carbs_g' => 1,
                'fat_g' => 1,
            ])->assertSessionHasErrors('eaten_on');

            $this->asOwner()->post('/foods', [
                'eaten_on' => '2026-11-23',
                'name' => 'Next Monday',
                'calories' => 100,
                'protein_g' => 1,
                'carbs_g' => 1,
                'fat_g' => 1,
            ])->assertSessionHasErrors('eaten_on');

            $this->get('/')
                ->assertSee('Mon, Nov 16')
                ->assertSee('Sun, Nov 22')
                ->assertSee('Protein 50.0 g')
                ->assertSee('Carbs 30.0 g')
                ->assertSee('Fat 12.0 g')
                ->assertDontSee('Last week');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_entries_can_be_removed(): void
    {
        $weight = WeightEntry::query()->create([
            'user_id' => $this->owner->id,
            'recorded_on' => '2026-11-18',
            'weight_kg' => 81.5,
        ]);
        $food = FoodEntry::query()->create([
            'user_id' => $this->owner->id,
            'eaten_on' => '2026-11-18',
            'name' => 'Eggs',
            'calories' => 180,
        ]);

        $this->asOwner()->delete(route('weights.destroy', $weight))->assertRedirect('/');
        $this->asOwner()->delete(route('foods.destroy', $food))->assertRedirect('/');

        $this->assertSame(0, WeightEntry::query()->count());
        $this->assertSame(0, FoodEntry::query()->count());
    }

    public function test_weight_keeps_two_decimal_places(): void
    {
        $this->asOwner()->post('/weights', [
            'recorded_on' => '2026-11-19',
            'weight_kg' => '99.55',
        ])->assertRedirect('/');

        $this->assertSame('99.55', WeightEntry::query()->first()->weight_kg);
        $this->get('/')->assertSee('99.55 kg');
    }

    public function test_target_weight_and_calorie_goal_use_the_profile(): void
    {
        $this->asOwner()->post('/weights', [
            'recorded_on' => '2026-11-19',
            'weight_kg' => '99.55',
        ])->assertRedirect('/');

        $plan = BodyPlan::fromProfile(Profile::query()->first(), 99.55);

        $this->assertEqualsWithDelta(94.82, $plan->targetWeightKg(), 0.01);
        $this->assertSame(2158, $plan->calorieTarget());
        $this->assertSame(2708, (int) round($plan->tdee()));
        $this->assertSame([
            'G.J. Hamwi (1964)' => 85,
            'B.J. Devine (1974)' => 81,
            'J.D. Robinson (1983)' => 78,
            'D.R. Miller (1983)' => 75,
        ], $plan->idealWeights());
        $this->assertEqualsWithDelta(28.5, $plan->bmi(), 0.01);
        $this->assertSame('Overweight', $plan->bmiCategory());
        $this->assertSame([5 => 87, 10 => 91, 15 => 96], $plan->muscularPotentialKg());

        $this->assertSame(550, $plan->nearestDeficit());
        $this->assertSame(2698, $plan->withDeficit(10)->calorieTarget());
        $this->assertGreaterThan($plan->withDeficit(550)->weeks(), $plan->withDeficit(10)->weeks());
        $this->assertSame(2208, $plan->withDeficit(500)->calorieTarget());
        $this->assertSame(1708, $plan->withDeficit(1000)->calorieTarget());
        $this->assertSame(1500, $plan->withDeficit(1500)->calorieTarget());
        $this->assertTrue($plan->withDeficit(1500)->calorieTargetIsFloored());
        $this->assertGreaterThan($plan->withDeficit(1000)->weeks(), $plan->withDeficit(500)->weeks());
        $this->assertSame(0.0, $plan->withDeficit(0)->weeks());

        foreach ([500, 1000, 1500, 2000] as $deficit) {
            $projected = $plan->withDeficit($deficit);
            $stored = (float) number_format($projected->weeklyLossKg, 4, '.', '');
            $reloaded = new BodyPlan(
                $plan->weightKg,
                $plan->bodyFatPercent,
                $plan->targetBodyFatPercent,
                $plan->sex,
                $plan->age,
                $plan->heightCm,
                $plan->activity,
                $stored,
            );

            $this->assertSame($projected->calorieTarget(), $reloaded->calorieTarget());
        }

        $this->get('/')
            ->assertSee('94.82 kg')
            ->assertSee('2,158 kcal')
            ->assertSee('2,708 kcal')
            ->assertSee('Calorie deficit')
            ->assertSee('id="deficit-slider"', false)
            ->assertSee('max="1000" step="1"', false)
            ->assertDontSee('deficit-tick', false)
            ->assertSee('23.8%')
            ->assertSee('20.0%')
            ->assertSee('75–85 kg')
            ->assertSee('G.J. Hamwi (1964)')
            ->assertSee('28.5')
            ->assertSee('Overweight')
            ->assertSee('87 kg')
            ->assertSee('91 kg')
            ->assertSee('96 kg');
    }

    public function test_a_saved_calorie_deficit_updates_the_weeks_and_calories(): void
    {
        $this->asOwner()->post('/weights', [
            'recorded_on' => '2026-11-19',
            'weight_kg' => '99.55',
        ])->assertRedirect('/');

        $plan = BodyPlan::fromProfile(Profile::query()->first(), 99.55);
        $projected = $plan->withDeficit(1000);

        $this->asOwner()->put('/profile', [
            'sex' => 'male',
            'age' => 40,
            'height_cm' => 187,
            'body_fat_percent' => 23.8,
            'target_body_fat_percent' => 20,
            'activity' => 'light',
            'weekly_loss_kg' => number_format($projected->weeklyLossKg, 4, '.', ''),
        ])->assertRedirect('/')
            ->assertSessionHasNoErrors();

        $saved = BodyPlan::fromProfile(Profile::query()->first(), 99.55);
        $this->assertSame('0.9091', Profile::query()->first()->weekly_loss_kg);
        $this->get('/')
            ->assertSee('1,708 kcal')
            ->assertSee($saved->timeline());
    }

    public function test_food_lookup_includes_calories_and_macros(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'nal.usda.gov')) {
                return Http::response([
                    'foods' => [[
                        'description' => 'Bananas, raw',
                        'foodNutrients' => [
                            ['nutrientId' => 1008, 'value' => 89],
                            ['nutrientId' => 1003, 'value' => 1.09],
                            ['nutrientId' => 1005, 'value' => 22.8],
                            ['nutrientId' => 1004, 'value' => 0.33],
                        ],
                    ]],
                ]);
            }

            if (str_contains($request->url(), 'search.openfoodfacts.org')) {
                return Http::response([
                    'hits' => [[
                        'product_name' => 'Nutella',
                        'brands' => ['Ferrero'],
                        'nutriments' => [
                            'energy-kcal_100g' => 539,
                            'proteins_100g' => 6.3,
                            'carbohydrates_100g' => 57.5,
                            'fat_100g' => 30.9,
                        ],
                    ]],
                ]);
            }

            if (str_contains($request->url(), '/api/v2/product/')) {
                return Http::response([
                    'status' => 1,
                    'product' => [
                        'product_name' => 'Nutella',
                        'brands' => 'Ferrero',
                        'serving_size' => '15 g',
                        'nutriments' => [
                            'energy-kcal_serving' => 81,
                            'proteins_serving' => 0.9,
                            'carbohydrates_serving' => 8.6,
                            'fat_serving' => 4.6,
                        ],
                    ],
                ]);
            }

            return Http::response([], 404);
        });

        $this->asOwner()->getJson('/foods/search?q=banana')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Banana',
                'calories' => 105,
                'protein' => 1.3,
                'carbs' => 27,
                'fat' => 0.4,
                'serving' => '1 medium',
            ]);

        $this->asOwner()->getJson('/foods/search?q=plantain')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Bananas, raw',
                'calories' => 89,
                'protein' => 1.1,
                'carbs' => 22.8,
                'fat' => 0.3,
                'serving' => 'per 100 g',
                'source' => 'USDA',
            ]);

        $this->asOwner()->getJson('/foods/search?q=nutella')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Nutella · Ferrero',
                'calories' => 539,
                'protein' => 6.3,
                'carbs' => 57.5,
                'fat' => 30.9,
                'source' => 'Open Food Facts',
            ]);

        $this->asOwner()->getJson('/foods/search?q=3017620422003')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Nutella · Ferrero',
                'calories' => 81,
                'serving' => '15 g',
                'source' => 'Open Food Facts',
            ]);
    }

    public function test_a_logged_custom_food_can_be_searched_again(): void
    {
        Http::fake();

        FoodEntry::query()->create([
            'user_id' => $this->owner->id,
            'eaten_on' => '2026-11-18',
            'name' => 'Garage pie',
            'calories' => 420,
            'protein_g' => 12,
            'carbs_g' => 40,
            'fat_g' => 22,
        ]);

        $other = User::factory()->create();
        FoodEntry::query()->create([
            'user_id' => $other->id,
            'eaten_on' => '2026-11-18',
            'name' => 'Secret stew',
            'calories' => 300,
            'protein_g' => 20,
            'carbs_g' => 10,
            'fat_g' => 15,
        ]);

        $foods = $this->asOwner()->getJson('/foods/search?q=pie')->assertOk()->json();

        $this->assertSame('Garage pie', $foods[0]['name']);
        $this->assertSame(420, $foods[0]['calories']);
        $this->assertEquals(12, $foods[0]['protein']);
        $this->assertEquals(40, $foods[0]['carbs']);
        $this->assertEquals(22, $foods[0]['fat']);
        $this->assertSame('Your log', $foods[0]['source']);

        $shared = collect($this->asOwner()->getJson('/foods/search?q=secret')->json())
            ->firstWhere('name', 'Secret stew');
        $this->assertNotNull($shared);
        $this->assertSame(300, $shared['calories']);
        $this->assertSame('Logged by '.$other->name, $shared['source']);
    }

    public function test_packaged_food_prefers_the_whole_serving(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'search.openfoodfacts.org')) {
                return Http::response([
                    'hits' => [
                        [
                            'code' => '2000000002603',
                            'product_name' => 'Big Mac',
                            'brands' => "McDonald's",
                            'nutriments' => [
                                'energy-kcal_100g' => 228,
                                'proteins_100g' => 11.6,
                                'carbohydrates_100g' => 18.1,
                                'fat_100g' => 11.6,
                            ],
                        ],
                        [
                            'code' => '2000300002603',
                            'product_name' => 'Big Mac',
                            'nutriments' => [
                                'energy-kcal_100g' => 540,
                                'proteins_100g' => 24,
                                'carbohydrates_100g' => 44,
                                'fat_100g' => 29,
                            ],
                        ],
                    ],
                ]);
            }

            if (str_contains($request->url(), '/api/v2/product/2000000002603')) {
                return Http::response([
                    'status' => 1,
                    'product' => [
                        'product_name' => 'Big Mac',
                        'brands' => "McDonald's",
                        'serving_size' => '232 g',
                        'nutriments' => [
                            'energy-kcal_serving' => 530,
                            'proteins_serving' => 27,
                            'carbohydrates_serving' => 42,
                            'fat_serving' => 27,
                        ],
                    ],
                ]);
            }

            return Http::response(['status' => 0], 404);
        });

        $foods = $this->asOwner()->getJson('/foods/search?q=big mac')->assertOk()->json();

        $this->assertCount(1, array_filter($foods, fn (array $food) => str_contains($food['name'], 'Big Mac')));
        $this->assertEquals([
            'name' => "Big Mac · McDonald's",
            'calories' => 530,
            'protein' => 27,
            'carbs' => 42,
            'fat' => 27,
            'serving' => '232 g',
            'source' => 'Open Food Facts',
        ], collect($foods)->first(fn (array $food) => str_contains($food['name'], 'Big Mac')));
    }

    public function test_steers_burger_comes_from_the_south_african_menu(): void
    {
        Http::fake([
            'search.openfoodfacts.org/*' => Http::response([
                'hits' => [[
                    'product_name' => 'Burger Knäckebrot',
                    'brands' => 'Burger',
                    'nutriments' => ['energy-kcal_100g' => 329, 'proteins_100g' => 8.3, 'carbohydrates_100g' => 60, 'fat_100g' => 1.5],
                ]],
            ]),
            'api.nal.usda.gov/*' => Http::response(['foods' => []]),
        ]);

        $this->asOwner()->getJson('/foods/search?q=steers burger')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Steers Burger & chips',
                'calories' => 654,
                'protein' => 31,
                'carbs' => 76.7,
                'fat' => 25.7,
                'serving' => 'burger & small chips',
                'source' => 'Steers',
            ])
            ->assertJsonFragment([
                'name' => 'Phanda Steers Burger & chips',
                'calories' => 299,
                'serving' => 'burger & small chips',
                'source' => 'Steers',
            ])
            ->assertJsonMissing(['name' => 'Burger Knäckebrot']);
    }

    public function test_invalid_weight_is_rejected(): void
    {
        $this->asOwner()->post('/weights', [
            'recorded_on' => '2026-11-19',
            'weight_kg' => 'nope',
        ])->assertSessionHasErrors('weight_kg');
    }

    public function test_a_member_keeps_a_private_profile_and_the_dashboard_stays_the_owners(): void
    {
        WeightEntry::query()->create([
            'user_id' => $this->owner->id,
            'recorded_on' => '2026-11-19',
            'weight_kg' => 99.55,
        ]);

        $this->get('/register')->assertOk()->assertSee('Sign up');

        $this->post('/register', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
        ]);

        $alex = User::query()->where('email', 'alex@example.com')->first();
        $profileUrl = '/u/'.$alex->slug;

        $this->put('/profile', [
            'sex' => 'male',
            'age' => 30,
            'height_cm' => 175,
            'body_fat_percent' => 20,
            'target_body_fat_percent' => 15,
            'activity' => 'light',
            'weekly_loss_kg' => 0.5,
        ])->assertSessionHasErrors('weight');

        $this->get('/me')->assertRedirect($profileUrl);

        $this->get($profileUrl)
            ->assertOk()
            ->assertSee('Your profile')
            ->assertSee('Save weight')
            ->assertSee('Log your weight before you can update the target.')
            ->assertDontSee('Update target')
            ->assertDontSee('99.55 kg');

        $this->get('/')
            ->assertSee('99.55 kg')
            ->assertDontSee('Save weight')
            ->assertSee('Your profile');

        $this->from($profileUrl)->post('/weights', [
            'recorded_on' => '2026-11-20',
            'weight_kg' => '70.25',
        ])->assertRedirect($profileUrl);

        $this->get('/')->assertDontSee('70.25');
        $this->get($profileUrl)
            ->assertSee('70.25 kg')
            ->assertSee('Update target');

        $ownerWeight = WeightEntry::query()->where('user_id', $this->owner->id)->first();
        $this->delete(route('weights.destroy', $ownerWeight))->assertNotFound();

        $this->post('/logout');

        $this->post('/login', [
            'email' => 'alex@example.com',
            'password' => 'secret-pass',
        ])->assertRedirect($profileUrl);

        $this->asOwner()->get('/me')->assertRedirect('/');
    }

    public function test_a_user_can_update_their_account(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
        ]);

        $this->get('/account')
            ->assertOk()
            ->assertSee('Save account')
            ->assertSee('alex@example.com')
            ->assertDontSee('name="email"', false)
            ->assertSee('value="Alex"', false);

        $this->put('/account', [
            'name' => 'Alex Roux',
            'email' => 'alex.roux@example.com',
        ])->assertRedirect()
            ->assertSessionHas('status', 'Account saved.');

        $this->assertSame('alex@example.com', auth()->user()->fresh()->email);
        Mail::assertNotSent(PasswordChanged::class);

        $this->put('/account', [
            'name' => 'Alex Roux',
            'password' => 'newer-pass',
            'password_confirmation' => 'newer-pass',
            'current_password' => 'wrong-pass',
        ])->assertSessionHasErrors('current_password');
        Mail::assertNotSent(PasswordChanged::class);

        $this->put('/account', [
            'name' => 'Alex Roux',
            'password' => 'newer-pass',
            'password_confirmation' => 'newer-pass',
            'current_password' => 'secret-pass',
        ])->assertSessionHas('status', 'Account saved.');

        Mail::assertSent(PasswordChanged::class, function (PasswordChanged $mail) {
            $body = $mail->render();

            return $mail->hasTo('alex@example.com')
                && $mail->envelope()->subject === 'Your Weight Factor password was changed'
                && str_contains($body, 'password for your Weight Factor account was just changed')
                && ! str_contains($body, 'newer-pass');
        });

        $this->post('/logout');

        $this->post('/login', [
            'email' => 'alex@example.com',
            'password' => 'newer-pass',
        ])->assertRedirect();

        $alex = User::query()->where('email', 'alex@example.com')->first();
        $this->assertSame('Alex Roux', $alex->name);

        $this->post('/logout');

        $this->get('/u/'.$alex->slug)->assertSee("Alex Roux's profile", false);
    }

    public function test_signup_sends_a_welcome_email(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
        ]);

        $alex = User::query()->where('email', 'alex@example.com')->first();

        Mail::assertSent(Welcome::class, function (Welcome $mail) use ($alex) {
            return $mail->hasTo('alex@example.com')
                && $mail->user->is($alex)
                && str_contains($mail->render(), '/u/'.$alex->slug);
        });
    }

    public function test_a_member_can_share_a_public_profile_link(): void
    {
        $this->post('/register', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
        ]);

        $alex = User::query()->where('email', 'alex@example.com')->first();
        $this->assertNotNull($alex->slug);

        WeightEntry::query()->create([
            'user_id' => $alex->id,
            'recorded_on' => '2026-11-20',
            'weight_kg' => 70.25,
        ]);
        FoodEntry::query()->create([
            'user_id' => $alex->id,
            'eaten_on' => '2026-11-20',
            'name' => 'Secret stew',
            'calories' => 300,
            'protein_g' => 20,
            'carbs_g' => 10,
            'fat_g' => 15,
        ]);

        $this->get('/me')->assertRedirect('/u/'.$alex->slug);

        $this->get('/u/'.$alex->slug)
            ->assertOk()
            ->assertSee('70.25 kg')
            ->assertSee('Save weight');

        $this->post('/logout');

        $this->get('/u/'.$alex->slug)
            ->assertOk()
            ->assertSee("Alex's profile", false)
            ->assertSee('70.25 kg')
            ->assertDontSee('Save weight')
            ->assertDontSee('Update target')
            ->assertDontSee('Secret stew');

        $this->get('/')->assertDontSee('70.25 kg');
        $this->get('/u/missing-person')->assertNotFound();
    }
}
