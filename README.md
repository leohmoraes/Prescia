# Prescia

Framework LAMP full-stack de alto nível.

O Prescia é um framework LAMP (Linux, Apache, MySQL, PHP) avançado, fruto de mais de 10 anos de trabalho de Caio Vianna de Lima Netto. Durante a fase de produção fechada, atendeu mais de 500 sites no mundo. Este repositório é a 4ª iteração do framework, evoluindo de trabalhos anteriores por volta de 2006.

Num cenário repleto de frameworks PHP, o Prescia se destaca pelo alto nível de automação e simplicidade. Ele é voltado a rodar **vários sites na mesma instalação** — é possível atender vários domínios a partir de um único deploy, sem duplicar código. A ideia surgiu de agências que precisavam entregar e manter muitos sites por mês, com o mínimo de redundância.

Com o tempo, foram incorporados recursos de otimização, segurança, simplicidade e facilidade de implementação (alguns depois descontinuados). O código foi refinado para atender muitos clientes na mesma instalação, mantendo-se limpo e robusto o suficiente para outros modelos de deploy.

Por ser um framework de alto nível, o Prescia usa componentes de terceiros onde faz sentido: bibliotecas JavaScript (Prototype, jQuery, CKEditor), frameworks de CSS (Bootstrap) e utilitários como `adodb_time`.

Se você já conhece outros frameworks e quer entender como o Prescia se diferencia, leia a documentação abaixo — em especial a [Referência de Uso](docs/Usage_reference.md) (fluxo de dados e ajustes no MVC). O Prescia é bem diferente dos frameworks comuns; costuma ser amado ou rejeitado de imediato.

**Licença:** [New BSD / BSD-new](prescia/LICENSE) — uso livre.

---

## Documentação

A documentação canônica está em [`docs/`](docs/) em Markdown (convertida das referências em texto original em 2026). Os guias técnicos detalhados estão em **inglês**; este README resume o essencial em português.

| Documento | Descrição |
|---|---|
| [**Usage Reference**](docs/Usage_reference.md) | **Fluxo de dados** da requisição, conteúdo em cache, **plugins**, **templates** (tags de layout, parâmetros do Template Core, tags CMS, callbacks), **metadata XML**, monitor XML, estruturas em árvore, meta tags preenchidas pelo Core e **referência rápida de funções** |
| [**Code Reference**](docs/Code_reference.md) | **Referência completa da API** de Template Core, Core, CoreFull e Module (`components/module.php`) — propriedades, métodos e auxiliares de instalação |
| [**FAQ**](docs/faq.md) | Problemas de CMS, módulos e plugins, depuração de **404** (BI_DEV), páginas de **manutenção** (`maint.txt`, `heavymaint.html`), **EconomicMode** |
| [**Bot Blocklist**](docs/bots.md) | Padrões de user-agent bloqueados com `CONS_BOTPROTECT` ativo; veja o FAQ para desativar via EconomicMode |

### Arquivos de texto legados

Cópias antigas em texto puro permanecem apenas para referência; prefira os arquivos Markdown acima:

- `docs/Usage_refference.txt` → [Usage_reference.md](docs/Usage_reference.md)
- `docs/Code_refference.txt` → [Code_reference.md](docs/Code_reference.md)
- `docs/faq.txt` → [faq.md](docs/faq.md)
- `docs/bots2015.txt` → [bots.md](docs/bots.md)

---

## Requisitos

- PHP 8+ com **mysqli** (driver MySQL)
- Apache com `mod_rewrite` (recomendado)
- MySQL 8+ (ou compatível)

---

## Instalação

### Manual

1. Extraia na pasta desejada (desenvolvido para a raiz do documento; instalação em subpasta é possível com configuração).
2. Renomeie os templates de configuração:
   - `config/domains.original` → `config/domains`
   - `config/settings.php.original` → `config/settings.php`
3. Edite esses arquivos. **Obrigatório:** `CONS_MASTERPASS` e `CONS_MASTERMAIL`. **Sugerido:** `date_default_timezone_set()`, `CONS_HTTPD_ERRFILE`, `CONS_OVERRIDE_DB`, `CONS_OVERRIDE_DBUSER`, `CONS_OVERRIDE_DBPAS`.
4. Garanta que os diretórios graváveis existam (veja `core::checkinstall()` na [Code Reference](docs/Code_reference.md#core)).
5. Aponte o servidor web para `index.php` e acesse o site.

Para o ciclo completo da requisição e onde seu código roda (`actions/`, `content/`, plugins), veja [Usage Reference — Dataflow](docs/Usage_reference.md#1-dataflow).

### Docker

```bash
docker compose up --build
```

- **Web:** http://localhost:8080  
- **MySQL:** `localhost:3306` — banco `prescia`, usuário `prescia_user`, senha `prescia_pass` (veja `docker-compose.yml`)

Na primeira build, a imagem copia `config/domains.original` e `config/settings.php.original` se os arquivos de destino não existirem. Monte o diretório do projeto para desenvolvimento com alterações ao vivo.

---

## Estrutura do projeto (essencial)

| Caminho | Função |
|---|---|
| `index.php` | Front controller |
| `config/` | Configurações globais (`settings.php`) e mapa de domínios (`domains`) |
| `pages/` | Pastas por site (templates, `actions/`, `content/`, `config.php`) |
| `prescia/` | Núcleo do framework, plugins e bibliotecas |
| `_temp/` | Logs, cache e backups (deve ser gravável) |

---

## Solução rápida de problemas

| Sintoma | Consulte |
|---|---|
| CMS ou URL virtual retorna 404 | [FAQ — CMS](docs/faq.md#cms); ative **BI_DEV** para ver o motivo exato |
| Aviso de manutenção ou 503 | [FAQ — Maintenance](docs/faq.md#maintenance-pages) |
| Hooks de plugin não disparam | [FAQ — Modules & Plugins](docs/faq.md#modules--plugins) |
| Crawlers bloqueados (403) | [Bot Blocklist](docs/bots.md), [FAQ — EconomicMode](docs/faq.md#what-does-economicmode-do) |
