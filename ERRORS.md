# Error catalogue (Day 58)

Every failure from `/api/*` has the same shape:

```json
{ "error": { "code": "validation_failed", "message": "The given data was invalid.", "meta": { "price": ["..."] } } }
```

`code` is the contract: snake_case, stable, never translated. Branch on it. `message` is for humans and may change. `meta` is always an object (`{}` when empty).

| Code | Status | Raised by | When | What the client should do |
|---|---|---|---|---|
| `malformed_json` | 400 | `RejectMalformedJson` middleware | The body says JSON but cannot be parsed | Fix the serialisation; do not retry as is |
| `validation_failed` | 422 | Form Requests (`ValidationException`) | A field is missing, has the wrong type or format, or is outside the allowed set. `meta` maps each field, with its array index (`lines.1.qty`), to its messages | Show the messages next to the named fields |
| `outside_delivery_zone` | 422 | `OutsideZoneException` | An area outside `config/addiseats.php` zones reached the domain (for example a list filter) | Choose a served area |
| `empty_order` | 422 | `EmptyOrderException` | Domain safety net behind the `lines` rule | Add at least one line |
| `unknown_dish` | 422 | `UnknownDishException` | Domain safety net behind `exists:dishes,id` | Remove the line |
| `unauthenticated` | 401 | `AuthenticationException` | No token or an expired one (Day 61) | Log in again |
| `forbidden` | 403 | `AuthorizationException` | Authenticated but not permitted (Day 62) | Do not retry |
| `not_found` | 404 | `ModelNotFoundException` (route model binding) | The id or slug does not exist | Refresh the list |
| `route_not_found` | 404 | `NotFoundHttpException` | No route matches the path (including `/dishes/abc`) | Fix the URL |
| `method_not_allowed` | 405 | `MethodNotAllowedHttpException` | The path exists, the verb does not. `meta.allow` lists the verbs | Use an allowed verb |
| `illegal_transition` | 409 | `IllegalTransitionException` | The status change is not allowed from the current status, or a cancelled order is paid | Refresh the order |
| `dish_withdrawn` | 409 | `DishWithdrawnException` | A dish was withdrawn after the client loaded the menu | Refresh the menu, then reorder |
| `already_paid` | 409 | `AlreadyPaidException` | The order is already paid with a different reference | Do not charge again |
| `payment_reference_taken` | 409 | `PaymentReferenceTakenException` | The reference already settled another order | Check the payment |
| `rate_limited` | 429 | `ThrottleRequestsException` | Too many requests (Day 63). `Retry-After` header set | Wait, then retry |
| `http_<status>` | any | Other `HttpException`s | A framework HTTP error with no specific code | Treat by status family |
| `server_error` | 500 | Anything else (`Throwable`, registered last) | Our bug. The detail is logged; the response carries only `meta.ref` | Report the `ref` to support |

## 422 or 409?

Could this request ever have succeeded exactly as written?

- **No** (negative price, unknown area, malformed phone, a dish id that never existed): **422**, caught by a validation rule.
- **Yes, but not now** (the order was cancelled, the dish was withdrawn a minute ago, the payment already went through): **409**, caught in the service where the state lives.

## What never leaves the server

Exception messages, SQL, file paths and stack traces. `APP_DEBUG=false` in production, and even with debug on, the `Throwable` handler for `/api/*` returns only a reference such as `err_k3j9x2mq`, which is also written to the log with the full exception.
