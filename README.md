# addis-eats-api

REST API for Addis Eats, a food-delivery service in Addis Ababa. CodeOps Module 4 backend capstone, **Laravel (PHP) track**, Days 52 to 57.

| | |
|---|---|
| Track | Laravel (PHP) |
| PHP | 8.3 (8.2 is the minimum for Laravel 11) |
| Framework | Laravel 11.x |
| Database | SQLite (file at `database/database.sqlite`) |
| Tests | Pest 3 (68 tests) |

Full day-by-day exercise write-up: [`docs/MODULE4_LARAVEL_D51-D57_SOLUTIONS.md`](docs/MODULE4_LARAVEL_D51-D57_SOLUTIONS.md).

## Run it from a fresh clone

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve            # http://127.0.0.1:8000
```

The two commands you need day to day: `php artisan migrate:fresh --seed` and `php artisan serve`.

Run the tests with `./vendor/bin/pest`. To test by hand, import `addis-eats-api.postman_collection.json` into Postman (40 requests with assertions) or run `npx newman run addis-eats-api.postman_collection.json`. Seed 1,200 extra dishes to see pagination with `php artisan db:seed --class=LargeDishSeeder`.

## Endpoints

All versioned routes live in `routes/api_v1.php` and are served under `/api/v1`.

| Method | URI | Purpose | Success | Failure |
|---|---|---|---|---|
| GET | /api/health | Liveness check | 200 | |
| GET | /api/areas | Delivery zones | 200 | |
| GET | /api/quote | Day 52 hard-coded quote | 200 | |
| GET | /api/v1/menu?area=&budget= | Affordable dishes in an area | 200 | 422 outside_delivery_zone |
| GET | /api/v1/dishes | List dishes (filter, search, sort, page) | 200 | 422 outside_delivery_zone |
| POST | /api/v1/dishes | Create a dish | 201 + Location | 422 |
| GET | /api/v1/dishes/{dish} | One dish | 200 | 404 |
| PUT | /api/v1/dishes/{dish} | Replace a dish (all fields required) | 200 | 404, 422 |
| PATCH | /api/v1/dishes/{dish} | Change some fields | 200 | 404, 422 |
| DELETE | /api/v1/dishes/{dish} | Soft-delete a dish | 204 | 404 |
| GET | /api/v1/restaurants | List restaurants, `?include=dishes` | 200 | |
| POST | /api/v1/restaurants | Create a restaurant (slug generated) | 201 + Location | 422 |
| GET | /api/v1/restaurants/{slug} | One restaurant, `?include=dishes` | 200 | 404 |
| PUT/PATCH | /api/v1/restaurants/{slug} | Update | 200 | 404, 422 |
| DELETE | /api/v1/restaurants/{slug} | Delete | 204 | 404 |
| GET | /api/v1/restaurants/{slug}/dishes | Dishes of one restaurant | 200 | 404 |
| POST | /api/v1/restaurants/{slug}/dishes | Add a dish to a restaurant | 201 + Location | 404, 422 |
| GET | /api/v1/orders | Order history (cursor pagination) | 200 | |
| POST | /api/v1/orders | Place an order | 201 + Location | 422 |
| GET | /api/v1/orders/{order} | One order with lines | 200 | 404 |
| PATCH | /api/v1/orders/{order}/status | Move along the status lifecycle | 200 | 409, 422 |
| POST | /api/v1/orders/{order}/cancellation | Cancel with a reason | 200 | 409 |
| PUT | /api/v1/orders/{order}/payment | Mark paid (idempotent) | 200 | 409, 422 |

## Query-string convention (every list endpoint)

| Parameter | Type | Applies to | Default | Notes |
|---|---|---|---|---|
| area | string | dishes, restaurants | none | On dishes it must be a configured zone, otherwise 422 |
| max_price | integer, santim | dishes | none | Inclusive. `0` returns nothing |
| fasting | boolean | dishes | none | 1, true, on and yes are accepted |
| q | string | dishes, restaurants | none | Name contains, case-insensitive. `%` and `_` are literal |
| sort | string | all lists | dishes: price, restaurants: name | dishes: name, price, created_at. restaurants: name, created_at. Leading `-` means descending. Unknown columns fall back to the default |
| include | comma list | restaurants | none | `dishes` |
| page | integer | offset lists | 1 | Past the last page returns `"data": []` |
| per_page | integer | all lists | 15 | Capped at 100. 0, negative or non-numeric becomes 15 |
| cursor | string | orders | none | Opaque. Follow `links.next` instead of building it |

Unknown query parameters are ignored, never rejected.

## Response envelope

Success: `{"data": ..., "meta": {...}}`. Paginated lists add `links` and `meta` automatically. `204` has no body.

Failure, always: `{"error": {"code": "machine_code", "message": "Human text", "meta": {}}}`.

Codes in use: `validation_failed`, `outside_delivery_zone`, `empty_order`, `unknown_dish`, `not_found`, `route_not_found`, `method_not_allowed`, `illegal_status_transition`, `already_paid`.

## Money

Every amount is an integer number of **santim** (ETB 245.50 is `24550`) in PHP, JSON and the database. Responses also publish a decimal string such as `"price_etb": "245.50"` built with integer arithmetic and no thousands separator.

## REST vs GraphQL vs gRPC, and why this API is REST

**REST** models the system as resources addressed by URIs and manipulated with the standard HTTP verbs. It is good at public, cacheable, widely consumable APIs: any client that speaks HTTP can use it, GET responses can be cached by browsers, proxies and CDNs, and status codes give a shared failure vocabulary. Its costs are over-fetching and under-fetching (a screen may need three requests, or get fields it does not use) and the need to version the contract by hand.

**GraphQL** exposes one endpoint and a typed schema; the client sends a query naming exactly the fields and relations it wants. It is good for rich frontends that aggregate many resources per screen, and it evolves by adding fields rather than versions. Its costs: HTTP caching mostly stops working (everything is a POST to one URL), authorization and rate limiting must be done per field, and a careless query can fan out into an N+1 storm on the server.

**gRPC** is a binary RPC framework over HTTP/2 using Protocol Buffers contracts and generated clients. It is good for internal service-to-service calls: compact, fast, strongly typed, with streaming in both directions. Its costs: browsers cannot call it directly without a proxy (gRPC-Web), payloads are not human readable, and it needs code generation in every consumer.

**Why REST here:** our consumers are a React web app, a mobile app and Postman. They all speak plain HTTP and JSON, list endpoints benefit from caching, the resource surface is small and stable, and every one of the four course tracks must expose the identical contract, which REST plus a published endpoint table makes trivial to check.
