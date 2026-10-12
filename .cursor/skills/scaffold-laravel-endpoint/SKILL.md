---
name: scaffold-laravel-endpoint
description: Scaffolds a Laravel API v1 endpoint with Form Request, Policy, Resource, and feature test. Use when adding CRUD, routes, controllers, or Sanctum-protected API resources in backend/.
---

# Scaffold Laravel endpoint

## Checklist

```
- [ ] Model + migration (+ factory if useful)
- [ ] Policy with create/view/update/delete rules
- [ ] Form Request(s) for store/update
- [ ] Action or Service for business logic
- [ ] API Resource for JSON shape (snake_case)
- [ ] Route in routes/api.php under v1 prefix + auth:sanctum
- [ ] Feature test: guest denied, validation 422, authorized success
```

## Route pattern

- `GET/POST /api/v1/things`, `GET/PATCH/DELETE /api/v1/things/{thing}`

## Controller

- Authorize early; delegate to Action; return Resource or `response()->json()` for errors only if not using exception handler.

## Tokens

- Document which token abilities are required on the route middleware or policy.

## Do not

- Put business logic in controllers or Resources.
- Return inconsistent JSON keys vs existing Resources.
