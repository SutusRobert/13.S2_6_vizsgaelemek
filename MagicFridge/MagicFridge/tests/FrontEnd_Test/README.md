# MagicFridge frontend Robot Framework tests

These tests cover the main browser flows only:

- login/register pages
- login and logout
- dashboard module navigation
- inventory add/search/update
- shopping list add/bought/delete
- recipe list and own recipe form

## Run

Start the Laravel app first:

```powershell
php artisan serve
```

Then run the Robot tests from the project root:

```powershell
robot -v BASE_URL:http://127.0.0.1:8000 -v TEST_EMAIL:your_verified_user@example.com -v TEST_PASSWORD:your_password tests/FrontEnd_Test
```

You can also use environment variables:

```powershell
$env:MAGICFRIDGE_TEST_EMAIL="your_verified_user@example.com"
$env:MAGICFRIDGE_TEST_PASSWORD="your_password"
robot tests/FrontEnd_Test
```

The public auth-page tests run without credentials. Tests that need protected pages are skipped unless a verified test user is provided.
