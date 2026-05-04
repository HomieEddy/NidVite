# ADR-010: Bilingual Support (EN/FR) for Quebec Compliance

## Status
Accepted

## Context

NidVite operates in Montreal, Quebec — a bilingual province where French is the official language (Law 101 / Charter of the French Language). While private businesses can operate in English, providing bilingual support is essential for:

1. **User inclusivity**: Serving both English and French-speaking citizens
2. **Accessibility**: Screen readers require proper `lang` attributes
3. **Legal prudence**: Demonstrating good-faith effort toward French accessibility
4. **Market reach**: Maximum adoption across Montreal's diverse population

## Decision

Implement bilingual support using **Laravel's built-in localization** (`__()`, `trans()`) with JSON translation files. No additional i18n packages required.

### Language Policy

| Aspect | Decision |
|--------|----------|
| **Default language** | French (`fr`) |
| **Fallback language** | French (`fr`) |
| **Available languages** | English (`en`), French (`fr`) |
| **Language detection** | 1. User explicit selection (cookie), 2. Browser `Accept-Language`, 3. Default `fr` |
| **Email language** | Single-language based on reporter's preference; `fr` fallback |
| **Dashboard language** | Per-user preference stored in `users.locale`; `fr` fallback |

### Legal Context

- **Law 101**: Requires French as the primary language for government services. NidVite is a private business, but French-first demonstrates compliance spirit.
- **GDPR/Accessibility**: Proper `lang` attributes and localized content improve screen reader compatibility.

## Implementation

### File Structure

```
lang/
├── en.json              # PWA + email + general strings
├── fr.json              # French translations
├── en/
│   └── validation.php   # English validation messages
└── fr/
    └── validation.php   # French validation messages
```

### Database Changes

Dynamic content tables require bilingual columns:

| Table | New Fields |
|-------|-----------|
| `expense_categories` | `label_en`, `label_fr` |
| `notifications` | `title_en`, `title_fr`, `message_en`, `message_fr` |
| `email_deliveries` | `subject_en`, `subject_fr`, `body_en`, `body_fr` |
| `reports` | `preferred_locale` (VARCHAR(5), default 'fr') |
| `users` | `locale` (VARCHAR(5), default 'fr') |

### Language Switcher

**PWA (Citizen-facing):**
- Globe icon (`🌐`) in top-right corner
- Dropdown: Français / English
- Persists in `localStorage` + cookie (`nidvite_locale`)
- Emits `locale-changed` event for Livewire components

**Dashboard (Filament):**
- User profile dropdown: Language preference
- Stored in `users.locale`
- Applied on login via `AppServiceProvider`

### Email Language Resolution

```php
// When sending email
$locale = $report->preferred_locale ?? 'fr';
app()->setLocale($locale);

Mail::to($report->reporter_email)
    ->locale($locale)
    ->send(new ReportCompleted($report));
```

## Consequences

- **Positive**: Serves 100% of Montreal population
- **Positive**: No package dependencies (Laravel native)
- **Positive**: Easy to add Spanish, Mandarin, etc. later
- **Negative**: All new UI text requires translation (development overhead)
- **Negative**: CI must enforce translation completeness

## Related Decisions

- TRANSLATION_GUIDE.md: Strict rules for developers
- CODING_MANIFESTO.md: Translation compliance in code standards
- DEFINITION_OF_DONE.md: Translation checklist item
