# Code Quality & Style Manifesto

## The TDD Workflow: Red-Green-Refactor

Every feature in **Nid-Vite** begins with a test. We use **Pest** for its expressive, minimalist syntax.

1. **RED**: Write a failing test for the requirement (e.g., a guest cannot submit a report outside Montreal).
2. **GREEN**: Write the minimal code necessary to pass the test.
3. **REFACTOR**: Clean up the code while keeping the tests green.

---

## Principles

### SOLID

- **Single Responsibility**: Keep Livewire components focused on UI state; move business logic (like PostGIS queries) into Action classes.
- **Open/Closed**: Use Laravel's service container to swap implementations (e.g., swapping Resend for another mailer) without changing the core logic.

### DRY (Don't Repeat Yourself)

If a UI element appears twice, make it a MaryUI or Blade component. If logic appears twice, move it to a Trait or Action.

### KISS (Keep It Simple, Stupid)

Prefer a readable `foreach` loop over a complex collection pipe if it makes the intent clearer to your "future self."

### YAGNI (You Ain't Gonna Need It)

Do not build a "Multi-organization Permission System" if we only have one entrepreneur today. Focus on the PWA and the Dashboard.

---

## Coding Style & Linting

### PHP

Follow **PER Coding Style** (the successor to PSR-12). Use **Laravel Pint** with the `laravel` preset for automated formatting.

```bash
./vendor/bin/pint
```

### Frontend

- Use **Prettier** for Alpine.js and Blade formatting.
- Use the **Tailwind CSS IntelliSense** plugin to ensure consistent class ordering.

### Strict Typing

Always use scalar type hints and return types in PHP methods to catch bugs early.

```php
public function findNearby(Point $location, float $radiusKm): Collection
```

---

## Pre-Commit Checklist

Before committing code, ensure:

- [ ] Pest tests pass (`./vendor/bin/pest`)
- [ ] Laravel Pint returns zero formatting errors
- [ ] PHPStan passes at Level 5+
- [ ] All new code has corresponding tests
- [ ] Type hints and return types are present
- [ ] **All user-facing strings are translated** (`__('key')` in Blade/Livewire/Filament)
- [ ] **Both `lang/fr.json` and `lang/en.json` updated** with identical keys
- [ ] **French translation is first/default** (FR is primary, EN is secondary)

---

## Translation Standards

### French-First Policy
- Default language: **French (`fr`)**
- Fallback language: **French (`fr`)**
- All user-facing strings must be translated to **both** French and English
- French translation is always the primary/default

### Key Conventions
- Use **semantic dot-notation** keys: `domain.component.element`
- Example: `report.form.title`, `email.completed.subject`
- **Never** use English text as the key
- **Never** hardcode strings in Blade, Livewire, or Filament

### Dynamic Content
- Database tables with user-facing labels must have `_fr` and `_en` columns
- Examples: `expense_categories.label_fr`, `notifications.title_fr`

### Localization
- Dates: Use `->locale(app()->getLocale())->isoFormat('LL')`
- Numbers: Use `number_format()` with locale awareness
- Emails: Send in user's preferred language (`reports.preferred_locale`)

---

*This manifesto is a living document. Propose changes via PR with rationale.*
