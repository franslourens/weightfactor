<?php

namespace App\Support;

class FoodCatalog
{
    /**
     * Common foods with calories and macros for one usual serving.
     *
     * @return array<int, array{name: string, calories: int, protein: float, carbs: float, fat: float, serving: string}>
     */
    public static function foods(): array
    {
        return [
            ['name' => 'Apple', 'calories' => 95, 'protein' => 0.5, 'carbs' => 25.0, 'fat' => 0.3, 'serving' => '1 medium'],
            ['name' => 'Avocado', 'calories' => 120, 'protein' => 1.5, 'carbs' => 6.0, 'fat' => 11.0, 'serving' => '1/2 fruit'],
            ['name' => 'Banana', 'calories' => 105, 'protein' => 1.3, 'carbs' => 27.0, 'fat' => 0.4, 'serving' => '1 medium'],
            ['name' => 'Beef mince, 10% fat, cooked', 'calories' => 212, 'protein' => 26.0, 'carbs' => 0.0, 'fat' => 12.0, 'serving' => '100 g'],
            ['name' => 'Blueberries', 'calories' => 84, 'protein' => 1.1, 'carbs' => 21.0, 'fat' => 0.5, 'serving' => '1 cup'],
            ['name' => 'Broccoli, cooked', 'calories' => 55, 'protein' => 3.7, 'carbs' => 11.0, 'fat' => 0.6, 'serving' => '1 cup'],
            ['name' => 'Butter', 'calories' => 102, 'protein' => 0.1, 'carbs' => 0.0, 'fat' => 11.5, 'serving' => '1 tbsp'],
            ['name' => 'Cheddar cheese', 'calories' => 114, 'protein' => 7.0, 'carbs' => 0.4, 'fat' => 9.4, 'serving' => '28 g'],
            ['name' => 'Chicken breast, cooked', 'calories' => 165, 'protein' => 31.0, 'carbs' => 0.0, 'fat' => 3.6, 'serving' => '100 g'],
            ['name' => 'Chicken thigh, cooked', 'calories' => 209, 'protein' => 26.0, 'carbs' => 0.0, 'fat' => 10.9, 'serving' => '100 g'],
            ['name' => 'Cola', 'calories' => 139, 'protein' => 0.0, 'carbs' => 35.0, 'fat' => 0.0, 'serving' => '330 ml'],
            ['name' => 'Cottage cheese, low fat', 'calories' => 163, 'protein' => 28.0, 'carbs' => 6.1, 'fat' => 2.3, 'serving' => '1 cup'],
            ['name' => 'Dark chocolate', 'calories' => 120, 'protein' => 1.6, 'carbs' => 10.0, 'fat' => 8.0, 'serving' => '20 g'],
            ['name' => 'Egg', 'calories' => 72, 'protein' => 6.3, 'carbs' => 0.4, 'fat' => 4.8, 'serving' => '1 large'],
            ['name' => 'Greek yogurt, plain nonfat', 'calories' => 100, 'protein' => 17.3, 'carbs' => 6.1, 'fat' => 0.7, 'serving' => '170 g'],
            ['name' => 'Honey', 'calories' => 64, 'protein' => 0.1, 'carbs' => 17.3, 'fat' => 0.0, 'serving' => '1 tbsp'],
            ['name' => 'Lentils, cooked', 'calories' => 230, 'protein' => 17.9, 'carbs' => 39.9, 'fat' => 0.8, 'serving' => '1 cup'],
            ['name' => 'Milk, 2%', 'calories' => 122, 'protein' => 8.1, 'carbs' => 12.0, 'fat' => 4.8, 'serving' => '1 cup'],
            ['name' => 'Milk, whole', 'calories' => 149, 'protein' => 7.7, 'carbs' => 12.0, 'fat' => 7.9, 'serving' => '1 cup'],
            ['name' => 'Oat milk', 'calories' => 120, 'protein' => 3.0, 'carbs' => 16.0, 'fat' => 5.0, 'serving' => '1 cup'],
            ['name' => 'Oats, dry', 'calories' => 150, 'protein' => 5.0, 'carbs' => 27.0, 'fat' => 3.0, 'serving' => '40 g'],
            ['name' => 'Olive oil', 'calories' => 119, 'protein' => 0.0, 'carbs' => 0.0, 'fat' => 13.5, 'serving' => '1 tbsp'],
            ['name' => 'Orange', 'calories' => 62, 'protein' => 1.2, 'carbs' => 15.4, 'fat' => 0.2, 'serving' => '1 medium'],
            ['name' => 'Pasta, cooked', 'calories' => 221, 'protein' => 8.1, 'carbs' => 43.2, 'fat' => 1.3, 'serving' => '1 cup'],
            ['name' => 'Peanut butter', 'calories' => 94, 'protein' => 4.0, 'carbs' => 3.2, 'fat' => 8.0, 'serving' => '1 tbsp'],
            ['name' => 'Potato, baked', 'calories' => 161, 'protein' => 4.3, 'carbs' => 36.6, 'fat' => 0.2, 'serving' => '1 medium'],
            ['name' => 'Quinoa, cooked', 'calories' => 222, 'protein' => 8.1, 'carbs' => 39.4, 'fat' => 3.6, 'serving' => '1 cup'],
            ['name' => 'Rice, white, cooked', 'calories' => 206, 'protein' => 4.3, 'carbs' => 44.5, 'fat' => 0.4, 'serving' => '1 cup'],
            ['name' => 'Salmon, cooked', 'calories' => 208, 'protein' => 20.0, 'carbs' => 0.0, 'fat' => 13.0, 'serving' => '100 g'],
            ['name' => 'Slice of bread', 'calories' => 79, 'protein' => 2.7, 'carbs' => 14.0, 'fat' => 1.0, 'serving' => '1 slice'],
            ['name' => 'Sweet potato, baked', 'calories' => 112, 'protein' => 2.0, 'carbs' => 26.0, 'fat' => 0.1, 'serving' => '1 medium'],
            ['name' => 'Tuna, canned in water', 'calories' => 116, 'protein' => 25.5, 'carbs' => 0.0, 'fat' => 0.8, 'serving' => '100 g'],
            ['name' => 'Turkey breast, cooked', 'calories' => 135, 'protein' => 30.0, 'carbs' => 0.0, 'fat' => 0.7, 'serving' => '100 g'],
            ['name' => 'Whey protein', 'calories' => 120, 'protein' => 24.0, 'carbs' => 3.0, 'fat' => 1.5, 'serving' => '1 scoop, 30 g'],
        ];
    }
}
