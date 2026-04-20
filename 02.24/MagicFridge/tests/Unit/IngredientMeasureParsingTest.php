<?php

namespace Tests\Unit;

use App\Http\Controllers\RecipeController;
use App\Http\Controllers\ShoppingListController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class IngredientMeasureParsingTest extends TestCase
{
    public function test_recipe_preparation_words_keep_piece_quantity(): void
    {
        [$qty, $unit] = $this->callPrivate(new RecipeController(), 'parseMeasureToQtyUnit', ['2 finely chopped']);

        $this->assertSame(2.0, $qty);
        $this->assertSame('pcs', $unit);
    }

    public function test_recipe_plain_number_means_piece_quantity(): void
    {
        [$qty, $unit] = $this->callPrivate(new RecipeController(), 'parseMeasureToQtyUnit', ['2']);

        $this->assertSame(2.0, $qty);
        $this->assertSame('pcs', $unit);
    }

    public function test_recipe_spice_teaspoons_can_compare_to_grams(): void
    {
        [$needQty, $needUnit] = $this->callPrivate(new RecipeController(), 'comparableAmount', ['Cumin seeds', 2.0, 'tsp']);
        [$stockQty, $stockUnit] = $this->callPrivate(new RecipeController(), 'comparableAmount', ['Cumin seeds', 100.0, 'g']);

        $this->assertSame('g', $needUnit);
        $this->assertSame('g', $stockUnit);
        $this->assertGreaterThanOrEqual($needQty, $stockQty);
    }

    public function test_recipe_rice_cups_can_compare_to_grams(): void
    {
        [$needQty, $needUnit] = $this->callPrivate(new RecipeController(), 'comparableAmount', ['Basmati Rice', 2.0, 'cups']);
        [$stockQty, $stockUnit] = $this->callPrivate(new RecipeController(), 'comparableAmount', ['Basmati Rice', 500.0, 'g']);

        $this->assertSame('g', $needUnit);
        $this->assertSame('g', $stockUnit);
        $this->assertGreaterThanOrEqual($needQty, $stockQty);
    }

    public function test_recipe_yogurt_cups_can_compare_to_grams(): void
    {
        [$needQty, $needUnit] = $this->callPrivate(new RecipeController(), 'comparableAmount', ['Yogurt', 1.0, 'cup']);
        [$stockQty, $stockUnit] = $this->callPrivate(new RecipeController(), 'comparableAmount', ['Yogurt', 300.0, 'g']);

        $this->assertSame('g', $needUnit);
        $this->assertSame('g', $stockUnit);
        $this->assertGreaterThanOrEqual($needQty, $stockQty);
    }

    public function test_recipe_chicken_drumstick_pieces_can_compare_to_grams(): void
    {
        [$needQty, $needUnit] = $this->callPrivate(new RecipeController(), 'comparableAmount', ['Chicken drumsticks', 8.0, 'pcs']);
        [$stockQty, $stockUnit] = $this->callPrivate(new RecipeController(), 'comparableAmount', ['Chicken drumsticks', 1000.0, 'g']);

        $this->assertSame('g', $needUnit);
        $this->assertSame('g', $stockUnit);
        $this->assertSame(1000.0, $needQty);
        $this->assertGreaterThanOrEqual($needQty, $stockQty);
    }

    public function test_recipe_chicken_drumstick_pieces_can_deduct_from_grams(): void
    {
        [$qty, $ok] = $this->callPrivate(new RecipeController(), 'convertQty', [8.0, 'pcs', 'g', 'Chicken drumsticks']);

        $this->assertTrue($ok);
        $this->assertSame(1000.0, $qty);
    }

    public function test_recipe_rice_varieties_use_generic_rice_search_term(): void
    {
        $terms = $this->callPrivate(new RecipeController(), 'ingredientSearchTerms', ['Basmati Rice']);

        $this->assertContains('basmati rice', $terms);
        $this->assertContains('rice', $terms);
    }

    public function test_recipe_water_is_always_available(): void
    {
        $available = $this->callPrivate(new RecipeController(), 'hasEnoughIngredient', [123, 'Water', 200.0, 'ml']);

        $this->assertTrue($available);
    }

    public function test_own_recipe_ingredient_line_extracts_measure(): void
    {
        $parsed = $this->callPrivate(new RecipeController(), 'parseOwnIngredientLine', ['Basmati Rice (2 cups)']);

        $this->assertSame('Basmati Rice', $parsed['name']);
        $this->assertSame('2 cups', $parsed['measure']);
        $this->assertSame(2.0, $parsed['qty']);
        $this->assertSame('cups', $parsed['unit']);
    }

    public function test_shopping_preparation_words_keep_piece_quantity(): void
    {
        [$qty, $unit] = $this->callPrivate(new ShoppingListController(), 'parseMeasureToQtyUnit', ['5 thinly sliced']);

        $this->assertSame(5.0, $qty);
        $this->assertSame('pcs', $unit);
    }

    public function test_shopping_plain_number_means_piece_quantity(): void
    {
        [$qty, $unit] = $this->callPrivate(new ShoppingListController(), 'parseMeasureToQtyUnit', ['2']);

        $this->assertSame(2.0, $qty);
        $this->assertSame('pcs', $unit);
    }

    public function test_shopping_water_counts_as_already_available(): void
    {
        $available = $this->callPrivate(new ShoppingListController(), 'invContains', [[], 'víz']);

        $this->assertTrue($available);
    }

    private function callPrivate(object $object, string $method, array $args): mixed
    {
        $reflection = new ReflectionClass($object);
        $m = $reflection->getMethod($method);
        $m->setAccessible(true);

        return $m->invokeArgs($object, $args);
    }
}
