# Day 58: expected results for bad_requests.http

Every one is a 4xx in the same envelope `{"error":{"code","message","meta"}}`. None is a 500. Dish ids assume the seeded database (`php artisan migrate:fresh --seed`): dishes 3 and 6 are in Bole, dish 2 is not.

| # | Request | Status | Code | Field named in `meta` | Why |
|---|---|---|---|---|---|
| 01 | Empty dish body: `POST /api/v1/dishes` | 422 | `validation_failed` | `name, restaurant_id, price, area` | Every required field is named at once, not one at a time. |
| 02 | Negative price: `POST /api/v1/dishes` | 422 | `validation_failed` | `price` | Santim rule: a whole number from 1 to 1,000,000. |
| 03 | Price as decimal ETB: `POST /api/v1/dishes` | 422 | `validation_failed` | `price` | Money is integer santim. 685.5 is a float. |
| 04 | Price as boolean: `POST /api/v1/dishes` | 422 | `validation_failed` | `price` | Laravel's integer rule accepts true; the Santim rule does not. |
| 05 | Unknown restaurant: `POST /api/v1/dishes` | 422 | `validation_failed` | `restaurant_id` | exists:restaurants,id. A missing foreign key is a 422, never a 500 from the database. |
| 06 | Unserved area: `POST /api/v1/orders` | 422 | `validation_failed` | `area` | Rule::in on the keys of config('addiseats.zones'). |
| 07 | Short phone: `POST /api/v1/orders` | 422 | `validation_failed` | `phone` | TeleBirrNumber: 09 followed by eight digits, after normalising +251 and spaces. |
| 08 | No lines: `POST /api/v1/orders` | 422 | `validation_failed` | `lines` | lines is required and needs at least one entry. |
| 09 | Too many lines (31): `POST /api/v1/orders` | 422 | `validation_failed` | `lines` | max:30 caps the array, so a client cannot make the server price 10,000 lines. |
| 10 | Quantity out of range on line 2: `POST /api/v1/orders` | 422 | `validation_failed` | `lines.1.qty` | The key carries the index, so the client can highlight the exact row. |
| 11 | Dish that never existed: `POST /api/v1/orders` | 422 | `validation_failed` | `lines.0.dish_id` | exists on every lines.*.dish_id. |
| 12 | Dishes from two areas: `POST /api/v1/orders` | 422 | `validation_failed` | `lines` | The after() hook: one order, one delivery area. |
| 13 | Client tries to set the price: `POST /api/v1/orders` | 422 | `validation_failed` | `lines.0` | lines.* is array:dish_id,qty. Unknown keys are refused, not silently ignored, because a price from the client is an attack. |
| 14 | Truncated JSON body: `POST /api/v1/orders` | 400 | `malformed_json` | `(none)` | Laravel would treat it as an empty body and answer 'name is required'. The body could not be read at all, so 400. |
| 15 | PATCH price as text: `PATCH /api/v1/dishes/1` | 422 | `validation_failed` | `price` | PATCH rules are sometimes, so only the price is checked, and it fails. |
| 16 | Restaurant with a landline phone: `POST /api/v1/restaurants` | 422 | `validation_failed` | `phone` | The same TeleBirrNumber rule object guards restaurants too. |

The same sixteen cases run in `tests/Feature/D58ValidationTest.php` and in the Postman folder "6. Bad requests (Day 58)".
