# OpenAPI → TypeScript

```bash
cd frontend
npm install
npm run generate:api
```

Writes `frontend/types/openapi.d.ts` from `openapi/openapi.yaml`.

Hand-written domain types in `types/api.ts` remain for app code; generated file is the contract mirror.
