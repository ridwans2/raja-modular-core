# Testing Modules

Laravel Modular seamlessly integrates with **Pest** (recommended) and PHPUnit.

## Test Structure

Every module has its own `tests/` folder.

```text
modules/Shop/
└── tests/
    ├── Feature/
    ├── Unit/
    ├── Pest.php      <-- Module-specific test configuration
    └── TestCase.php  <-- Base test case
```

## Running Tests

### 1. Running All Tests (Global)

As of **v1.1.5**, Laravel Modular provides **Native Testing Support**. You do not need to use `modular:test` to run your entire suite.

Simply run your standard testing commands from the root of your application:

```bash
php artisan test
```

or if using Pest:

```bash
./vendor/bin/pest
```

This natively discovers and seamlessly executes all tests located in `modules/*/tests/` alongside your root application tests. This is automatically configured under the hood during `php artisan modular:install` by safely injecting testsuite directories into your `phpunit.xml`.

> [!TIP]
> You can still use `php artisan modular:test` if you prefer the historical isolated test runner approach, but the native method above is recommended for modern CI pipelines.

### 2. Running Specific Module Tests

Use the Artisan command to isolate tests for one module.

```bash
# Run tests ONLY for Shop
php artisan modular:test Shop
```

This command:

1.  Points PHPUnit to `modules/Shop/phpunit.xml`.
2.  Ensures strictly that only this module's tests run.

---

## Writing Tests

Your module's `TestCase.php` usually extends `Tests\TestCase` (the application's base test case).

**Example Application Test:**

```php
// packages/modular/Shop/tests/Feature/CartTest.php

use Modules\Shop\Models\Product;

it('can add items to cart', function () {
    $product = Product::factory()->create();

    $response = this->post('/cart', [
        'product_id' => $product->id
    ]);

    $response->assertRedirect('/cart');
});
```

### 3. Model Factories (v1.1.5+)

Prior to v1.1.5, trying to use `Product::factory()->create()` on a modular model would crash, as Laravel expected the factory to live in the host application's `database/factories` directory.

Laravel Modular now overrides Laravel's internal `Factory::guessFactoryNamesUsing()` mapping behind the scenes.
You can use natively seamless factory resolution:

```php
use Modules\Shop\Models\Product;

// This correctly natively instances \Modules\Shop\Database\Factories\ProductFactory
$product = Product::factory()->count(5)->create();
```

Make sure your factories simply reside in `modules/Shop/database/factories/` and you never have to think about paths again!

### Mocking Other Modules

Since modules are isolated, how do you test interactions?

**Scenario:** Shop module needs to notify the User module.
**Approach:** The Shop module should depend on an Interface, not the User class. You can then mock that interface.

```php
// In ShopServiceProvider
$this->app->bind(UserRepositoryInterface::class, RealUserRepository::class);

// In Tests
$this->mock(UserRepositoryInterface::class, function ($mock) {
    $mock->shouldReceive('find')->andReturn(...);
});
```
