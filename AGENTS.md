# AGENTS.md

## Project Structure

- `backend/` - Laravel 12 API (PHP 8.2+)
- `fronent/` - Quasar/Vue 3 frontend (note: directory is misspelled as "fronent")

## Commands

### Backend
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
php artisan queue:work --timeout=3600 --memory=1024  # for async jobs
```

### Frontend
```bash
cd fronent
npm install
npm run dev      # runs quasar dev
npm run build    # production build
npm run lint    # eslint check
npm run format  # prettier
```

### Combined Dev (runs both servers)
```bash
cd backend && npm run dev
```

## Key Dependencies

- Backend: Laravel Sanctum (auth), Maatwebsite Excel (exports), barryvdh/laravel-dompdf (PDFs), OpenSpout (sheets)
- Frontend: Pinia (state), Vue Router, ApexCharts, ExcelJS, ZXing (QR)

## Notes

- Queue worker must be running for export/background jobs
- Storage link required: `php artisan storage:link`
- Clear cache: `php artisan cache:clear && php artisan config:clear && php artisan route:clear`