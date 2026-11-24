# Addis Eats API design (Day 57)

Base path `/api/v1`. Plural, lower-case, hyphenated nouns; no verbs; no trailing slash or extension. Every list endpoint follows the query-string convention in the README. Status in the "Built" column: **yes** exists today, **D58+** arrives on the day the topic is taught.

## Resources

| Resource | Kind | Holds |
|---|---|---|
| dishes | collection | name, restaurant, price (santim), fasting flag, area |
| restaurants | collection, bound by slug | name, slug, area, phone, opening hours |
| restaurants/{slug}/dishes | sub-resource | dishes of one restaurant (list, create) |
| orders | collection | customer, area, lines, subtotal, fee, total, status |
| orders/{id}/lines | sub-resource | lines of one order (read only) |
| orders/{id}/status | state sub-resource | the order's current status |
| orders/{id}/cancellation | action noun | a cancellation and its reason |
| orders/{id}/payment | singleton | the order's payment |
| users | collection | name, email, role, TeleBirr number |
| me | singleton | the authenticated user |
| auth | actions | register, login, refresh |
| dishes/{id}/image | singleton | the dish photo |

## Endpoint table

| Method | URI | Purpose | Success | Failure | Built |
|---|---|---|---|---|---|
| GET | /dishes | List, filter, sort, page | 200 | 422 bad area | yes |
| POST | /dishes | Create | 201 + Location | 422 | yes |
| GET | /dishes/{id} | Read | 200 | 404 | yes |
| PUT | /dishes/{id} | Replace | 200 | 404, 422 | yes |
| PATCH | /dishes/{id} | Partial update | 200 | 404, 422 | yes |
| DELETE | /dishes/{id} | Soft delete | 204 | 404 | yes |
| GET | /restaurants | List, `?include=dishes` | 200 | | yes |
| POST | /restaurants | Create | 201 + Location | 422 | yes |
| GET | /restaurants/{slug} | Read | 200 | 404 | yes |
| PUT, PATCH | /restaurants/{slug} | Update | 200 | 404, 422 | yes |
| DELETE | /restaurants/{slug} | Delete | 204 | 404 | yes |
| GET | /restaurants/{slug}/dishes | Dishes of a restaurant | 200 | 404 | yes |
| POST | /restaurants/{slug}/dishes | Add a dish to it | 201 + Location | 404, 422 | yes |
| GET | /orders | Order history, cursor paged | 200 | | yes (mine-only after D61) |
| POST | /orders | Place an order | 201 + Location | 422 invalid, 409 dish withdrawn | yes |
| GET | /orders/{id} | Read with lines | 200 | 404 | yes |
| GET | /orders/{id}/lines | Lines only | 200 | 404 | D58+ |
| PATCH | /orders/{id}/status | Move to preparing, on_the_way, delivered | 200 | 409 illegal transition, 422 | yes (role check D62) |
| POST | /orders/{id}/cancellation | Cancel | 200 | 409 | yes |
| PUT | /orders/{id}/payment | Mark paid, idempotent | 200 | 409 already paid, 422 | yes |
| POST | /payments/telebirr | Start an ETB payment | 202 | 422, 503 TeleBirr down | D64+ |
| POST | /auth/register | Register | 201 | 422 | D61 |
| POST | /auth/login | Obtain a token | 200 | 401 | D61 |
| POST | /auth/refresh | New token | 200 | 401 | D61 |
| GET | /me | Current user | 200 | 401 | D61 |
| PATCH | /me | Update profile | 200 | 401, 422 | D61 |
| GET | /users | List users (admin) | 200 | 401, 403 | D62 |
| PUT | /dishes/{id}/image | Upload the photo | 200 | 413, 422 | D64 |
| GET | /dishes/{id}/image | Serve the photo | 200 | 404 | D64 |

## The three non-CRUD actions

| Action | Chosen design | Why |
|---|---|---|
| Mark delivered | `PATCH /orders/{id}/status` `{"status":"delivered"}` behind single-action `OrderStatusController` | It is a state transition with rules (placed to preparing to on_the_way to delivered). Modelling status as its own sub-resource keeps those rules in one class. From Day 62 only the `driver` role may send `delivered`; a customer gets 403. |
| Cancel | `POST /orders/{id}/cancellation` `{"reason": "..."}` | A cancellation carries data of its own (the reason) and is only legal from placed or preparing, so it is a noun we create. Returns 200 with the updated order because the cancellation is stored on the order, not as a separate addressable record. |
| Mark paid | `PUT /orders/{id}/payment` `{"reference": "TB-..."}` | A payment is a singleton of the order. PUT is idempotent, so a client that lost the response can retry: the same reference is a no-op, a different reference is 409. POST would risk recording two payments. |

## Status codes used

200 read or update, 201 create with Location, 204 delete, 404 unknown resource or route, 405 wrong verb, 409 legal request that the current state forbids, 422 invalid content, 500 only for our bugs.

## Pagination decision

Offset (`paginate()`) for dishes and restaurants: clients show page numbers and the total is useful. Cursor (`cursorPaginate()`) for orders: order history is an ever-growing feed shown as "load more", new orders arriving while a user scrolls must not shift rows between pages, and deep pages stay fast. The capstone keeps offset as the default and cursor for orders.
