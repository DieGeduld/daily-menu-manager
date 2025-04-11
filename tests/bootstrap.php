<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Setup Brain\Monkey
use Brain\Monkey;

// Define setup and teardown functions that will be used in test classes
trait BrainMonkeySetup {
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }
}

// Mock WordPress functions that we use
Brain\Monkey\Functions\stubs([
    'add_submenu_page' => true,
    'check_admin_referer' => true,
    'sanitize_text_field' => function($input) { return $input; },
    'sanitize_hex_color' => function($input) { return $input; },
    'add_settings_error' => true,
    'get_locale' => function() { return 'de_DE'; },
    '__' => function($text, $domain = 'default') { return $text; },
]);