# FTS AI Website

FTS's own company website, rebuilt as an **AI Website** — a live, working example of the service FTS sells. An AI Staff member ("Aya") works inside the site: introduces FTS, explains services, shows real projects, answers questions from the approved knowledge base, and records consultation / demo / quotation requests for the team.

Same architecture as `fts-hotel-ai` (Hotel AI Website V1):

- **Self-hosted LLM** (LM Studio / Ollama, OpenAI-compatible) with tool calling — `app/Services/AiStaff/AiStaffService.php`
- **Tools = the only source of truth** — `app/Services/AiStaff/CompanyStaffTools.php`
  `search_knowledge`, `list_services`, `get_service_detail`, `show_projects`, `get_project_detail`, `create_lead`, `request_human_handover`
- **Animated AI Staff character** (`resources/views/components/staff-character.blade.php`, pure SVG + CSS): states idle / listening / thinking / talking / happy / handover, driven by chat events in `ai-staff.js` via `data-state`
- **Voice** (opt-in speaker button in the panel; browser `speechSynthesis`, ID/EN/JA voice picked per locale) and a **proactive greeting** once per session after ~20 s + 8 s idle, worded for the section in view — both in `ai-staff.js`
- **AI Staff panel** that renders tool results as cards (services, pricing tiers, project screenshots, lead confirmation) — `resources/js/ai-staff.js`
- **Admin** (`/admin`): dashboard, leads, handovers (reply to visitors live), services, projects, knowledge base, company profile
- Public page sections (all data-driven from the admin): hero with stats, services, capability band with demo chat, filterable portfolio, pricing tiers, about / process / team / values, FAQ, CTA, footer. Knowledge categories `team` (body: first line = role) and `values` feed the team cards and values band.
- ID / EN / JA throughout
- **Theme matched to the main site** (fts-tech.co.id): Inter, primary blue `hsl(203 89% 53%)` with the blue gradient, 0.75rem radius, dark starfield hero, white / muted alternating sections, pill buttons. Tokens live at the top of `resources/css/app.css`. Logo and favicon are the official files from fts-tech.co.id (`public/images/logo-fts.webp`, `public/favicon.ico`).

## Setup

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve --port=8010
```

`.env` needs the model endpoint:

```
LOCAL_LLM_BASE_URL=http://<lm-studio-host>:1234
LOCAL_LLM_MODEL=<model id>
LOCAL_LLM_API_KEY=
# optional: which company this instance serves (defaults to the first published one)
FTS_COMPANY_SLUG=fts
```

Seeded admin: `admin@fts-tech.test` / `password` — change it before deploying.

Test the tool loop without the browser: `php artisan ai-staff:chat --locale=en`

## Before going live

Fill in under **Admin → Company profile**: address, email, WhatsApp, phone (left empty on purpose so the AI never invents them). Review the seeded services, projects and knowledge base — the AI answers only from that content.

## Tests

```bash
php artisan test
```
