# Project: Fitness Group App

## Environment

This project runs in **Laravel Sail**. All `php artisan` commands must be run through Sail:

```bash
./vendor/bin/sail artisan <command>
```

For example:
```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan route:list
./vendor/bin/sail artisan tinker
```

Other Sail commands:
```bash
./vendor/bin/sail up -d        # Start containers
./vendor/bin/sail down          # Stop containers
./vendor/bin/sail npm run dev   # Run Vite dev server
./vendor/bin/sail npm run build # Build frontend assets
```

## Frontend Theming

The app supports 9 user-selectable themes (dark, light, rose, ocean, forest, sunset, lavender, sky, sakura). **Never hardcode Tailwind color classes** (e.g. `bg-gray-800`, `text-indigo-300`). Always use the theme utility classes instead.

### Available theme classes

| Category | Classes |
|----------|---------|
| **Backgrounds** | `bg-theme-page`, `bg-theme-card`, `bg-theme-elevated`, `bg-theme-input`, `bg-theme-nav`, `bg-theme-header` |
| **Text** | `text-theme-text-primary`, `text-theme-text-secondary`, `text-theme-text-muted`, `text-theme-text-faint` |
| **Borders** | `border-theme-border`, `border-theme-border-subtle`, `divide-theme-border` |
| **Accent** | `bg-theme-accent`, `bg-theme-accent-hover`, `text-theme-accent-text`, `focus:ring-theme-accent-ring`, `border-theme-accent` |
| **Buttons** | `bg-theme-btn-primary`, `text-theme-btn-primary-text`, `bg-theme-btn-primary-hover`, `bg-theme-btn-secondary`, `text-theme-btn-secondary-text`, `bg-theme-btn-secondary-hover` |
| **Semantic** | `bg-theme-danger`, `bg-theme-danger-hover`, `bg-theme-success`, `bg-theme-warning`, `text-theme-link`, `text-theme-link-hover` |

### Examples

```html
<!-- Good -->
<div class="bg-theme-card border border-theme-border text-theme-text-primary">
<button class="bg-theme-accent hover:bg-theme-accent-hover text-white">

<!-- Bad - hardcoded colors break other themes -->
<div class="bg-gray-800 border border-gray-700 text-white">
<button class="bg-indigo-600 hover:bg-indigo-700 text-white">
```

Theme CSS variables are defined in `resources/css/app.css` and mapped to Tailwind in `tailwind.config.js`.

## Stack

- **Backend:** Laravel 11 + PHP 8.3
- **Frontend:** Vue 3 + Inertia.js + Tailwind CSS
- **Database:** MySQL (via Sail)
