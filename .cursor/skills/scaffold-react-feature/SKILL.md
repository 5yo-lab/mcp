---
name: scaffold-react-feature
description: Scaffolds a standalone React feature with API client methods, hooks, and page components using Bearer token auth. Use when adding pages, routes, or UI that consumes the Laravel API in frontend/.
---

# Scaffold React feature

## Checklist

```
- [ ] API method(s) in shared client (Bearer header)
- [ ] Types matching Laravel Resource JSON (snake_case or mapped camelCase)
- [ ] Route registered in app router
- [ ] Page + feature components under features/<name>/
- [ ] Hook for fetch/mutation (e.g. TanStack Query) with loading/error
- [ ] Forms map 422 errors to fields
- [ ] 401 → redirect to login/token entry per app pattern
```

## Structure

```text
frontend/src/features/<name>/
  api.ts
  hooks.ts
  <Name>Page.tsx
  components/
```

## Auth

- Use project API client; never hardcode tokens in source.

## Do not

- Add Inertia or server-rendered Laravel views for this UI.
- Call non-versioned API paths if `/api/v1` is the standard.
