# CodeOps Module 4, Laravel Track: Days 51 to 57 Exercise Solutions

**Project:** `addis-eats-api` (the backend capstone)
**Stack:** PHP 8.3, Laravel 11, SQLite, Pest 3
**Status:** every in-class exercise and every homework or capstone slice for Days 52 to 57 is implemented in this repository and covered by an automated test suite (70 tests, 233 assertions, all passing).

---

## Contents

1. [How to use this document](#how-to-use-this-document)
2. [Day 51: Backend Fundamentals](#day-51-backend-fundamentals)
3. [Day 52: Track Setup and Language Fundamentals I](#day-52-track-setup-and-language-fundamentals-i)
4. [Day 53: Language Fundamentals II](#day-53-language-fundamentals-ii)
5. [Day 54: Framework Foundations](#day-54-framework-foundations)
6. [Day 55: Building the First API](#day-55-building-the-first-api)
7. [Day 56: Routing and Controllers](#day-56-routing-and-controllers)
8. [Day 57: REST API Design](#day-57-rest-api-design)
9. [How the work is graded](#how-the-work-is-graded)
10. [Testing with Postman](#testing-with-postman)
11. [Where the slides are wrong or outdated](#where-the-slides-are-wrong-or-outdated)
12. [Verification log](#verification-log)

---

## How to use this document

Each day follows the same structure:

- **What the day asks for:** the in-class exercise and the homework, quoted from the slide deck.
- **Solution:** what was built, where it lives, and the key code.
- **Review questions:** a full answer to every question on the "Review Questions" slide.
- **Challenge:** weaknesses in the obvious solution, or in the slide itself, and the better alternative.

Two honest limits:

1. Every day's homework also lists "five numbered exercises" from a reading sheet (`CodeOps_M4_D5x_..._Reading_Sheet_....docx`). Those Word files were not among the uploaded PDFs, so their exercises are not covered here. Everything that appears in the slides is.
2. Some deliverables are personal: pasting your own `php -v` output, screenshots, swapping tables with another pair, and pushing from your own GitHub account. The document gives you the content; you still have to perform those steps yourself.

Run everything yourself:

```bash
composer install && cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed
./vendor/bin/pest                       # 70 tests
php homework/d52_menu_report.php        # Day 52 mini-project
php homework/d53_order_engine.php       # Day 53 mini-project
php artisan serve                       # then use requests.http
```

---

## Day 51: Backend Fundamentals

Shared day, no code. The goal is vocabulary: server, request, response, status code, API.

### In-class exercise: trace and document a real API

**Task (pairs, 45 minutes):**
1. Trigger six requests in the Module 3 frontend and record method, path, status and whether there was a body.
2. For two of them, record three request headers and three response headers and explain each.
3. Find or force one failing request and argue whether its status code was right.
4. Write a six-row endpoint table for the Addis Eats API: method, path, purpose, success status.
5. Swap tables with another pair and challenge one row.
6. Push the table to GitHub.

**Step 1: six traced requests.** Your Module 3 app is not available to me, so these are traced from the running `addis-eats-api` with `curl -i`, which shows exactly what the browser network tab shows. Repeat the exercise in your own browser for the submission.

| # | Method | Full path | Status | Request body? |
|---|---|---|---|---|
| 1 | GET | /api/v1/dishes?area=Bole&sort=-price&per_page=2&page=2 | 200 OK | No |
| 2 | GET | /api/v1/restaurants/yod-abyssinia | 200 OK | No |
| 3 | POST | /api/v1/orders | 201 Created | Yes, JSON order |
| 4 | PATCH | /api/v1/orders/1/status | 409 Conflict | Yes, `{"status":"delivered"}` |
| 5 | DELETE | /api/v1/dishes/41 | 204 No Content | No |
| 6 | GET | /api/v1/dishes/9999 | 404 Not Found | No |

**Step 2: headers for two requests.**

Request 3, `POST /api/v1/orders`:

| Header | Direction | What it is doing |
|---|---|---|
| `Content-Type: application/json` | request | Tells the server how to parse the body bytes. Wrong value means the body cannot be read. |
| `Accept: application/json` | request | Asks for JSON back. Laravel also uses it to decide to render errors as JSON instead of an HTML page. |
| `Host: 127.0.0.1:8123` | request | Which site on the machine is being addressed. Set automatically. |
| `Location: http://127.0.0.1:8123/api/v1/orders/1` | response | Where the new order lives, so the client does not have to build the URL. |
| `Content-Type: application/json` | response | The body is JSON. |
| `Cache-Control: no-cache, private` | response | Caches must revalidate and shared caches (proxies, CDNs) must not store it. Correct for personal order data. |

Request 1, `GET /api/v1/dishes?...`:

| Header | Direction | What it is doing |
|---|---|---|
| `Accept: application/json` | request | Format wanted back. |
| `User-Agent: curl/8.x` | request | Which client software is asking. A browser sends its own. |
| `Accept-Encoding` (browser) | request | Whether the client can take gzip or br compressed bodies. |
| `Content-Type: application/json` | response | Body format. |
| `Access-Control-Allow-Origin: *` | response | CORS: lets a page on any origin read the response. Fine for public reads, must be tightened before auth (Day 63). |
| `X-Powered-By: PHP/8.3.6` | response | Advertises the PHP version. Harmless locally, but it is free reconnaissance for an attacker and should be turned off in production (`expose_php = Off`). |

**Step 3: one failing request, argued.** Request 4 sends `PATCH /orders/1/status` with `delivered` for an order that is still `placed`. The API answers:

```http
HTTP/1.1 409 Conflict
Content-Type: application/json

{"error":{"code":"illegal_status_transition","message":"An order that is placed cannot become delivered.","meta":{"from":"placed","to":"delivered"}}}
```

Was 409 right? The body is well formed and every field is valid, so 400 and 422 are wrong: nothing about the content would make it acceptable. The request is refused because of the current state of the resource, which is exactly what 409 Conflict means. A 500 would be a lie (the server did not break) and a 200 with an error message would be the "status and body disagree" defect from slide 19.

**Step 4: six-row endpoint table.**

| Method | Path | Purpose | Success |
|---|---|---|---|
| GET | /api/v1/restaurants?area=Bole&page=1 | List restaurants, filtered and paged | 200 OK |
| GET | /api/v1/restaurants/14/menu | One restaurant's menu | 200 OK |
| POST | /api/v1/orders | Place an order | 201 Created + Location |
| GET | /api/v1/orders/91 | Read one order | 200 OK |
| PATCH | /api/v1/orders/91/status | Change an order's status | 200 OK |
| DELETE | /api/v1/orders/91 | Cancel an order | 204 No Content |

**Step 5: the row worth challenging.** `DELETE /orders/91` for "cancel". Two objections:
- A cancelled order still exists. Customers, couriers and accountants will ask about it later. DELETE suggests it is gone, and a later `GET /orders/91` returning 404 would be wrong.
- Cancellation has rules (you cannot cancel a delivered order, which is the slide's own 409 example) and data (a reason).

The stronger design, adopted on Day 57, is `POST /orders/91/cancellation` with a reason, returning the updated order, and 409 when the order is no longer cancellable. That is the kind of 200 versus 201 versus 204 disagreement the slide says is "the point of the exercise".

**Step 6:** this table is committed as part of `API_DESIGN.md` and this document.

### Review questions

**1. What is a server, without the word "computer"?**
A server is a program that starts, claims a numbered door (a port), and then waits indefinitely. Whenever a request arrives on that door it works out an answer, sends it back, and goes back to waiting. It never speaks first, and "the server is down" usually just means that program stopped listening.

**2. Name the four parts of `POST /api/v1/orders` with a JSON body.**
- **Method** `POST`: the intent, create something new.
- **Path** `/api/v1/orders`: which resource (the orders collection, version 1).
- **Headers** such as `Content-Type: application/json`, `Authorization: Bearer ...`, `Accept: application/json`: metadata about format, identity and what is wanted back.
- **Body** `{"restaurantId":14,"items":[...]}`: the data describing the new order.

**3. Why is repeating `DELETE /orders/91` harmless but repeating `POST /orders` is not?**
After one DELETE the order is gone; after two it is still gone. The end state is the same (the second call returns 404, but nothing new happened). Each POST creates a new order, so a retry creates a second order and may charge Almaz twice. The property is **idempotence**. GET, PUT and DELETE are idempotent; POST is not, which is why clients disable the "Order" button after the first tap and why servers later add idempotency keys.

**4. An order with quantity zero: which code, and why not 500?**
A **4xx**, because the caller sent something wrong and must change it. In this module the answer is **422 Unprocessable Content**: the JSON is well formed but violates a business rule (quantity must be 1 or more). Slide 18 lists 400 for "body invalid", so 400 is defensible if the API reserves 422 for semantic errors only; pick one convention and use it everywhere. Not 500, because 5xx says "the server failed", which sends people hunting in the wrong codebase.

**5. 401 versus 403, with Addis Eats examples.**
401 means "I do not know who you are": the customer's token expired, or no `Authorization` header was sent. 403 means "I know who you are, and no": a logged-in courier tries `PATCH /restaurants/14/menu`, which only restaurant staff may do.

**6. 200 with a body saying "order failed".**
Clients, monitoring, proxies and retry logic all read the status first. A 200 tells every one of them the order succeeded, so the React app shows a confirmation, dashboards show no errors, and nothing retries. The status must match the outcome: 422 for invalid content, 409 for a state conflict, 503 if TeleBirr is unreachable, 500 if our code crashed, each with a JSON error body.

### Challenge

- Slide 12 marks PATCH as "Usually" safe to repeat. Strictly, PATCH is not required to be idempotent: `{"op":"increment","qty":1}` is a valid PATCH that is not. Only design PATCH bodies that set values, and the operation stays idempotent in practice.
- Slide 18 says 400 for "qty was "two"" and 422 for "a negative ETB amount". Laravel's validator returns 422 for both. Consistency matters more than the exact split, so this API uses 422 for all validation failures and keeps 400 for bodies that cannot be parsed at all.

---

## Day 52: Track Setup and Language Fundamentals I

### In-class exercise: Addis Eats pricing in PHP

| Deliverable | Where | Status |
|---|---|---|
| Verified toolchain pasted into README | `CHECKS.md`, README table | Done (paste your own output too) |
| Project `addis-eats-api` with `GET /api/health` returning status, service, time | `routes/api.php` | Done, tested |
| `app/Support/pricing.php` with `deliveryFee()` and `orderTotal()`, typed, `strict_types` | `app/Support/pricing.php` | Done, tested |
| `GET /api/quote` calling `orderTotal()` on a hard-coded three-line Bole order | `QuoteController` | Done, returns `{"subtotal":85000,"fee":0,"total":85000}` |
| Two extra areas in the match arm | CMC (7000) and Ayat (7500) | Done |
| README with track, PHP version, the two run commands | `README.md` | Done |

**Setup commands used:**

```bash
composer create-project laravel/laravel:^11.0 addis-eats-api   # same result as `laravel new`
cd addis-eats-api
php artisan install:api      # creates routes/api.php and the /api prefix
php artisan serve            # http://127.0.0.1:8000
```

**`app/Support/pricing.php`**

```php
<?php

declare(strict_types=1);

if (! defined('FREE_DELIVERY_FROM')) {
    define('FREE_DELIVERY_FROM', 50000); // ETB 500.00 in santim
}

if (! function_exists('deliveryFee')) {
    function deliveryFee(string $area, int $subtotal): int
    {
        // Trap 1: exactly 50000 ships free, so >= and not >.
        if ($subtotal >= FREE_DELIVERY_FROM) {
            return 0;
        }

        return match ($area) {
            'Bole', 'Kazanchis' => 4500,
            'Piassa', 'Megenagna' => 6000,
            'CMC' => 7000,
            'Ayat' => 7500,
            default => 8000,
        };
    }
}

if (! function_exists('orderTotal')) {
    /** @return array{subtotal: int, fee: int, total: int} */
    function orderTotal(array $lines, string $area): array
    {
        $subtotal = 0;
        foreach ($lines as $line) {
            $subtotal += $line['qty'] * $line['price'];
        }

        $fee = deliveryFee($area, $subtotal);

        return ['subtotal' => $subtotal, 'fee' => $fee, 'total' => $subtotal + $fee];
    }
}
```

**A detail the slide skips:** a file of plain functions is never loaded by PSR-4, which only autoloads classes. Without the next step, `/api/quote` fails with "Call to undefined function orderTotal()". Register the file in `composer.json` and run `composer dump-autoload`:

```json
"autoload": {
    "psr-4": { "App\\": "app/" },
    "files": ["app/Support/pricing.php"]
}
```

**Routes** (`routes/api.php`):

```php
Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'service' => 'addis-eats-api',
    'time' => now()->toIso8601String(),
]))->name('health');

Route::get('/areas', fn () => response()->json(array_keys(config('addiseats.zones'))));

Route::get('/quote', QuoteController::class); // calls orderTotal() on the slide's three lines
```

**The two traps, both tested** (`tests/Unit/D52PricingTest.php`):
- `deliveryFee('Bole', 50000)` is `0` and `deliveryFee('Bole', 49999)` is `4500`.
- `number_format()` is used only in output, never before arithmetic. All math stays in integer santim.

### Homework mini-project: the Addis Eats menu report

`homework/d52_menu_report.php` holds eight dishes from Yod Abyssinia, Kategna and Sishu, then prints every dish in ETB, the fasting count, the cheapest dish and the average price. It uses `array_filter`, `array_map`, `array_sum` and `usort`, converts `is_fasting` with `match`, types every function and starts with `declare(strict_types=1)`.

```php
function fastingLabel(bool $isFasting): string
{
    return match ($isFasting) {
        true => 'Fasting',
        false => 'Non-fasting',
    };
}

function menuReport(array $menu): string
{
    if ($menu === []) {
        return "The menu is empty.\n";          // no division by zero, no null "cheapest"
    }

    $sorted = $menu;                             // usort works in place and returns bool
    usort($sorted, fn (array $a, array $b): int => [$a['price'], $a['name']] <=> [$b['price'], $b['name']]);

    $rows = array_map(fn (array $d): string => sprintf('%-15s %-14s %12s  %s',
        $d['name'], $d['restaurant'], formatEtb($d['price']), fastingLabel($d['is_fasting'])), $sorted);

    $fasting  = array_filter($menu, fn (array $d): bool => $d['is_fasting'] === true);
    $prices   = array_column($menu, 'price');
    $average  = intdiv(array_sum($prices) * 2 + count($prices), count($prices) * 2); // int, half up
    $cheapest = $sorted[0];
    // ... implode the report
}
```

Actual output:

```text
ADDIS EATS MENU REPORT
------------------------------------------------------------
Misir Wat       Kategna          ETB 165.00  Fasting
Shiro           Kategna          ETB 180.00  Fasting
Tibs            Kategna          ETB 220.00  Non-fasting
Beyaynetu       Yod Abyssinia    ETB 245.00  Fasting
Fasting Burger  Sishu            ETB 300.00  Fasting
Burger          Sishu            ETB 350.00  Non-fasting
Kitfo           Yod Abyssinia    ETB 380.00  Non-fasting
Doro Wat        Yod Abyssinia    ETB 420.00  Non-fasting
------------------------------------------------------------
Fasting dishes : 4
Cheapest dish  : Misir Wat (Kategna), ETB 165.00
Average price  : ETB 282.50
```

Check: the eight prices sum to 226000 santim, divided by 8 is 28250, which is ETB 282.50.

Edge cases handled: an empty menu (no division by zero), two dishes with the same price (the sort falls back to name so the "cheapest" is deterministic), and the average stays an integer in santim.

### Review questions

**Q1. URL and behaviour of an `api.php` route.** A route `/health` in `routes/api.php` is served at `http://127.0.0.1:8000/api/health`. It gets the `/api` prefix and the `api` middleware group, which makes it **stateless**: no session and no CSRF token check. `web.php` routes have sessions, cookies and CSRF protection.

**Q2. What `declare(strict_types=1)` changes about `deliveryFee('7')`.** Without it PHP silently coerces `'7'` to `7` (and truncates `7.9` to `7`). With it the call throws a `TypeError`. It must be the very first statement in the file, directly after `<?php`. It applies to calls **made from** that file, so it belongs in every file.

**Q3. Why `120.10 + 120.10` is unsafe for ETB.** Binary floating point cannot represent most decimals exactly, so `120.10 + 120.10 === 240.20` is false and errors accumulate across thousands of orders. The module stores every amount as an **integer number of santim** (`12010 + 12010 = 24020`, exact) and formats only for display.

**Q4. `array_filter` returns `[1 => 6000]`.** The keys are no longer `0, 1, 2...`, so `json_encode` produces an object `{"1":6000}` instead of a list `[6000]`, and a frontend calling `.map()` on it breaks. Fix: wrap the result in `array_values()`.

**Q5. Two behaviours of `match` that `switch` lacks, and its error.** `match` is an expression that returns a value, and it compares strictly with `===`. It also has no fall-through, so no `break` is needed. When no arm matches and there is no `default`, it throws `UnhandledMatchError`.

**Q6. "There is an estimate and it is above zero".**

```php
if ($minutes !== null && $minutes > 0) { /* ready in $minutes */ }
```

### Challenge

- **Float for display is fine, float for an API field is not.** `number_format($santim / 100, 2)` returns `"1,200.00"` for 120000 santim. A thousands separator in a JSON field breaks every client that parses it. The API therefore uses `App\Support\Money::decimal()`, which uses integer arithmetic and no separator (`"1200.00"`).
- **`default => 8000` in `deliveryFee()` silently delivers to anywhere.** "Sululta" gets a fee instead of a refusal. Day 53 fixes this with `OutsideZoneException`; the Day 52 function keeps the slide's behaviour so the exercise matches the spec.
- **`laravel new` versus `composer create-project`:** identical output. The installer is a convenience, not a requirement.

---

## Day 53: Language Fundamentals II

### In-class exercise: modelling the Addis Eats domain

| Deliverable | File | Notes |
|---|---|---|
| Promoted, readonly `Dish` (name, restaurant, price, isFasting) | `app/Domain/Dish.php` | Also rejects a negative price |
| Backed enum `OrderStatus`, four cases, `label()` with `match` | `app/Domain/OrderStatus.php` | A fifth case, `Cancelled`, is added on Day 57 |
| Trait `FormatsEtb::toEtb(int): string`, used by two classes | `app/Support/FormatsEtb.php` | Used by `Dish`, `Order`, `Receipt`, `MenuService` |
| Interface `PricingRule` with `StandardRule` and `PromoRule` | `app/Domain/Pricing/` | Signature adjusted, see Challenge |
| `OutsideZoneException` with `public readonly $area` | `app/Exceptions/OutsideZoneException.php` | Renders itself as a 422 |
| `MenuService` with one collection chain | `app/Domain/MenuService.php` | Moved onto Eloquent on Day 54 |
| `GET /api/menu?area=Bole&budget=25000`, unknown area gives 422 | `GET /api/v1/menu` | Tested |

**Dish with constructor property promotion:**

```php
final class Dish
{
    use FormatsEtb;

    public function __construct(
        public readonly string $name,
        public readonly string $restaurant,
        public readonly int $price,       // santim
        public readonly bool $isFasting,
    ) {
        if ($price < 0) {
            throw new \InvalidArgumentException('A price cannot be negative.');
        }
    }

    public function priceInEtb(): string
    {
        return $this->toEtb($this->price);
    }
}
```

**OrderStatus enum:**

```php
enum OrderStatus: string
{
    case Placed = 'placed';
    case Preparing = 'preparing';
    case OnTheWay = 'on_the_way';
    case Delivered = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::Placed => 'Placed',
            self::Preparing => 'Being prepared',
            self::OnTheWay => 'On the way',
            self::Delivered => 'Delivered',
        };
    }
}
```

**The trait:**

```php
namespace App\Support;

trait FormatsEtb
{
    public function toEtb(int $santim): string
    {
        return number_format($santim / 100, 2).' ETB';
    }
}
```

**PricingRule and its implementations.** A small `DeliveryZones` value object holds the zone list and base fees in one place so the rules do not each repeat it:

```php
interface PricingRule
{
    /** @throws OutsideZoneException */
    public function feeFor(string $area, int $subtotal): int;
}

final class StandardRule implements PricingRule
{
    public function __construct(private readonly DeliveryZones $zones) {}

    public function feeFor(string $area, int $subtotal): int
    {
        $base = $this->zones->baseFee($area);          // throws for an unserved area
        return $subtotal >= $this->zones->freeDeliveryFrom ? 0 : $base;
    }
}

/** 10% off the standard fee. A decorator, so the threshold logic is not duplicated. */
final class PromoRule implements PricingRule
{
    public function __construct(private readonly StandardRule $standard, private readonly int $percentOff = 10) {}

    public function feeFor(string $area, int $subtotal): int
    {
        $fee = $this->standard->feeFor($area, $subtotal);
        return intdiv($fee * (100 - $this->percentOff) + 50, 100); // integer, half up
    }
}
```

**Custom exception carrying data:**

```php
class OutsideZoneException extends \DomainException
{
    public function __construct(public readonly string $area)
    {
        parent::__construct("Addis Eats does not deliver to {$area} yet.");
    }

    public function render(): JsonResponse   // Laravel calls this automatically
    {
        return ApiResponse::error('outside_delivery_zone', $this->getMessage(), 422, ['area' => $this->area]);
    }
}
```

**MenuService, as the Day 53 slide specifies it** (a collection passed into the constructor):

```php
final class MenuService
{
    use FormatsEtb;
    private const ZONES = ['Bole', 'Kazanchis', 'Piassa', 'Megenagna'];

    public function __construct(private readonly Collection $dishes) {}

    public function affordableIn(string $area, int $budget): Collection
    {
        if (! in_array($area, self::ZONES, true)) {
            throw new OutsideZoneException($area);
        }

        return $this->dishes
            ->filter(fn (Dish $d) => $d->price <= $budget)
            ->sortBy(fn (Dish $d) => $d->price)
            ->map(fn (Dish $d) => ['name' => $d->name, 'price' => $this->toEtb($d->price)])
            ->values();                       // trap 2: close the chain with values()
    }
}
```

The repository holds the Day 54 version of this class, which reads from the database. The reason is in the Challenge below.

**Proof from the route:**

```text
GET /api/v1/menu?area=Bole&budget=25000  -> 200 {"data":[{"name":"Ful","price":"139.00 ETB",...}, ...],"meta":{"count":4,...}}
GET /api/v1/menu?area=Sululta            -> 422 {"error":{"code":"outside_delivery_zone","message":"Addis Eats does not deliver to Sululta yet.","meta":{"area":"Sululta"}}}
```

### Homework mini-project: the Addis Eats order engine

| Requirement | Implementation |
|---|---|
| `Order`, `OrderLine`, `Dish` as promoted classes | `app/Domain/Order.php`, `OrderLine.php`, `Dish.php` |
| `OrderStatus` enum and `FormatsEtb` shared by `Order` and `Receipt` | Both `use FormatsEtb` |
| `PricingRule` with `StandardRule`, `PromoRule`, `FreeDeliveryRule`; `Order` accepts any | `Order::__construct(..., PricingRule $rule)` |
| Throw `OutsideZoneException` and `EmptyOrderException`; catch both in one script | `homework/d53_order_engine.php` uses `catch (OutsideZoneException\|EmptyOrderException $e)` |
| Receipt built with a collection chain | `Receipt::render()` maps `$order->lines()` |

```php
final class Order
{
    use FormatsEtb;

    /** @var list<OrderLine> */
    private array $lines = [];

    public function __construct(
        public readonly int $id,
        public readonly string $area,
        private readonly PricingRule $rule,
    ) {}

    public function addLine(Dish $dish, int $qty): static
    {
        $this->lines[] = new OrderLine($dish, $qty);   // OrderLine rejects qty < 1
        return $this;                                   // fluent
    }

    public function subtotal(): int
    {
        return collect($this->lines)->sum(fn (OrderLine $l) => $l->amount());
    }

    public function fee(): int
    {
        if ($this->lines === []) {
            throw new EmptyOrderException;
        }
        return $this->rule->feeFor($this->area, $this->subtotal());
    }
}
```

Actual output of `php homework/d53_order_engine.php` (the submission asks for one receipt inside the zone and one outside):

```text
=== Inside the zone, standard rule ===
Receipt for order #24550 (Piassa)
  Shiro        x1      180.00 ETB
  Tibs         x1      220.00 ETB
  Subtotal             400.00 ETB
  Delivery fee          60.00 ETB
  Total                460.00 ETB
  Status: Placed

=== Inside the zone, promo rule ===
Receipt for order #24551 (Bole)
  Shiro        x2      360.00 ETB
  Subtotal             360.00 ETB
  Delivery fee          40.50 ETB
  Total                400.50 ETB
  Status: Placed

=== Free delivery threshold reached ===
Receipt for order #24552 (Bole)
  Doro Wat     x1      420.00 ETB
  Tibs         x1      220.00 ETB
  Subtotal             640.00 ETB
  Delivery fee           0.00 ETB
  Total                640.00 ETB
  Status: Placed

=== Outside the zone ===
Could not price the order: Addis Eats does not deliver to Sululta yet. [area=Sululta]

=== Empty order ===
Could not price the order: An order needs at least one line.
```

### Review questions

**Q1. Rewrite a Dish (name, price, rating) with promotion; which properties are readonly?**

```php
final class Dish
{
    public function __construct(
        public readonly string $name,
        public readonly int $price,     // santim
        public float $rating = 0.0,
    ) {}
}
```

`name` and `price` describe facts about this dish and should never change on an existing object (a new price is a new value, recorded through an update). `rating` changes over the object's life as reviews arrive, so it stays mutable. Rating is a `float` because it is a genuinely fractional measurement, unlike money.

**Q2. What does `fn ($d) => $d['price']` capture, and what does the `function` form need?**
An arrow function automatically captures, by value, any outer variable it uses (that one uses none). The long form sees no outer variables at all, so to read `$budget` it needs `function ($d) use ($budget) { return $d['price'] <= $budget; }`.

**Q3. Interface versus trait.**
An interface can be used as a **type**: `function pay(PaymentGateway $g)` accepts any implementation, and the container can bind it. A trait cannot be type-hinted. A trait can carry **method bodies and properties** that are copied into the class; an interface only declares signatures.

**Q4. `app/Domain/Pricing/ZoneRule.php` cannot be found. Three causes.**
1. The namespace does not match the folder: it must be `namespace App\Domain\Pricing;`.
2. The file name does not match the class name exactly, including case (`zoneRule.php` works on macOS and fails on Linux).
3. A new top-level folder or a classmap change without `composer dump-autoload` (or a stale optimised autoloader).

**Q5. Why `catch (\Throwable $e)` at the top of a controller hides bugs.**
It also catches `TypeError`, `Error`, database failures and every programming mistake, then usually turns them into a polite response. The bug never reaches the log or the exception handler, and the client gets a misleading status. Catch only the narrow type you can act on, such as `OutsideZoneException`, and let everything else propagate to Laravel's handler, which logs it and returns a 500.

**Q6. `filter(...)->pluck('name')` "returns keys 0, 3 and 7".**
The idea behind the question: `filter()` keeps the original keys, so after removing items the keys have gaps, and a collection with non-sequential keys serialises to a JSON **object**. The fix is `->values()`.
Nuance verified in Laravel 11: `pluck()` itself builds a fresh array and happens to renumber, so `filter()->pluck()` does give `0, 1, 2`. `filter()->map()` keeps the gaps (`{"1":"B","2":"C"}`). Both cases are in `tests/Unit/D53DomainTest.php`. Keep the habit of ending with `values()`; do not rely on which method happens to reindex.

### Challenge

- **`feeFor(string $area): int` is the wrong interface.** A free-delivery threshold depends on the subtotal, and the homework requires a `FreeDeliveryRule`. With the slide signature the threshold has to live outside the rule, which defeats the purpose of the interface. The implemented signature is `feeFor(string $area, int $subtotal): int`.
- **A `Collection` constructor parameter is a trap for the container.** On Day 54 Laravel builds `MenuService` automatically. Asked for a `Collection`, the container happily injects an **empty** one, so the menu silently returns nothing. That is why the repository version reads from Eloquent and only injects `DeliveryZones`.
- **The slides disagree on prices.** Day 52 charges Piassa 6000, Day 53 charges 5500. Zones and fees now live once, in `config/addiseats.php`.
- **`PromoRule` as a decorator** (wrapping `StandardRule`) is better than copying the threshold logic into a second class. One rule change, one file.

---

## Day 54: Framework Foundations

### In-class exercise: Addis Eats on the framework

| Deliverable | Where |
|---|---|
| dishes migration: name, restaurant, price (santim), is_fasting, area, timestamps | `database/migrations/2026_08_21_000001_create_dishes_table.php` |
| `App\Models\Dish` with `$fillable` and casts, `DishFactory`, seeder with 40 dishes | `app/Models/Dish.php`, `database/factories/DishFactory.php`, `DatabaseSeeder.php` |
| v1 group with `apiResource('dishes')` plus `GET /menu` | `routes/api_v1.php` (split out on Day 57) |
| `MenuController` taking `MenuService` through its constructor, no `new` | `app/Http/Controllers/Api/V1/MenuController.php` |
| `config/addiseats.php` with zones and threshold, read with `config()` only | `config/addiseats.php` |
| `PricingRule` bound to `StandardRule`, proven by swapping to `PromoRule` | `AppServiceProvider::register()` |

**Migration:**

```php
Schema::create('dishes', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('restaurant');
    $table->unsignedInteger('price');                 // santim
    $table->boolean('is_fasting')->default(false);
    $table->string('area')->index();
    $table->timestamps();
});
```

**Model:**

```php
class Dish extends Model
{
    use HasFactory, SoftDeletes;   // SoftDeletes arrives with Day 55's discussion

    // Final form after Day 56: the restaurant is a relationship, not a text column.
    protected $fillable = ['name', 'price', 'is_fasting', 'area', 'restaurant_id'];

    protected function casts(): array
    {
        return ['is_fasting' => 'boolean', 'price' => 'integer', 'cost_price' => 'integer'];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function orderLines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }
}
```

**Config, never `env()` outside it:**

```php
// config/addiseats.php
return [
    'free_delivery_from' => (int) env('ADDIS_EATS_FREE_DELIVERY_FROM', 50000),
    'zones' => ['Bole' => 4500, 'Kazanchis' => 4500, 'Piassa' => 6000,
                'Megenagna' => 6000, 'CMC' => 7000, 'Ayat' => 7500],
    'pricing_rule' => env('ADDIS_EATS_PRICING_RULE', 'standard'),
    'pagination' => ['default_per_page' => 15, 'max_per_page' => 100],
];
```

**Container bindings:**

```php
public function register(): void
{
    $this->app->singleton(DeliveryZones::class, fn () => new DeliveryZones(
        config('addiseats.zones'),
        (int) config('addiseats.free_delivery_from'),
    ));

    // The only line in the application that names a concrete pricing rule.
    $this->app->bind(PricingRule::class, fn ($app) => match (config('addiseats.pricing_rule')) {
        'promo' => $app->make(PromoRule::class),
        'free' => $app->make(FreeDeliveryRule::class),
        default => $app->make(StandardRule::class),
    });
}
```

Swapping to `PromoRule` is now an `.env` change (`ADDIS_EATS_PRICING_RULE=promo`) instead of a code edit. A test proves both bindings.

**Thin controller:**

```php
class MenuController extends Controller
{
    public function __construct(private readonly MenuService $menu) {}

    public function __invoke(Request $request): JsonResponse
    {
        $area = $request->string('area', 'Bole')->trim()->toString();
        $budget = max(0, $request->integer('budget', 100000));

        $dishes = $this->menu->affordableIn($area, $budget);   // OutsideZoneException renders 422 itself

        return ApiResponse::ok($dishes, ['count' => $dishes->count(), 'area' => $area]);
    }
}
```

The slide catches `OutsideZoneException` inside the controller. Here the exception has a `render()` method, so every controller that triggers it gets the same 422 without a `try/catch`. That is the Day 58 pattern arriving early, and it keeps the controller to three lines.

**The three proofs (all tested):**

| Request | Expected | Result |
|---|---|---|
| `GET /api/v1/menu?area=Bole&budget=25000` | seeded dishes | 200 |
| `GET /api/v1/menu?area=Sululta` | 422 | 422 `outside_delivery_zone` |
| `GET /api/v1/dishes/9999` | 404 | 404 `not_found` |

### Homework: the Addis Eats API, first cut

| Requirement | Implementation |
|---|---|
| orders table and `Order` model with status cast to `OrderStatus` | `2026_08_24_000001_create_orders_table.php` (orders plus order_lines), `App\Models\Order` casts `status` to the enum |
| `OrderController` with `store()` and `show()`, 201 with the resource | Implemented; `store()` returns 201 plus `Location` |
| Pricing through the `PricingRule` binding, controller never names a rule | `OrderService` receives `PricingRule` by injection |
| 422 for an area outside the zones using yesterday's exception | Thrown by the rule, rendered by the exception |

```php
final class OrderService
{
    public function __construct(private readonly PricingRule $rule) {}

    public function place(string $customerName, ?string $phone, string $area, Collection $lines): Order
    {
        if ($lines->isEmpty()) {
            throw new EmptyOrderException;
        }

        // Merge duplicate lines: [{3,1},{3,2}] becomes one line of qty 3.
        $qtyByDish = $lines->groupBy('dish_id')->map(fn (Collection $g) => (int) $g->sum('qty'));

        $dishes = Dish::query()->whereIn('id', $qtyByDish->keys())->get()->keyBy('id');
        $missing = $qtyByDish->keys()->diff($dishes->keys())->values();
        if ($missing->isNotEmpty()) {
            throw new UnknownDishException($missing->all());
        }

        $subtotal = $qtyByDish->reduce(fn (int $sum, int $qty, int $id) => $sum + $dishes[$id]->price * $qty, 0);
        $fee = $this->rule->feeFor($area, $subtotal);       // 422 outside the zone

        return DB::transaction(function () use (/* ... */) {
            // create the order with forceFill() for server-computed fields,
            // then one order_line per dish, snapshotting name and unit price
        });
    }
}
```

Design decisions worth defending:
- **Prices come from the database, never from the request.** A client that sends `"price": 1` cannot change what it pays.
- **Order lines snapshot `dish_name` and `unit_price`.** If a dish is renamed or repriced next week, last week's receipts still show what was actually sold.
- **Everything is written in one transaction.** A failure halfway leaves no order without lines.
- **`status`, totals and payment fields are not in `$fillable`.** Only the service sets them, with `forceFill()`.

### Review questions

**Q1. The six stages, and who owns 401 and 404.**
`public/index.php`, then bootstrap (`bootstrap/app.php` builds the container and loads providers), then the middleware stack, then route matching, then the controller and its services, then the response travelling back out through middleware. A 401 comes from **middleware** (authentication). A 404 comes from **route matching** (no route) or from **route model binding** just after it (no row).

**Q2. Why `{dish}` plus `show(Dish $dish)` gives a free 404, and what breaks with `{id}`.**
Laravel sees a route parameter named `dish` and a type-hinted `Dish $dish` with the same name, so it calls `Dish::where('id', $value)->firstOrFail()`. `firstOrFail()` throws `ModelNotFoundException`, which the handler turns into a 404 before `show()` runs. If the route says `{id}` the names no longer match: implicit binding is skipped and the container tries to build an empty `new Dish()` instead, so `show()` receives a blank model (null id) rather than a 404.

**Q3. How the container knows what to pass to `MenuController`.**
Reflection on the constructor's **type hints**. It sees `MenuService`, builds it, sees that `MenuService` needs `DeliveryZones`, finds the singleton binding, and so on down the tree. It breaks when a parameter is an **interface with no binding**, a **scalar** (`int $timeout`) with no default and no contextual binding, or a parameter with **no type** at all.

**Q4. The `Log` facade versus an injected logger.**
Advantage: short and available anywhere, with no constructor change, and still testable with `Log::spy()` or `Log::shouldReceive()`. Cost: the dependency is hidden. A class using six facades has six collaborators its constructor never mentions, which makes it harder to read, harder to reuse outside Laravel, and easier to grow too large.

**Q5. `env('TELEBIRR_KEY')` works locally, null in production.**
Production ran `php artisan config:cache`. Once the configuration is cached, the `.env` file is no longer loaded, so `env()` outside a config file returns `null`. Fix: in `config/services.php` add `'telebirr' => ['key' => env('TELEBIRR_KEY')]`, read it with `config('services.telebirr.key')`, and re-run `config:cache`. This was reproduced on this project: after `config:cache`, `env('ADDIS_EATS_PRICING_RULE')` returned `NULL` while `config('addiseats.pricing_rule')` still returned `"standard"`.

**Q6. What `$fillable` protects against, and why `Dish::all()->where(...)` is wrong at scale.**
`$fillable` blocks **mass assignment**: without it `Dish::create($request->all())` would write any column the client invents, such as `cost_price`, `id` or `is_admin`. `Dish::all()->where(...)` loads every one of the forty thousand rows into PHP memory as models and then filters in PHP. `Dish::where(...)->get()` puts the condition in SQL and the database (with an index on `area`) returns only the matching rows.

### Challenge

- **Use `config()` not `env()` is enforced by a test** that scans `app/` for `env(`. Documentation that is not enforced drifts.
- **Laravel 11 is no longer the current release.** `composer audit` on this project reports open advisories against `laravel/framework` 11.x that are fixed only in newer majors. The course standardises on 11, so the code follows it, but a real deployment should move to the current major. Almost nothing in this repository would change.

---

## Day 55: Building the First API

### In-class exercise: ship the dishes resource

| Deliverable | Status |
|---|---|
| `apiResource('dishes')` in the v1 group, five methods, `route:list` shows five routes | Done |
| index with optional `area`, `max_price`, `fasting`, composed with `when()`, applied in SQL | Done (now via scopes, Day 56) |
| store returns 201 plus `Location`; update returns the resource; destroy returns 204 | Done |
| `DishResource` publishing only id, name, restaurant, price_etb, price_santim, fasting, area | Done; a test asserts the exact key list |
| `requests.http`, one request per endpoint, including a 404 and a filtered list | `requests.http` (24 requests) |
| README with track, install steps, endpoint table | `README.md` |

**Controller (final form):**

```php
public function index(Request $request): JsonResponse
{
    $area = $request->filled('area') ? $request->string('area')->toString() : null;
    if ($area !== null && ! $this->zones->serves($area)) {
        throw new OutsideZoneException($area);
    }

    [$column, $direction] = $this->sort($request, self::SORTABLE, 'price');

    $dishes = Dish::query()
        ->inArea($area)
        ->under($request->filled('max_price') ? $request->integer('max_price') : null)
        ->fasting($request->boolean('fasting'))
        ->search($request->filled('q') ? $request->string('q')->trim()->toString() : null)
        ->orderBy($column, $direction)
        ->orderBy('id')
        ->paginate($this->perPage($request))
        ->withQueryString();

    return ApiResponse::paginated(DishResource::collection($dishes));
}

public function show(Dish $dish): JsonResponse
{
    return ApiResponse::ok(new DishResource($dish));
}

public function store(Request $request): JsonResponse
{
    $dish = Dish::create($request->validate($this->rules(partial: false)));

    return ApiResponse::created(new DishResource($dish), route('v1.dishes.show', $dish));
}

public function update(Request $request, Dish $dish): JsonResponse   // PUT and PATCH
{
    $dish->update($request->validate($this->rules(partial: $request->isMethod('patch'))));

    return ApiResponse::ok(new DishResource($dish->refresh()));
}

public function destroy(Dish $dish): Response
{
    $dish->delete();                          // soft delete
    return ApiResponse::noContent();          // 204, empty body
}
```

**Resource:**

```php
class DishResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'restaurant' => $this->restaurant?->name,      // relationship, eager-loaded by the controller
            'price_etb' => Money::decimal($this->price),   // "1200.00", exact, no comma
            'price_santim' => $this->price,
            'fasting' => $this->is_fasting,
            'area' => $this->area,
        ];
    }
}
```

**The three traps, each covered by a test:**
- `cost_price` is in the seed data on purpose and never appears in a response.
- A column missing from `$fillable` is silently ignored: posting `cost_price` and `id` leaves `cost_price` null and the id auto-generated.
- `noContent()` returns 204 with an empty body; the test asserts the body is `''`.

**Validation note.** The slides postpone validation to Day 58 and use `$request->only([...])`. That lets `"price": "abc"` or a missing `name` reach the database and come back as a 500, which the Day 75 rubric marks down ("no 500 for bad input"). Minimal `$request->validate()` rules are added now; Day 58 moves them into Form Request classes unchanged.

### Homework (capstone slice): restaurants

| Requirement | Implementation |
|---|---|
| restaurants table, model, factory, resource | Migration, `Restaurant` model with slug generation, `RestaurantFactory`, `RestaurantResource` |
| `GET /api/v1/restaurants` and `GET /api/v1/restaurants/{restaurant}` bound on slug | `getRouteKeyName()` returns `slug`; full CRUD added on Day 56 |
| `requests.http` and README cover every endpoint | Done |
| Runs from a fresh clone: install, copy env, migrate with seed, serve | Verified, see the Verification log |

```php
class Restaurant extends Model
{
    protected $fillable = ['name', 'slug', 'area', 'phone', 'opening_hours'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::saving(function (Restaurant $r) {
            if (blank($r->slug)) {
                $r->slug = Str::slug($r->name);     // "Yod Abyssinia" -> "yod-abyssinia"
            }
        });
    }

    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class);
    }
}
```

Migrations are forward only: `cost_price`, `restaurant_id` and `deleted_at` were added with **new** migrations instead of editing the Day 54 one.

### Review questions

**Q1. Rewrite as REST.**

| Before | After |
|---|---|
| `GET /getDishesByArea/Bole` | `GET /dishes?area=Bole` |
| `POST /dishes/42/delete` | `DELETE /dishes/42` |
| `GET /dishes/create` | `POST /dishes` (an API needs no "create form" page) |

**Q2. Status codes owed.** Successful create: **201** with `Location`. Successful delete: **204**, no body. An unserved delivery area: **422**. A `DELETE` sent to `/dishes`: **405 Method Not Allowed**, with an `Allow: GET, HEAD, POST` header (verified).

**Q3. What `Location` on a 201 tells the client.** The URL of the newly created resource. Without it the client has to know the URL pattern and build `/api/v1/dishes/{id}` itself from the body, which couples it to the URL structure, or make an extra request to find the resource.

**Q4. Two things that break when returning the model directly.**
1. **Leaks**: every column is published, including internal ones like `cost_price` and `supplier_note`.
2. **Instability**: renaming a column in a migration silently renames a field in the public contract and breaks every client.
(A third: raw values such as santim integers and database timestamps go out unformatted.)

**Q5. Why `when()` on the query builder and not on the collection.** On the builder each condition becomes part of one SQL `WHERE`, so the database returns only matching rows using its indexes. On the collection the whole table would first be loaded into memory and then filtered in PHP. It works with forty rows and fails with four hundred thousand.

**Q6. `PATCH /dishes/3` with `{"price": 19500, "cost_price": 100}`.** Only `price` changes to 19500. Two mechanisms decided it: the controller only accepts the listed keys (`only([...])` in the slides, `validate()` here, which returns only validated keys) and the model's `$fillable` does not contain `cost_price`. Verified by a test: `cost_price` stays at 9000.

### Challenge

- **Keep the data wrapper on single resources too.** `return new DishResource($dish)` is wrapped in `data`; a raw `return $dish` is not. Mixing both forces clients into conditionals, so everything goes through `ApiResponse`.
- **The Day 54 `restaurant` text column became redundant** once Day 56 added `restaurant_id`, and it blocked the natural relationship name `$dish->restaurant`. A forward migration (`2026_08_25_000001_replace_restaurant_name_with_relationship_on_dishes.php`) links every existing dish to a restaurant row, creating one if a name has no row, then drops the text column. Its `down()` restores the column and refills it. Both directions were run against legacy rows. The public contract did not change: `"restaurant": "Kategna"` is now read through the relationship. Creating a dish now takes `restaurant_id` instead of a free-text name, so a typo can no longer invent a restaurant.

---

## Day 56: Routing and Controllers

### In-class exercise: restaurants and conventions

| Deliverable | Implementation |
|---|---|
| Controllers in `App\Http\Controllers\Api\V1`, `route:list` resolves everything | Done |
| `apiResource('restaurants')` bound on slug plus `restaurants.dishes` nested | Done (see Challenge on `shallow()`) |
| Constraints on every parameter | Global `Route::pattern()` in `AppServiceProvider::boot()` |
| Scopes `inArea`, `under`, `fasting` on `Dish`; controller reduced to a chain | Done, plus `search` |
| `SORTABLE` whitelist, `?sort=price`, `?sort=-price`, `?q=` search | Shared `ListsResources` trait |
| `ApiResponse` with `ok`, `created`, `error`, used by every method | Done, with two bug fixes |

**Routes:**

```php
// routes/api_v1.php (loaded inside prefix('v1')->name('v1.'))
Route::get('/menu', MenuController::class)->name('menu');
Route::apiResource('dishes', DishController::class);
Route::apiResource('restaurants', RestaurantController::class);
Route::apiResource('restaurants.dishes', RestaurantDishController::class)->only(['index', 'store']);
Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
Route::patch('/orders/{order}/status', OrderStatusController::class)->name('orders.status');
Route::post('/orders/{order}/cancellation', OrderCancellationController::class)->name('orders.cancellation');
Route::put('/orders/{order}/payment', OrderPaymentController::class)->name('orders.payment');
```

**Constraints:**

```php
public function boot(): void
{
    Route::pattern('dish', '[0-9]+');
    Route::pattern('order', '[0-9]+');
    Route::pattern('restaurant', '[a-z0-9]+(?:-[a-z0-9]+)*');   // lower-case slug with hyphens
}
```

**Scopes:**

```php
public function scopeInArea(Builder $query, ?string $area): Builder
{
    return $query->when($area, fn (Builder $q) => $q->where('area', $area));
}

public function scopeUnder(Builder $query, ?int $santim): Builder
{
    // !== null, not truthiness: max_price=0 must return nothing, not everything.
    return $query->when($santim !== null, fn (Builder $q) => $q->where('price', '<=', $santim));
}

public function scopeFasting(Builder $query, bool $only): Builder
{
    return $query->when($only, fn (Builder $q) => $q->where('is_fasting', true));
}

public function scopeSearch(Builder $query, ?string $term): Builder
{
    // Escape LIKE wildcards so "%" in a search means a literal percent sign.
    return $query->when($term, function (Builder $q) use ($term) {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term);
        $q->whereRaw("lower(name) like ? escape '\\'", ['%'.mb_strtolower($escaped).'%']);
    });
}
```

**Sort whitelist, shared by every list controller:**

```php
protected function sort(Request $request, array $sortable, string $default): array
{
    $sort = $request->string('sort', $default)->trim()->toString();
    $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
    $column = ltrim($sort, '-');

    if (! in_array($column, $sortable, true)) {      // a client value never reaches SQL unchecked
        return [ltrim($default, '-'), str_starts_with($default, '-') ? 'desc' : 'asc'];
    }

    return [$column, $direction];
}
```

**ApiResponse (corrected version):**

```php
final class ApiResponse
{
    public static function ok(mixed $data, array $meta = [], int $status = 200): JsonResponse
    {
        $body = ['data' => $data];
        if ($meta !== []) {
            $body['meta'] = $meta;
        }
        return response()->json($body, $status);
    }

    public static function created(mixed $data, string $location): JsonResponse
    {
        return response()->json(['data' => $data], 201)->header('Location', $location);
    }

    public static function error(string $code, string $message, int $status, array $meta = []): JsonResponse
    {
        return response()->json(['error' => [
            'code' => $code,
            'message' => $message,
            'meta' => (object) $meta,     // {} when empty, never []
        ]], $status);
    }
}
```

Two bugs in the slide's version are fixed here:
1. `array_filter(['data' => $data, 'meta' => ...])` removes **any falsy value, including `data` when it is an empty list**. An empty search would return `{}` instead of `{"data": []}`. A test covers this.
2. `compact('code', 'msg', 'meta')` produces a key called `msg`, while the envelope slide promises `message`. Clients written against the documented shape would read `undefined`.

**Error envelope applied to framework errors too** (`bootstrap/app.php`), so 404, 405 and validation failures share the same shape as domain errors:

```json
{"error":{"code":"not_found","message":"No dish matches that identifier.","meta":{"ids":["9999"]}}}
{"error":{"code":"method_not_allowed","message":"DELETE is not allowed on this path.","meta":{"allow":"GET, HEAD, POST"}}}
{"error":{"code":"validation_failed","message":"The request was invalid.","meta":{"fields":{"price":["..."]}}}}
```

**The four proofs:**

| Request | Result |
|---|---|
| `/restaurants/yod-abyssinia` | 200, `dish_count` included |
| `/restaurants/yod-abyssinia/dishes?sort=-price` | 200, most expensive first |
| `/dishes?q=doro&area=Bole` | 200, case-insensitive match |
| `/restaurants/no-such-place` | 404 `not_found` |

The slide's second proof uses `/restaurants/1/dishes`. With slug binding (which the same slide requires), `1` is looked up as a slug and correctly returns 404. Use the slug.

### Homework (capstone slice)

| Requirement | Implementation |
|---|---|
| Full restaurants CRUD, slug binding, `dish_count` | Done (create, read, update, delete all tested) |
| `?include=dishes` on list and single, with `whenLoaded` and `with()` | Done |
| Query-string convention on every list, copied into README | Done |
| Every response through `ApiResponse` | Done; no controller calls `response()->json()` |
| Query count with and without include | **2 and 3**, measured by a test with 40 restaurants |

```php
public function index(Request $request): JsonResponse
{
    [$column, $direction] = $this->sort($request, self::SORTABLE, 'name');

    $restaurants = Restaurant::query()
        ->withCount('dishes')
        ->when($this->includesDishes($request), fn (Builder $q) => $q->with('dishes'))
        ->when($request->filled('area'), fn (Builder $q) => $q->where('area', $request->string('area')->toString()))
        ->orderBy($column, $direction)
        ->orderBy('id')
        ->paginate($this->perPage($request))
        ->withQueryString();

    return ApiResponse::paginated(RestaurantResource::collection($restaurants));
}
```

Why the counts are 2 and 3: `withCount('dishes')` is a sub-select inside the main query, not an extra query; `paginate()` adds one `COUNT(*)` for the total; `with('dishes')` adds exactly one `WHERE restaurant_id IN (...)` query. Without eager loading it would be 1 plus one per restaurant, the 41 queries from review question 6.

### Review questions

**Q1. Path or query string.** Dish id: path (identity). Delivery area filter: query. Restaurant slug: path (identity). Sort direction: query. Page number: query.

**Q2. What `->whereNumber('dish')` does for `/dishes/abc`.** The route simply does not match, so the router moves on and finally answers 404 before any controller, binder or database query runs. That is better than checking inside the controller because invalid input never reaches your code, the controller has no defensive `if`, and the constraint documents what a valid id looks like. It also stops `/dishes/{dish}` from swallowing a literal route such as `/dishes/popular`.

**Q3. Shallow nesting in one sentence.** Nest only the actions that need the parent to find or create the child, and address the child by its own id for everything else. Of the five resource routes, `show`, `update` and `destroy` end up unnested (`/dishes/{dish}`), while `index` and `store` stay nested.

**Q4. A controller with `markPaid`.** Move it into its own single-action (invokable) controller, for example `OrderPaymentController`, behind a route that models the action as a resource (`PUT /orders/{order}/payment`). Resource controllers should hold only the five resource actions; extra verbs are a sign of a missing noun, they make the controller grow without bound, and the single-action class can carry its own middleware and rules.

**Q5. Why the sort column must be whitelisted.** `orderBy()` takes a column **name**, and column names cannot be bound as query parameters the way values can. A client-controlled column name is a SQL fragment the client writes. At best an unknown column throws a 500; at worst it enables injection or lets a client sort by a hidden column such as `cost_price` and infer its values. A test sends `sort=-cost_price` and `sort=price;drop table dishes` and confirms both fall back to the default sort and the table survives.

**Q6. 41 queries for 40 restaurants.** The N+1 problem: one query for the restaurants, then one lazy-loaded query for each restaurant's dishes when the resource touches `$this->dishes`. Fix with **`with('dishes')`** (eager load in one extra query) and **`whenLoaded('dishes')`** in the resource (only serialise the relation when it was loaded). For counts only, `withCount('dishes')` with `whenCounted()`.

### Challenge

- **`whereAlphaNumeric` on slugs breaks every multi-word restaurant.** The slide recommends it, but `yod-abyssinia` contains a hyphen, so the route never matches and returns 404. The pattern used is `[a-z0-9]+(?:-[a-z0-9]+)*`. A test proves `yod-abyssinia` works and `Yod_Abyssinia` is rejected.
- **`apiResource('restaurants.dishes')->shallow()` next to `apiResource('dishes')` registers `/dishes/{dish}` twice**, under the same route names. Laravel keeps whichever is registered last, so a refactor that reorders the file silently changes which controller answers. Only `index` and `store` need the parent, so the nested resource is registered with `->only(['index', 'store'])`.
- **N+1 is guarded automatically.** `Model::preventLazyLoading()` is on outside production, so any resource that touches a relation the controller did not eager-load fails a test instead of quietly running one query per row. `Restaurant::dishes()` uses `->chaperone()`, so each loaded dish already knows its restaurant without another query. A test confirms a 33-dish page with restaurant names costs 3 queries (count, page, one `IN` lookup).
- **`include` should be split on commas**, not compared with `=== 'dishes'`. The convention table says includes are a comma list; `?include=dishes,reviews` would otherwise ignore `dishes`.
- **`route:cache` and closures.** The slide says a closure route cannot be cached. That was true in old Laravel versions; on Laravel 11 `php artisan route:cache` succeeded with the closure routes in `routes/api.php` and they kept working. Keeping closures out of route files is still good style (they cannot be reused or unit tested), but it is no longer a hard rule.

---

## Day 57: REST API Design

### In-class exercise: design, then paginate

| Deliverable | Implementation |
|---|---|
| `API_DESIGN.md`: every URI, method, success and failure code, including orders and users | `API_DESIGN.md` (29 endpoints, built ones marked) |
| Three non-CRUD actions: cancel, mark delivered, mark paid | `OrderCancellationController`, `OrderStatusController`, `OrderPaymentController` |
| Routes split into `routes/api_v1.php`, loaded from a prefixed group | Done |
| `paginate()` on every list, default 15, cap 100, `withQueryString()` | Shared `perPage()` helper |
| One endpoint on `cursorPaginate()`, with a note on which the capstone keeps | `GET /orders` |
| Filter contract table in README matching what the code enforces | Done |

**Routes split:**

```php
// routes/api.php
Route::prefix('v1')->name('v1.')->group(base_path('routes/api_v1.php'));
```

**Safe `per_page`:**

```php
protected function perPage(Request $request): int
{
    $default = (int) config('addiseats.pagination.default_per_page', 15);
    $max = (int) config('addiseats.pagination.max_per_page', 100);
    $asked = $request->integer('per_page', $default);

    return $asked < 1 ? $default : min($asked, $max);
}
```

Tested inputs: none gives 15, `1000000` gives 100, `0` gives 15, `-5` gives 15, `abc` gives 15, `7` gives 7.

The slide's version, `min($request->integer('per_page', 15) ?: 15, 100)`, handles `0` but lets `-5` through to `paginate(-5)`. That is the third trap on the exercise slide, and the slide's own code falls into it.

**Cursor pagination for orders:**

```php
$orders = Order::query()
    ->orderByDesc('created_at')
    ->orderByDesc('id')                // tie-break: a cursor needs a unique, deterministic order
    ->cursorPaginate($this->perPage($request))
    ->withQueryString();
```

**Which one the capstone keeps, and why:** offset (`paginate()`) stays the default for dishes and restaurants, because clients show page numbers and the total count is useful for a catalogue that changes slowly. Cursor stays on orders, because order history is an ever-growing feed shown as "load more", new orders arriving while someone scrolls would shift rows between offset pages, and deep cursor pages cost the same as the first.

**The three non-CRUD actions:**

| Action | Design | Success | Failure |
|---|---|---|---|
| Mark delivered | `PATCH /orders/{id}/status` `{"status": "delivered"}` | 200 + order | 409 illegal transition, 422 unknown status |
| Cancel | `POST /orders/{id}/cancellation` `{"reason": "..."}` | 200 + order | 409 if already delivered or cancelled |
| Mark paid | `PUT /orders/{id}/payment` `{"reference": "TB-..."}` | 200 + order | 409 `already_paid` if paid with another reference, 409 `payment_reference_taken` if that reference already settled a different order |

The transition rules live in the enum, so they exist in exactly one place:

```php
public function canTransitionTo(self $next): bool
{
    return in_array($next, match ($this) {
        self::Placed => [self::Preparing, self::Cancelled],
        self::Preparing => [self::OnTheWay, self::Cancelled],
        self::OnTheWay => [self::Delivered],
        self::Delivered, self::Cancelled => [],
    }, true);
}
```

**Proof: page 2 of a filtered, sorted list still carries the filters.**

```json
"links": {
  "first": ".../api/v1/dishes?area=Bole&sort=-price&per_page=2&page=1",
  "prev":  ".../api/v1/dishes?area=Bole&sort=-price&per_page=2&page=1",
  "next":  ".../api/v1/dishes?area=Bole&sort=-price&per_page=2&page=3",
  "last":  ".../api/v1/dishes?area=Bole&sort=-price&per_page=2&page=7"
},
"meta": { "current_page": 2, "per_page": 2, "total": 13, "last_page": 7, "...": "..." }
```

### Homework (capstone slice)

| Requirement | Implementation |
|---|---|
| `API_DESIGN.md` covering every resource the capstone will have by Day 75 | Includes users, `/me`, auth, payments and dish images, marked with the day they arrive |
| Pagination live on every list with meta and links, cap enforced | Dishes, restaurants, a restaurant's dishes (offset); orders (cursor) |
| orders resource: migration, model with `OrderStatus` cast, factory, seeder | Done on Day 54 and extended here (payment and cancellation columns via the same migration set) |
| One-page comparison: REST versus GraphQL versus gRPC, and why REST | `README.md`, section "REST vs GraphQL vs gRPC" |

The orders endpoints that the slide says "follow tomorrow" already exist, because the Day 54 homework required `store()` and `show()`.

### Review questions

**Q1. The six constraints, and the session-stored order.** Client-server, stateless, cacheable, uniform interface, layered system, code on demand (optional). Storing "the current order" in a server session violates **statelessness**. The first thing to break is horizontal scaling: with two servers behind a load balancer, the request that adds a line lands on the server that never saw the "start order" request. Retries and a second browser tab break next.

**Q2. Automatic retry after a dropped connection.**
- **GET**: safe and idempotent, retry freely. It changes nothing.
- **PUT**: not safe but idempotent. Sending the same full replacement twice leaves the same state, so a retry is harmless. That is why `PUT /orders/{id}/payment` was chosen.
- **POST**: neither. The first request may have succeeded before the connection dropped, so a retry can create a second order and a second charge. Only retry a POST with an idempotency key the server deduplicates on (Day 68).

**Q3. Richardson level.** The capstone is at **level 2**: resources, correct HTTP verbs and meaningful status codes. Level 3 needs hypermedia, meaning each response carries links to the actions currently allowed (for example an order in `placed` would include a `cancel` link, and a delivered one would not). The paginated envelope already gets there in one place: `links.next`, `links.prev`, `links.first` and `links.last` tell the client where it can go next without building URLs.

**Q4. "Mark order 24550 as delivered", three ways.**
1. `PATCH /orders/24550` `{"status": "delivered"}`: simplest, but the status rules get mixed into the general update method.
2. `PATCH /orders/24550/status` (or `PUT`) `{"status": "delivered"}`: status as its own sub-resource with its own controller and rules.
3. `POST /orders/24550/delivery` `{"delivered_at": "...", "proof_photo": "..."}`: the action creates a noun, useful if delivery carries data.
When only a driver may make the transition, choose **option 2**: a dedicated single-action controller is the natural place for a policy check (`driver` role only, Day 62), and the transition rules stay in one class instead of being one branch of a general update.

**Q5. Which need a v2?**
- Adding a `rating` field: **no**, adding a field is safe.
- Renaming `price` to `price_santim`: **yes**, existing clients read `price`.
- Making `area` optional (in a request): **no**, relaxing a requirement does not break existing callers.
- Adding a fifth order status: **yes**, by the slide's own rule, because clients that `switch` over four statuses fail on the fifth. In practice many teams avoid a v2 by documenting from day one that clients must tolerate unknown enum values. The `Cancelled` status was added to this API before any client existed, which is the cheap time to do it.

**Q6. `withQueryString()` and an uncapped `per_page`.** Without `withQueryString()` the generated `next` link drops the filters, so page 2 of `?area=Bole&sort=-price` silently becomes page 2 of the unfiltered, default-sorted list. With 400,000 rows and no cap, `?per_page=1000000` makes the server load and serialise the entire table in one request: memory exhaustion, a request that times out, and an easy denial of service anyone can trigger with a URL.

### Challenge

- **The Day 57 filter table says `area` "must be a configured delivery zone".** Most APIs would just return an empty list for an unknown filter value. Here the list endpoint returns 422 `outside_delivery_zone`, because the contract says so and "documentation that is not enforced is fiction". Pick one and document it; the choice is in the README.
- **`cursorPaginate()` needs a deterministic order.** Two orders created in the same second would otherwise be skipped or repeated. `orderByDesc('id')` is the tie-breaker, and a test pages through seven orders five at a time and checks no id appears twice.
- **A unique column needs a domain check, not only an index.** Running the Postman collection twice exposed a 500: the second run paid a new order with the same TeleBirr reference, and the unique index on `payment_reference` threw. `OrderService::markPaid()` now checks first and also catches `UniqueConstraintViolationException` for the race between two simultaneous requests; both become a 409 `payment_reference_taken`.
- **The paginator's `meta.links` array** contains HTML entities (`&laquo; Previous`). It is built for Blade pagination views. It is harmless, but a public API could strip it with a custom resource collection.

---

## How the work is graded

The evaluation sheet has eight columns. This is what each one checks, where it lives in this repository, and how to demonstrate it.

| Column | What the evaluator looks for | Where it is | How to demonstrate |
|---|---|---|---|
| **Setup** | Runs from a fresh clone with only documented steps; `.env` not committed; `.env.example` present; README explains it | `README.md`, `.env.example`, `CHECKS.md` | Clone, then `composer install`, `cp .env.example .env`, `php artisan key:generate`, `touch database/database.sqlite`, `php artisan migrate --seed`, `php artisan serve`, open `/api/health` |
| **Scalar Type Declarations** | `declare(strict_types=1)` first in every file; typed parameters and returns; nullable `?type`; `void`; money as integer santim; `===` | Every PHP file written for the project (app, routes, config, migrations, factories, seeders, tests); `app/Support/pricing.php`; `App\Support\Money` | `D52PricingTest` proves `deliveryFee('Bole', '7')` throws `TypeError`; explain why `0.1 + 0.2 !== 0.3` |
| **Routing and Versioning** | `routes/api.php` (stateless, `/api`); `v1` prefix and names; constraints; `apiResource`; nested and single-action routes; `route:list` | `routes/api.php` loads `routes/api_v1.php` under `prefix('v1')->name('v1.')`; `Route::pattern()` in `AppServiceProvider` | `php artisan route:list --path=api` (22 routes); `/api/v1/dishes/abc` gives 404 |
| **Model and Relationships** | Migrations moving forward only; `$fillable`; `casts()` including the enum; `hasMany` and `belongsTo` both ways; factories and seeders; eager loading | `Restaurant hasMany Dish`, `Dish belongsTo Restaurant`, `Dish hasMany OrderLine`, `Order hasMany OrderLine`, `OrderLine belongsTo Order` and `Dish`; `Order` casts `status` to `OrderStatus` | `?include=dishes` and the query-count tests (2, 3 and 3 queries); tinker: `Dish::first()->restaurant->name` |
| **Controller** | Thin controllers in `Api\V1`; five-method resource controllers; `__invoke` for non-CRUD actions; constructor injection, never `new Service()` | Dish, Restaurant, RestaurantDish, Order controllers; OrderStatus, OrderCancellation, OrderPayment, Menu, Quote single-action controllers; rules in `OrderService` and `MenuService` | A test fails if any controller writes `new ...Service` or `new ...Rule` |
| **RestAPI + Filtering** | Nouns not verbs; correct codes 200, 201 with `Location`, 204, 404, 405, 409, 422; filtering, search, sorting through a whitelist; pagination with a cap and filters kept in links | `ListsResources` trait (`perPage()`, `sort()`), Dish scopes `inArea`, `under`, `fasting`, `search`; `withQueryString()`; cursor pagination on orders | `/api/v1/dishes?area=Bole&sort=-price&per_page=2&page=2` and read `links.next`; `?per_page=1000000` returns 100 |
| **Resources** | API Resource classes instead of raw models; internal fields hidden; `whenLoaded` and `whenCounted`; `data` wrapper; one success and error envelope | `app/Http/Resources/V1/*`; `ApiResponse`; error rendering in `bootstrap/app.php` | A test asserts the exact DishResource field list and that `cost_price` never appears |
| **Request Handling** | Typed input (`integer()`, `boolean()`, `string()`, `filled()`, `enum()`, `collect()`); never `$request->all()` into `create()`; invalid input gives 422 not 500; one error shape | Every controller; `$request->validate()` on each write; `validation_failed`, `not_found`, `method_not_allowed` envelopes | Send `{"price": "two"}` or an order with `qty: 0` and get a 422 `validation_failed` |

Two likely questions per column are already answered in the Review Questions of the matching day. The one gap an evaluator could raise is **Form Request classes** (`php artisan make:request`), which the course formally teaches on Day 58. The validation rules already exist in the controllers, so moving them into Form Requests is a short, mechanical change.

## Testing with Postman

The repository includes `addis-eats-api.postman_collection.json`: 40 requests in five folders (Day 52 basics, Menu, Dishes, Restaurants, Orders) with 55 automatic assertions on status codes, error codes, the `Location` header, the hidden `cost_price` field and pagination links.

1. Start the API: `php artisan migrate:fresh --seed` then `php artisan serve`.
2. In Postman choose **Import**, then select the collection file.
3. The collection variable `baseUrl` is `http://127.0.0.1:8000`. Change it if you serve on another port.
4. Click a request and **Send**, or right-click the collection, choose **Run collection**, and run all 40 in order. Requests pass ids to each other through collection variables (`dishId`, `orderId`, `restaurantSlug`, `payRef`), so run them in order.
5. Command line alternative, the same tool Day 69 uses: `npx newman run addis-eats-api.postman_collection.json`.

Verified: three consecutive runs against the same database, 40 requests and 55 assertions, 0 failures.

---

## Where the slides are wrong or outdated

Each item was confirmed by running code on Laravel 11.57.1 / PHP 8.3.6.

| Day | Slide says | What actually happens | What this repository does |
|---|---|---|---|
| 52 | `app/Support/pricing.php` functions just work | PSR-4 never loads plain functions | Registers the file under `autoload.files` |
| 52, 55 | `number_format($price / 100, 2)` for `price_etb` | Gives `"1,200.00"` from ETB 1,000 up | `Money::decimal()`, integer arithmetic, no separator |
| 53 | `feeFor(string $area): int` | Cannot express a free-delivery threshold | `feeFor(string $area, int $subtotal): int` |
| 53 | `MenuService(Collection $dishes)` | The container injects an empty collection | Reads from Eloquent on Day 54 |
| 53 | Q6: `filter()->pluck()` returns keys 0, 3, 7 | `pluck()` renumbers; `filter()->map()` keeps gaps | Always end chains with `values()` |
| 53 vs 52 | Piassa fee 5500 vs 6000 | Inconsistent | One table in `config/addiseats.php` |
| 56 | `whereAlphaNumeric('slug')` | Rejects `yod-abyssinia` | Slug regex with hyphens |
| 56 | `ApiResponse::ok` uses `array_filter` | Drops `data` when it is `[]` | Explicit check |
| 56 | `ApiResponse::error` uses `compact('msg')` | Key is `msg`, not `message` | Uses `message` |
| 56 | `shallow()` beside `apiResource('dishes')` | Duplicate `/dishes/{dish}` routes and names | Nested routes limited to index and store |
| 56 | `/restaurants/1/dishes` as a proof | 404 once binding is on slug | Use the slug |
| 56 | Closures cannot be route-cached | `route:cache` works on Laravel 11 | Closures kept only for trivial Day 52 routes |
| 57 | `min($request->integer('per_page', 15) ?: 15, 100)` | `-5` reaches `paginate()` | Lower bound of 1 |
| 55 | Validation waits for Day 58 | Bad input becomes a 500 meanwhile | Minimal `validate()` now |
| all | Laravel 11 | `composer audit` lists unpatched advisories on 11.x | Upgrade the major before any real deployment |

---

## Verification log

**Test suite** (`./vendor/bin/pest`):

```text
Tests:    70 passed (233 assertions)
```

| Test file | Covers |
|---|---|
| `tests/Unit/D52PricingTest.php` | Fees per area, the 50000 boundary, the slide order total, empty order, `TypeError` under strict types, float error |
| `tests/Unit/D53DomainTest.php` | Readonly `Dish`, enum values and transitions, the three pricing rules, `OutsideZoneException` data for every rule, receipt, empty order, quantity zero, `Money::decimal`, collection key gaps |
| `tests/Feature/D52D54FrameworkTest.php` | Day 52 routes, menu filtering, empty list shape, 422 envelope, container binding swap, no `env()` in `app/`, no `new Service` in controllers |
| `tests/Feature/D55DishesTest.php` | Filters, blank and zero filters, 404 variants, exact resource fields, 201 plus `Location`, mass-assignment protection, 422 instead of 500, PATCH versus PUT, 204 and soft delete, 405 |
| `tests/Feature/D56RestaurantsTest.php` | Slug binding with hyphens, nested list sorting, whitelist fallback and injection attempt, case-insensitive search with literal `%`, `include`, query counts 2 and 3, nested create, restaurant CRUD |
| `tests/Feature/D57DesignTest.php` | `per_page` sanitising, filters in links, page past the end, cursor paging with no repeats, order totals and merged lines, five bad-order cases, status transitions and 409, cancellation, idempotent payment |

**Postman / Newman:** 40 requests, 55 assertions, 0 failures, three consecutive runs.

**Route table** (`php artisan route:list --path=api`): 22 routes, listed in the README endpoint table.

**Fresh clone check:** cloned the repository into an empty folder and ran `composer install`, `cp .env.example .env`, `php artisan key:generate`, `touch database/database.sqlite`, `php artisan migrate --seed`, `php artisan serve`; the health, dishes and restaurants endpoints answered 200.

**Runtime checks performed with curl:** 201 with `Location` on order create, 409 on an illegal transition, 405 with an `Allow` header on `DELETE /dishes`, filters preserved in pagination links, `env()` returning `NULL` after `config:cache` while `config()` still worked, and `route:cache` succeeding with closure routes.
