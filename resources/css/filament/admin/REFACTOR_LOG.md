# Theme CSS Refactor Log

**Date:** 2026-04-02  
**Scope:** `resources/css/filament/admin/theme.css` → partial split  
**Original file:** 2,291 lines / 58,799 bytes

---

## Codebase References to `theme.css`

| File | Line | Reference |
|------|------|-----------|
| `app/Providers/Filament/AdminPanelProvider.php` | 100 | `->viteTheme('resources/css/filament/admin/theme.css')` |
| `vite.config.js` | 11 | `'resources/css/filament/admin/theme.css'` (Vite input entry) |

No Blade file references were found. No other config files reference `theme.css`.

> **None of these files were modified.** Vite resolves `@import` partials at build time, so the Panel Provider and Vite config references remain valid as-is.

---

## Partials Created

All partials live in `resources/css/filament/admin/` alongside `theme.css`.

| Partial | Original Lines | Description |
|---------|---------------|-------------|
| `_topbar.css` | 6–18 | Meta comment block + top bar background/border |
| `_sidebar.css` | 20–198 | Sidebar shell, scrollbar hiding, header, nav container, nav items, active/hover states, group labels, footer, theme switcher overrides |
| `_page-content.css` | 200–238 | Page headings, stat overview cards, primary buttons, badges, alternating table rows |
| `_curriculum-map.css` | 240–601 | Timeline roadmap: spine, phases, nodes, phase headers/titles, course grid, compact cards (header, code pill, units, actions, name, tags, specialization sub-header, notes), mobile responsive grid |
| `_program-overview.css` | 603–1003 | Program overview card, stats pill strip, stats pill interactions/editor, requirements row (collapsible toggle, list, badges), min-units badge (inline-editable), requirements grid (numbered cards) |
| `_modals.css` | 1005–1217 | Add program button, overlay, modal (header, body, fields, inputs, footer, cancel/save buttons) |
| `_view-mode.css` | 1219–1263 | View mode toggle container and buttons (active state) |
| `_conditional-groups.css` | 1265–1317 | MS-conditional visual grouping (group wrapper, banner, courses grid, mobile responsive) |
| `_curriculum-table.css` | 1319–1446 | Curriculum table view (thead, group rows, data rows, hover states, MS-conditional row variant, action buttons, MS-conditional tag) |
| `_add-course.css` | 1448–1633 | Add course card (large), dropdown modal (search, list, options), centered variant |
| `_responsive.css` | 1635–1722 | Responsive media queries: small-screen timeline adjustments (≤640px), large-screen scale-up (≥1280px), extra-large scale-up (≥1536px) |
| `_choice-groups.css` | 1724–1845 | Choice group OR pairing (wrapper, compact card overrides, divider, divider text/lines, label, mobile responsive) |
| `_course-type-selector.css` | 1847–1914 | Course type filter selector inside add-course dropdown (selector container, label, buttons, active state) |
| `_dark-mode.css` | 1916–1955 | Dark mode enhancement overrides: timeline node glow, compact card contrast, phase header visibility, code pill brightness, timeline connector opacity, requirements toggle contrast |
| `_program-selector.css` | 1957–2106 | Program selector button, code badge, name, level badge, units, dropdown (search, options, option code/name/level) |
| `_utilities.css` | 2108–2240 | PDF export button, editable requirements (action buttons, edit input, save/cancel buttons, add requirement button), total stats divider |
| `_print.css` | 2242–2291 | Print stylesheet: hides UI chrome, full-width content, removes shadows, forces color printing |

---

## Updated `theme.css` Structure

```css
@import '../../../../vendor/filament/filament/resources/css/theme.css';

@source '../../../../app/Filament/**/*.php';
@source '../../../../resources/views/**/*.blade.php';

/* ── Partials (cascade order preserved) ── */
@import './_topbar.css';
@import './_sidebar.css';
@import './_page-content.css';
@import './_curriculum-map.css';
@import './_program-overview.css';
@import './_modals.css';
@import './_view-mode.css';
@import './_conditional-groups.css';
@import './_curriculum-table.css';
@import './_add-course.css';
@import './_responsive.css';
@import './_choice-groups.css';
@import './_course-type-selector.css';
@import './_dark-mode.css';
@import './_program-selector.css';
@import './_utilities.css';
@import './_print.css';
```

---

## Notes

- **No styles were modified** — only relocated into partials.
- **Cascade order is preserved** — partial imports follow the exact original line order.
- All `!important` flags, selectors, and property values are identical to the original.
- The misplaced `/* TABLES */` comment header (originally at line 146, preceding sidebar group label colors) was renamed to `/* GROUP LABEL COLORS (light mode) */` inside `_sidebar.css` to accurately reflect the styles it precedes.
