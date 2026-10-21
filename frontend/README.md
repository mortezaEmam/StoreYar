# StoreYar Frontend

## Run

```bash
cd frontend
npm install --legacy-peer-deps
export NUXT_PUBLIC_API_BASE=http://localhost:8000/api
npm run dev
```

Open the URL printed by Nuxt (often `http://localhost:3001` if 3000 is busy).

Backend:

```bash
cd ..   # repo root
php artisan serve
```

## CORS

If the browser blocks API calls, copy `config/cors.php` paths from the project
or merge `allowed_origins` for `localhost:3000` and `3001`.

## Structure

```
pages/          routes
layouts/        default shell + toasts
composables/    useApi, useToast
stores/         session (Pinia)
assets/css/     design system
```

## Happy path

1. Register / Login  
2. Business → create or select org  
3. Products → add  
4. Stock → initialize  
5. Orders → draft → line → confirm  
6. Payments → initiate → succeed  
7. Shipments → create → ship  
8. Audit → review  
