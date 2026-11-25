# Day 58: sixteen request/response pairs

Captured from `php artisan serve` against a freshly seeded database. Generated, not hand written.

## 01 Empty dish body: as expected

```http
POST /api/v1/dishes
Content-Type: application/json

{}
```

```http
HTTP/1.1 422

{
  "error": {
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "meta": {
      "name": [
        "The name field is required."
      ],
      "restaurant_id": [
        "The restaurant field is required."
      ],
      "price": [
        "The price field is required."
      ],
      "area": [
        "The area field is required."
      ]
    }
  }
}
```

## 02 Negative price: as expected

```http
POST /api/v1/dishes
Content-Type: application/json

{"name": "Test Dish", "restaurant_id": 1, "price": -500, "area": "Bole"}
```

```http
HTTP/1.1 422

{
  "error": {
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "meta": {
      "price": [
        "The price must be between 1 and 1000000 santim."
      ]
    }
  }
}
```

## 03 Price as decimal ETB: as expected

```http
POST /api/v1/dishes
Content-Type: application/json

{"name": "Test Dish", "restaurant_id": 1, "price": 685.5, "area": "Bole"}
```

```http
HTTP/1.1 422

{
  "error": {
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "meta": {
      "price": [
        "The price must be a whole number of santim, for example 68500 for ETB 685.00."
      ]
    }
  }
}
```

## 04 Price as boolean: as expected

```http
POST /api/v1/dishes
Content-Type: application/json

{"name": "Test Dish", "restaurant_id": 1, "price": true, "area": "Bole"}
```

```http
HTTP/1.1 422

{
  "error": {
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "meta": {
      "price": [
        "The price must be a whole number of santim, for example 68500 for ETB 685.00."
      ]
    }
  }
}
```

## 05 Unknown restaurant: as expected

```http
POST /api/v1/dishes
Content-Type: application/json

{"name": "Test Dish", "restaurant_id": 999, "price": 18000, "area": "Bole"}
```

```http
HTTP/1.1 422

{
  "error": {
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "meta": {
      "restaurant_id": [
        "The selected restaurant is invalid."
      ]
    }
  }
}
```

## 06 Unserved area: as expected

```http
POST /api/v1/orders
Content-Type: application/json

{"customer_name": "Almaz", "phone": "0911234567", "area": "Sululta", "lines": [{"dish_id": 3, "qty": 1}]}
```

```http
HTTP/1.1 422

{
  "error": {
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "meta": {
      "area": [
        "Addis Eats does not deliver to that area yet."
      ]
    }
  }
}
```

## 07 Short phone: as expected

```http
POST /api/v1/orders
Content-Type: application/json

{"customer_name": "Almaz", "phone": "12345", "area": "Bole", "lines": [{"dish_id": 3, "qty": 1}]}
```

```http
HTTP/1.1 422

{
  "error": {
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "meta": {
      "phone": [
        "Enter a TeleBirr number as 09XXXXXXXX."
      ]
    }
  }
}
```

## 08 No lines: as expected

```http
POST /api/v1/orders
Content-Type: application/json

{"customer_name": "Almaz", "phone": "0911234567", "area": "Bole", "lines": []}
```

```http
HTTP/1.1 422

{
  "error": {
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "meta": {
      "lines": [
        "The lines field is required."
      ]
    }
  }
}
```

## 09 Too many lines (31): as expected

```http
POST /api/v1/orders
Content-Type: application/json

{"customer_name": "Almaz", "phone": "0911234567", "area": "Bole", "lines": [{"dish_id": 3, "qty": 1}, {"dish_id": 3, "qty": 1}, {"dish_id": 3, "qty": 1}, {"dish_id": 3, "qty": 1}, {"dish_id": 3, "qty" ... (31 lines)
```

```http
HTTP/1.1 422

{
  "error": {
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "meta": {
      "lines": [
        "An order may have at most 30 lines."
      ]
    }
  }
}
```

## 10 Quantity out of range on line 2: as expected

```http
POST /api/v1/orders
Content-Type: application/json

{"customer_name": "Almaz", "phone": "0911234567", "area": "Bole", "lines": [{"dish_id": 3, "qty": 1}, {"dish_id": 6, "qty": 51}]}
```

```http
HTTP/1.1 422

{
  "error": {
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "meta": {
      "lines.1.qty": [
        "Each quantity must be between 1 and 50."
      ]
    }
  }
}
```

## 11 Dish that never existed: as expected

```http
POST /api/v1/orders
Content-Type: application/json

{"customer_name": "Almaz", "phone": "0911234567", "area": "Bole", "lines": [{"dish_id": 99999, "qty": 1}]}
```

```http
HTTP/1.1 422

{
  "error": {
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "meta": {
      "lines.0.dish_id": [
        "The selected dish is invalid."
      ]
    }
  }
}
```

## 12 Dishes from two areas: as expected

```http
POST /api/v1/orders
Content-Type: application/json

{"customer_name": "Almaz", "phone": "0911234567", "area": "Bole", "lines": [{"dish_id": 3, "qty": 1}, {"dish_id": 2, "qty": 1}]}
```

```http
HTTP/1.1 422

{
  "error": {
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "meta": {
      "lines": [
        "All dishes in one order must be from the same area."
      ]
    }
  }
}
```

## 13 Client tries to set the price: as expected

```http
POST /api/v1/orders
Content-Type: application/json

{"customer_name": "Almaz", "phone": "0911234567", "area": "Bole", "lines": [{"dish_id": 3, "qty": 1, "price": 1}]}
```

```http
HTTP/1.1 422

{
  "error": {
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "meta": {
      "lines.0": [
        "Each line may contain only dish_id and qty. Prices are set by the server."
      ]
    }
  }
}
```

## 14 Truncated JSON body: as expected

```http
POST /api/v1/orders
Content-Type: application/json

{"customer_name": "Almaz", "lines": [
```

```http
HTTP/1.1 400

{
  "error": {
    "code": "malformed_json",
    "message": "The request body is not valid JSON.",
    "meta": {
      "reason": "Syntax error"
    }
  }
}
```

## 15 PATCH price as text: as expected

```http
PATCH /api/v1/dishes/1
Content-Type: application/json

{"price": "two hundred"}
```

```http
HTTP/1.1 422

{
  "error": {
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "meta": {
      "price": [
        "The price must be a whole number of santim, for example 68500 for ETB 685.00."
      ]
    }
  }
}
```

## 16 Restaurant with a landline phone: as expected

```http
POST /api/v1/restaurants
Content-Type: application/json

{"name": "Totot", "area": "Megenagna", "phone": "0111234567"}
```

```http
HTTP/1.1 422

{
  "error": {
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "meta": {
      "slug": [
        "The slug has already been taken."
      ],
      "phone": [
        "Enter a TeleBirr number as 09XXXXXXXX."
      ]
    }
  }
}
```

