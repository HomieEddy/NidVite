# Translation Guide & Conventions

This document defines strict rules for implementing bilingual (EN/FR) support in NidVite. All developers must follow these conventions.

---

## Core Principles

1. **French-first**: Default language is `fr`. All features must be fully translated to French before English.
2. **Never hardcode strings**: Every user-facing string must use Laravel's translation system.
3. **Semantic keys**: Use descriptive dot-notation keys, not English text.
4. **Always complete**: Both `lang/en.json` and `lang/fr.json` must have identical key sets.

---

## File Structure

```
lang/
├── en.json              # General strings (PWA, emails, buttons)
├── fr.json              # French translations
├── en/
│   └── validation.php   # Validation error messages
└── fr/
    └── validation.php   # French validation messages
```

**Rules:**
- Use JSON files for general UI strings (`__('key')`)
- Use PHP files for complex pluralization/validation (`trans('validation.required')`)
- Never create `lang/en/messages.php` — stick to JSON for consistency

---

## Naming Conventions

### Key Format

Use **dot-notation** with semantic grouping:

```
{domain}.{component}.{element}
```

Examples:
```json
{
  "report.form.title": "Soumettre un signalement",
  "report.form.email_label": "Votre courriel",
  "report.form.submit_button": "Envoyer",
  "report.status.pending": "En attente",
  "report.status.repaired": "Réparé",
  "email.completed.subject": "Votre signalement a été réparé",
  "email.completed.greeting": "Bonjour,",
  "dashboard.kpi.open_reports": "Signalements ouverts",
  "validation.email.required": "Le courriel est obligatoire"
}
```

### Forbidden Patterns

```json
// ❌ BANNED: English text as key
{
  "Submit a Report": "Soumettre un signalement",
  "Your email": "Votre courriel"
}

// ❌ BANNED: Vague keys
{
  "submit": "Envoyer",
  "title": "Signalement"
}

// ❌ BANNED: Missing namespace
{
  "form_title": "Signalement"
}

// ✅ REQUIRED: Semantic dot-notation
{
  "report.form.title": "Soumettre un signalement",
  "report.form.email_label": "Votre courriel"
}
```

---

## Code Patterns

### Blade / Livewire

```php
// ❌ BANNED
<h1>Submit a Report</h1>
<button>Send</button>

// ✅ REQUIRED
<h1>{{ __('report.form.title') }}</h1>
<button>{{ __('report.form.submit_button') }}</button>
```

### Filament Resources

```php
// ❌ BANNED
TextInput::make('email')->label('Email'),

// ✅ REQUIRED
TextInput::make('email')
    ->label(__('report.form.email_label')),
```

### Validation Messages

```php
// ❌ BANNED
$request->validate([
    'email' => 'required|email'
]);

// ✅ REQUIRED
$request->validate([
    'email' => ['required', 'email']
], [
    'email.required' => __('validation.email.required'),
    'email.email' => __('validation.email.invalid'),
]);
```

### Emails

```php
// ❌ BANNED
Mail::to($user)->send(new ReportMail());

// ✅ REQUIRED
$locale = $report->preferred_locale ?? 'fr';
app()->setLocale($locale);

Mail::to($report->reporter_email)
    ->locale($locale)
    ->send(new ReportCompleted($report));
```

### Dates & Numbers

```php
// ✅ REQUIRED: Localized dates
$date->locale(app()->getLocale())->isoFormat('LL');
// FR: "4 mai 2026" | EN: "May 4, 2026"

// ✅ REQUIRED: Localized numbers
number_format($cost, 2);
// FR: "1 234,56" | EN: "1,234.56"
```

---

## Dynamic Content in Database

Tables with user-facing labels must have `_en` and `_fr` columns:

| Table | Columns | Usage |
|-------|---------|-------|
| `expense_categories` | `label_en`, `label_fr` | Display based on `app()->getLocale()` |
| `notifications` | `title_en`, `title_fr`, `message_en`, `message_fr` | |
| `email_deliveries` | `subject_en`, `subject_fr`, `body_en`, `body_fr` | |
| `report_categories` | `label_en`, `label_fr` | Already exists ✅ |
| `montreal_boundary` | `name_fr` | Already exists ✅ |

**Retrieval pattern:**
```php
$label = $category->{'label_' . app()->getLocale()};
```

---

## Language Switcher Implementation

### PWA (JavaScript)

```javascript
// Store preference
localStorage.setItem('nidvite_locale', 'fr');
document.cookie = 'nidvite_locale=fr;path=/;max-age=31536000';

// Reload with new locale
window.location.href = '/locale/fr';
```

### Laravel Middleware

```php
// app/Http/Middleware/SetLocale.php
public function handle($request, Closure $next)
{
    $locale = $request->cookie('nidvite_locale') 
        ?? $request->header('Accept-Language', 'fr');
    
    $locale = in_array($locale, ['en', 'fr']) ? $locale : 'fr';
    app()->setLocale($locale);
    
    return $next($request);
}
```

### Filament (Admin)

```php
// In AppServiceProvider
Filament::serving(function () {
    $locale = auth()->user()?->locale ?? 'fr';
    app()->setLocale($locale);
});
```

---

## Translation Workflow

### For Developers

1. **Add feature** with French strings first in `lang/fr.json`
2. **Copy keys** to `lang/en.json` with English translations
3. **Use keys** in Blade/Livewire/Filament with `__('key')`
4. **Verify** both files have identical key sets

### CI Enforcement

```bash
# Check translation completeness
php artisan translation:check
```

Custom command to verify:
- All keys in `fr.json` exist in `en.json`
- All keys in `en.json` exist in `fr.json`
- No empty values
- No hardcoded strings in Blade files (regex scan)

### Pre-Commit Checklist

- [ ] All new user-facing strings use `__('key')`
- [ ] Keys added to both `lang/fr.json` and `lang/en.json`
- [ ] French translation is first/default
- [ ] No hardcoded strings in Blade/Livewire/Filament files
- [ ] Dates and numbers use localized formatting
- [ ] Validation messages are translated

---

## Fallback Behavior

```php
// config/app.php
'locale' => 'fr',
'fallback_locale' => 'fr',
```

If a key is missing:
1. Look in current locale (`fr` or `en`)
2. Fall back to `fr`
3. If still missing, return the key name (Laravel default)

**Production:** Log missing translations to Sentry for monitoring.

---

## Examples

### Complete Feature Example

**Task:** Add a "Photo Required" hint to the report form.

**Step 1:** Add to `lang/fr.json`:
```json
{
  "report.form.photo_hint": "Vous pouvez ajouter jusqu'à 3 photos"
}
```

**Step 2:** Add to `lang/en.json`:
```json
{
  "report.form.photo_hint": "You can add up to 3 photos"
}
```

**Step 3:** Use in Blade:
```blade
<p class="text-sm text-gray-500">{{ __('report.form.photo_hint') }}</p>
```

**Step 4:** Verify CI passes translation check.

---

## Common Translations (Reference)

| Key | FR | EN |
|-----|-----|-----|
| `common.submit` | Envoyer | Submit |
| `common.cancel` | Annuler | Cancel |
| `common.save` | Enregistrer | Save |
| `common.delete` | Supprimer | Delete |
| `common.edit` | Modifier | Edit |
| `common.loading` | Chargement... | Loading... |
| `common.error` | Une erreur est survenue | An error occurred |
| `common.success` | Succès | Success |

---

*This guide is mandatory reading for all NidVite developers. Propose changes via PR.*
