# Prescia

Full-stack high-level LAMP framework.

Prescia is an advanced LAMP (Linux, Apache, MySQL, PHP) framework based on over 10 years of work by Caio Vianna de Lima Netto. It served more than 500 sites worldwide during its production and non-open stage. This repository is the 4th iteration of the framework, building on earlier work from around 2006.

On an internet filled with plenty of PHP frameworks, Prescia stands out for its high level of automation and simplicity. It is oriented toward running **multiple sites on the same install** — you can deliver several domains from one deployment without duplicating code. The idea came from agencies that needed to ship and maintain many sites per month with minimal redundancy.

Over time, features for optimization, safety, simplicity, and ease of implementation were added (some later deprecated). The codebase was refined to serve many customers on one install while staying clean and robust enough for other deployment models.

Being a high-level framework, Prescia relies on third-party components where they fit: JavaScript libraries (Prototype, jQuery, CKEditor), CSS frameworks (Bootstrap), and utilities such as `adodb_time`.

If you know other frameworks and want to see how Prescia differs, read the documentation below — especially the [Usage Reference](docs/Usage_reference.md) (dataflow and MVC tweaks). Prescia is quite different from typical frameworks; it tends to be a love-it-or-leave-it choice.

**License:** [New BSD / BSD-new](prescia/LICENSE) — free for use.

---

## Documentation

The canonical documentation lives in [`docs/`](docs/) as Markdown (converted from the original text references in 2026).

| Document | Description |
|---|---|
| [**Usage Reference**](docs/Usage_reference.md) | Request **dataflow**, cached content, **plugins**, **templating** (layout tags, Template Core parameters, CMS tags, callbacks), **metadata XML**, monitor XML, tree structures, meta tags filled by Core, and a **quick function reference** |
| [**Code Reference**](docs/Code_reference.md) | Full **API reference** for Template Core, Core, CoreFull, and Module (`components/module.php`) — properties, methods, install helpers |
| [**FAQ**](docs/faq.md) | CMS issues, modules & plugins, **404** debugging (BI_DEV), **maintenance** pages (`maint.txt`, `heavymaint.html`), **EconomicMode** |
| [**Bot Blocklist**](docs/bots.md) | User-agent patterns blocked when `CONS_BOTPROTECT` is enabled; see FAQ for disabling via EconomicMode |

### Legacy text files

Older plain-text copies remain for reference only; prefer the Markdown files above:

- `docs/Usage_refference.txt` → [Usage_reference.md](docs/Usage_reference.md)
- `docs/Code_refference.txt` → [Code_reference.md](docs/Code_reference.md)
- `docs/faq.txt` → [faq.md](docs/faq.md)
- `docs/bots2015.txt` → [bots.md](docs/bots.md)

---

## Requirements

- PHP 8+ with **mysqli** (MySQL driver)
- Apache with `mod_rewrite` (recommended)
- MySQL 8+ (or compatible)

---

## Installation

### Manual

1. Unzip into the desired folder (developed for document root; subfolder installs are possible with configuration).
2. Rename configuration templates:
   - `config/domains.original` → `config/domains`
   - `config/settings.php.original` → `config/settings.php`
3. Edit those files. **Required:** `CONS_MASTERPASS` and `CONS_MASTERMAIL`. **Suggested:** `date_default_timezone_set()`, `CONS_HTTPD_ERRFILE`, `CONS_OVERRIDE_DB`, `CONS_OVERRIDE_DBUSER`, `CONS_OVERRIDE_DBPAS`.
4. Ensure writable directories exist (see `core::checkinstall()` in the [Code Reference](docs/Code_reference.md#core)).
5. Point the web server at `index.php` and open the site.

For the full request lifecycle and where your code runs (`actions/`, `content/`, plugins), see [Usage Reference — Dataflow](docs/Usage_reference.md#1-dataflow).

### Docker

```bash
docker compose up --build
```

- **Web:** http://localhost:8080  
- **MySQL:** `localhost:3306` — database `prescia`, user `prescia_user`, password `prescia_pass` (see `docker-compose.yml`)

On first build, the image copies `config/domains.original` and `config/settings.php.original` if the target files are missing. Mount the project directory for live development.

---

## Project layout (essentials)

| Path | Role |
|---|---|
| `index.php` | Front controller |
| `config/` | Global settings (`settings.php`) and domain map (`domains`) |
| `pages/` | Per-site folders (templates, `actions/`, `content/`, `config.php`) |
| `prescia/` | Framework core, plugins, libraries |
| `_temp/` | Logs, cache, backups (must be writable) |

---

## Quick troubleshooting

| Symptom | See |
|---|---|
| CMS or virtual URL returns 404 | [FAQ — CMS](docs/faq.md#cms), enable **BI_DEV** for the exact reason |
| Maintenance banner or 503 | [FAQ — Maintenance](docs/faq.md#maintenance-pages) |
| Plugin hooks not firing | [FAQ — Modules & Plugins](docs/faq.md#modules--plugins) |
| Crawlers blocked (403) | [Bot Blocklist](docs/bots.md), [FAQ — EconomicMode](docs/faq.md#what-does-economicmode-do) |
