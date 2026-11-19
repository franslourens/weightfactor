<?php

namespace App\Support;

class RestaurantMenus
{
    /**
     * South African restaurant meals. Steers publishes these as the burger with chips,
     * in kilojoules; calories here are kJ / 4.184. Phanda meals include small chips,
     * classic meals include small chips, and King Steer meals include medium chips.
     *
     * @return array<int, array{name: string, calories: int, protein: float, carbs: float, fat: float, serving: string, source: string}>
     */
    public static function foods(): array
    {
        $steers = 'Steers';
        $small = 'burger & small chips';
        $medium = 'burger & medium chips';

        return [
            ['name' => 'Phanda Steers Burger & chips', 'calories' => 299, 'protein' => 14.9, 'carbs' => 29.4, 'fat' => 14.6, 'serving' => $small, 'source' => $steers],
            ['name' => 'Phanda Rave Burger & chips', 'calories' => 292, 'protein' => 14.9, 'carbs' => 28.1, 'fat' => 14.6, 'serving' => $small, 'source' => $steers],
            ['name' => 'Phanda Cheese Burger & chips', 'calories' => 348, 'protein' => 17.8, 'carbs' => 29.9, 'fat' => 18.4, 'serving' => $small, 'source' => $steers],
            ['name' => 'Phanda King Steer Burger & chips', 'calories' => 461, 'protein' => 24.3, 'carbs' => 33.8, 'fat' => 26.3, 'serving' => $small, 'source' => $steers],
            ['name' => 'Phanda Cheesy BBQ Burger & chips', 'calories' => 336, 'protein' => 18.0, 'carbs' => 34.0, 'fat' => 15.0, 'serving' => $small, 'source' => $steers],
            ['name' => 'Phanda Cheesy BBQ Double Burger & chips', 'calories' => 454, 'protein' => 27.0, 'carbs' => 35.0, 'fat' => 25.0, 'serving' => $small, 'source' => $steers],
            ['name' => 'Phanda Cheesy BBQ Triple Burger & chips', 'calories' => 480, 'protein' => 39.0, 'carbs' => 37.0, 'fat' => 39.0, 'serving' => $small, 'source' => $steers],
            ['name' => 'Steers Burger & chips', 'calories' => 654, 'protein' => 31.0, 'carbs' => 76.7, 'fat' => 25.7, 'serving' => $small, 'source' => $steers],
            ['name' => 'Steers Rave Burger & chips', 'calories' => 583, 'protein' => 27.0, 'carbs' => 52.0, 'fat' => 24.0, 'serving' => $small, 'source' => $steers],
            ['name' => 'Steers Cheese Burger & chips', 'calories' => 702, 'protein' => 33.9, 'carbs' => 77.2, 'fat' => 29.5, 'serving' => $small, 'source' => $steers],
            ['name' => 'Steers Chilli Cheese Burger & chips', 'calories' => 702, 'protein' => 33.9, 'carbs' => 77.2, 'fat' => 29.5, 'serving' => $small, 'source' => $steers],
            ['name' => 'Steers Chicken Burger & chips', 'calories' => 570, 'protein' => 36.0, 'carbs' => 72.5, 'fat' => 9.2, 'serving' => $small, 'source' => $steers],
            ['name' => 'Steers Ribster Burger & chips', 'calories' => 695, 'protein' => 34.4, 'carbs' => 73.2, 'fat' => 29.0, 'serving' => $small, 'source' => $steers],
            ['name' => 'Steers Ribster Cheese Burger & chips', 'calories' => 744, 'protein' => 37.3, 'carbs' => 73.7, 'fat' => 32.8, 'serving' => $small, 'source' => $steers],
            ['name' => 'Steers Bacon & Cheese Burger & chips', 'calories' => 769, 'protein' => 39.0, 'carbs' => 74.3, 'fat' => 36.6, 'serving' => $small, 'source' => $steers],
            ['name' => 'Steers Prince Burger & chips', 'calories' => 726, 'protein' => 34.1, 'carbs' => 78.4, 'fat' => 29.6, 'serving' => $small, 'source' => $steers],
            ['name' => 'Steers Mjojo Burger & chips', 'calories' => 732, 'protein' => 37.6, 'carbs' => 82.5, 'fat' => 29.1, 'serving' => $small, 'source' => $steers],
            ['name' => "Steers Mo' Cheese Burger & chips", 'calories' => 817, 'protein' => 41.0, 'carbs' => 79.0, 'fat' => 37.0, 'serving' => $small, 'source' => $steers],
            ['name' => 'Steers Original King Steer Burger & chips', 'calories' => 1033, 'protein' => 55.4, 'carbs' => 83.1, 'fat' => 53.5, 'serving' => $medium, 'source' => $steers],
            ['name' => 'Steers Big Bacon King Steer Burger & chips', 'calories' => 1113, 'protein' => 60.4, 'carbs' => 84.1, 'fat' => 60.5, 'serving' => $medium, 'source' => $steers],
            ['name' => 'Steers Mighty Burger & chips', 'calories' => 1341, 'protein' => 76.7, 'carbs' => 87.7, 'fat' => 77.5, 'serving' => $medium, 'source' => $steers],
        ];
    }
}
