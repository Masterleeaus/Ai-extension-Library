# Template-Aware Chatbot Shell Pass 1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the shared Titan Zero application shell with template-aware primary navigation, hamburger drawer, gear settings entry point, persistent chat access, and backward compatibility for generic chatbots.

**Architecture:** Add a small PHP navigation resolver that reads template configuration and supplies safe defaults. Render the shell through focused Blade components and drive drawer/settings/view state with the existing Alpine component. Keep WorkCore data, offline sync, and generative UI untouched; this pass changes only navigation and shell presentation.

**Tech Stack:** Laravel/PHP, Blade, Alpine.js, Tailwind utility classes, existing chatbot frontend runtime.

## Global Constraints

- Do not create a separate Assistant, Chat, Settings, or Help primary navigation item.
- Keep the chat input available from every operational view.
- Preserve existing generic chatbot behaviour when no Titan template is selected.
- Do not duplicate Laravel WorkCore business logic in JavaScript.
- Do not remove or rewrite the offline WorkCore runtime.
- Use progressive enhancement and accessible controls.

---

### Task 1: Template navigation resolver

**Files:**
- Create: `System/TitanShell/TemplateNavigation.php`
- Modify: `resources/titan-apps/TitanSuiteTemplates/TitanRegistry.php`
- Test: `tests/Unit/TitanShell/TemplateNavigationTest.php`

**Interfaces:**
- Produces: `TemplateNavigation::resolve(?string $slug): array`
- Produces: `TitanRegistry::getNavigation(string $slug): array`

- [ ] Write a failing unit/static contract test for Titan Go, Titan Hub, and generic fallback navigation.
- [ ] Run the test and verify failure because the resolver does not exist.
- [ ] Implement a resolver with safe defaults and 14 template maps.
- [ ] Add the registry accessor.
- [ ] Run the test and verify it passes.

### Task 2: Shared shell Blade components

**Files:**
- Create: `resources/views/frontend-ui/components/app-shell-navigation.blade.php`
- Create: `resources/views/frontend-ui/components/app-drawer.blade.php`
- Create: `resources/views/frontend-ui/components/settings-panel.blade.php`
- Modify: `resources/views/frontend-ui/components/header.blade.php`
- Modify: `resources/views/frontend-ui/frontend-ui-frontpage.blade.php`
- Modify: `resources/views/frontend-ui/components/footer.blade.php`
- Test: `tests/Feature/TitanShell/TitanShellStaticContractTest.php`

**Interfaces:**
- Consumes: `TemplateNavigation::resolve($templateSlug)`
- Produces: accessible `data-titan-shell`, drawer, settings, and primary-navigation markup.

- [ ] Write a failing static contract test for hamburger, gear, drawer, settings panel, and absence of forbidden primary labels.
- [ ] Run the test and verify failure.
- [ ] Add the three focused Blade components.
- [ ] Update the header to show hamburger, template/page title, sync status, notifications, and gear.
- [ ] Replace branding-only footer with template primary navigation while retaining attribution inside the drawer.
- [ ] Run the test and verify it passes.

### Task 3: Alpine shell state and routing

**Files:**
- Modify: `resources/assets/js/external-chatbot.js`
- Test: `tests/js/titan-shell-runtime.test.js`

**Interfaces:**
- Produces: `openTitanDrawer()`, `closeTitanDrawer()`, `openTitanSettings()`, `closeTitanSettings()`, `navigateTitanView(view)`.

- [ ] Write a failing Node static/runtime test for shell methods and state.
- [ ] Run the test and verify failure.
- [ ] Add shell state and methods without changing existing conversation methods.
- [ ] Add Escape-key and online/offline state handling.
- [ ] Run the test and verify it passes.

### Task 4: Shell styling and responsive behaviour

**Files:**
- Create: `resources/assets/css/titan-app-shell.css`
- Modify: `resources/views/frontend-ui/frontend-ui-frontpage.blade.php`
- Test: `tests/Feature/TitanShell/TitanShellStylesContractTest.php`

**Interfaces:**
- Produces: mobile bottom dock, tablet responsive layout, desktop rail-ready navigation, accessible drawers.

- [ ] Write a failing static style contract test for responsive selectors and reduced-motion support.
- [ ] Run the test and verify failure.
- [ ] Add isolated shell CSS using existing CSS variables.
- [ ] Include the stylesheet in the front page.
- [ ] Run the test and verify it passes.

### Task 5: Documentation and package verification

**Files:**
- Create: `TITAN-TEMPLATE-AWARE-SHELL-PASS1.md`
- Modify: `FILES.sha256`

- [ ] Document scope, template navigation, compatibility, and next passes.
- [ ] Run PHP syntax checks, JavaScript syntax checks, JSON checks, and ZIP integrity verification.
- [ ] Generate a cumulative upgraded ZIP and checksum.
