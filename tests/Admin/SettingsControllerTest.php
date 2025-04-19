<?php

namespace DailyMenuManager\Tests\Admin;

use Brain\Monkey\Functions;
use DailyMenuManager\Controller\Admin\SettingsController;
use DailyMenuManager\Model\Settings;
use Mockery;
use PHPUnit\Framework\TestCase;

class SettingsControllerTest extends TestCase
{
    use \BrainMonkeySetup;

    public function test_format_price_with_euro_comma_right()
    {
        // Mock static method calls
        $settings = Mockery::mock('overload:' . Settings::class);
        $settings->shouldReceive('init')->andReturn(null);
        $settings->shouldReceive('getInstance')->andReturn($settings);
        $settings->shouldReceive('get')
            ->with('price_format')
            ->andReturn('symbol_comma_right');
        $settings->shouldReceive('get')
            ->with('currency')
            ->andReturn('EUR');

        $formattedPrice = SettingsController::formatPrice(9.99);
        $this->assertEquals('9,99 €', $formattedPrice);
    }

    public function test_format_price_with_usd_dot_left()
    {
        $settings = Mockery::mock('overload:' . Settings::class);
        $settings->shouldReceive('init')->andReturn(null);
        $settings->shouldReceive('getInstance')->andReturn($settings);
        $settings->shouldReceive('get')
            ->with('price_format')
            ->andReturn('symbol_dot_left');
        $settings->shouldReceive('get')
            ->with('currency')
            ->andReturn('USD');

        $formattedPrice = SettingsController::formatPrice(9.99);
        $this->assertEquals('$ 9.99', $formattedPrice);
    }

    public function test_format_price_with_custom_currency()
    {
        $settings = Mockery::mock('overload:' . Settings::class);
        $settings->shouldReceive('init')->andReturn(null);
        $settings->shouldReceive('getInstance')->andReturn($settings);
        $settings->shouldReceive('get')
            ->with('price_format')
            ->andReturn('symbol_comma_right');
        $settings->shouldReceive('get')
            ->with('currency')
            ->andReturn('custom');
        $settings->shouldReceive('get')
            ->with('custom_currency_symbol', '€')
            ->andReturn('Fr.');

        $formattedPrice = SettingsController::formatPrice(9.99);
        $this->assertEquals('9,99 Fr.', $formattedPrice);
    }

    public function test_get_currency_symbol()
    {
        $settings = Mockery::mock('overload:' . Settings::class);
        $settings->shouldReceive('init')->andReturn(null);
        $settings->shouldReceive('getInstance')->andReturn($settings);
        $settings->shouldReceive('get')
            ->with('currency')
            ->andReturn('EUR');

        $symbol = SettingsController::getCurrencySymbol();
        $this->assertEquals('€', $symbol);
    }

    public function test_get_consumption_types_with_defaults()
    {
        Functions\expect('__')
            ->andReturnUsing(function ($text) { return $text; });

        $settings = Mockery::mock('overload:' . Settings::class);
        $settings->shouldReceive('init')->andReturn(null);
        $settings->shouldReceive('getInstance')->andReturn($settings);
        $settings->shouldReceive('get')
            ->with('consumption_types')
            ->andReturn([]);
        $settings->shouldReceive('set')
            ->with('consumption_types', Mockery::type('array'))
            ->andReturn(true);

        $types = SettingsController::getConsumptionTypes();
        $this->assertIsArray($types);
        $this->assertCount(2, $types);
        $this->assertEquals('Pick up', $types[0]);
        $this->assertEquals('Eat in', $types[1]);
    }

    public function test_get_menu_properties_with_defaults()
    {
        Functions\expect('__')
            ->andReturnUsing(function ($text) { return $text; });

        $settings = Mockery::mock('overload:' . Settings::class);
        $settings->shouldReceive('init')->andReturn(null);
        $settings->shouldReceive('getInstance')->andReturn($settings);
        $settings->shouldReceive('get')
            ->with('menu_properties')
            ->andReturn([]);
        $settings->shouldReceive('set')
            ->with('menu_properties', Mockery::type('array'))
            ->andReturn(true);

        $properties = SettingsController::getMenuProperties();
        $this->assertIsArray($properties);
        $this->assertCount(3, $properties);
        $this->assertEquals('Vegetarian', $properties[0]);
        $this->assertEquals('Vegan', $properties[1]);
        $this->assertEquals('Glutenfree', $properties[2]);
    }
}
