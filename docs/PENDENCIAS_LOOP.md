# Pendências e loop de execução — Prescia

**Data da auditoria:** 2026-09-08
**Commit auditado:** `659cdc6` (`master`)
**Skill instalada:** `phpstan-legacy-remediation`
**Estado da skill:** validada pelo `quick_validate.py`; cópia instalada em `/home/ubuntu/skills/phpstan-legacy-remediation/SKILL.md`, idêntica à versão versionada em `skills/phpstan-legacy-remediation/SKILL.md`.

## Estado atual

A árvore de trabalho local estava limpa e o `master` local estava alinhado a `origin/master`. A consulta ao GitHub não encontrou issues abertas no momento da auditoria. Portanto, as referências a issues nos planos antigos são **histórico de governança**, não evidência de que existam tickets abertos atualmente.

O PHPStan nível 1 aparece como zerado no histórico documentado, sem expansão da baseline. A última validação remota registrada para os lotes recentes de segurança foi verde. Não foi possível repetir PHP, Composer, PHPUnit, PHPStan ou Docker neste sandbox porque esses executáveis não estão instalados; a validação local deve ser feita no CI ou em ambiente PHP 8.3.

## Backlog priorizado

| Ordem | Pendência | Local principal | Prioridade | Dependência | Critério de aceite |
|---:|---|---|---|---|---|
| 1 | Parametrizar os filtros SQL livres das APIs `getTags()` e `getArchieveDates()`; hoje o contrato aceita uma cláusula `WHERE` livre | `prescia/plugins/bi_bb/module.php:188`, `:217` | P0 | Definir uma API de filtros estruturados e confirmar se há consumidores externos | Valores são parâmetros tipados; tabela/coluna permanecem internas; regressões cobrem aspas, unicode, nulos e entradas inválidas |
| 2 | Migrar a consulta principal do endpoint de thread, separando SQL estrutural de parâmetros | `prescia/plugins/bi_bb/payload/content/thread.php` e caminho `runContent()` | P0 | Confirmar contrato completo de `SQL-array`, paginação e retorno | Consulta principal usa `queryPrepared()`/`fetchPrepared()`; paginação preservada; regressão impede concatenação de `idf`, `idt`, filtros e limites |
| 3 | Parametrizar os filtros da árvore parental em `getContents()` | `prescia/components/module.php:198`; consumidores em `bi_adm/payload/content/edit.php` e `options.php` | P0 | Criar transporte de tipos/parâmetros sem quebrar o formato legado | Valores do filtro são vinculados; nomes de tabela/coluna vêm de metadados; árvore simples e parental continuam funcionando |
| 4 | Fazer varredura de todos os fluxos administrativos ainda não cobertos pelos lotes SQL registrados | `prescia/plugins/bi_adm/**`, `prescia/components/**` | P1 | Concluir os três lotes P0 ou executar em paralelo por componente isolado | Inventário de entradas externas e chamadas SQL; cada risco vira lote, teste ou justificativa documentada |
| 5 | Revalidar autenticação, CSRF, sessão, upload/CKFinder, serialização, debug/headers e rate limiting contra o estado atual | Planos `docs/PLANO_ACAO_SEGURANCA.md` e testes em `tests/` | P1 | Ambiente PHP 8.3 + MySQL/MariaDB descartável | Cada controle possui teste negativo ou evidência manual; itens não comprovados permanecem pendentes |
| 6 | Repetir a suíte completa em ambiente suportado | `.github/workflows/php83.yml` | P1 | Runner CI ou ambiente local com PHP 8.3, extensões e Docker | `composer validate`, `composer audit`, PHPUnit, lint, PHPStan, build e scan Docker concluídos com `completed/success` |
| 7 | Revisar e atualizar os planos históricos para refletir o estado pós-lotes SQL | `docs/RELATORIO_PHPSTAN_PROGRESSO.md`, `docs/PLANO_ACAO_SEGURANCA.md`, `CHANGELOG.md` | P2 | Cada lote deve produzir evidência de CI | Diferenciar explicitamente corrigido, analisado e pendente; não encerrar uma frente ampla por causa de um lote parcial |

## Loop de execução por lote

Executar um único item delimitado por ciclo. Não misturar remediação PHPStan, migração SQL, autenticação e refatoração funcional no mesmo commit.

1. **Selecionar:** escolher o primeiro item não bloqueado da tabela e limitar o escopo a um arquivo, método ou fluxo relacionado.
2. **Inventariar:** registrar commit-base, call sites, entrada externa, consulta atual, comportamento esperado e teste existente.
3. **Confirmar contexto:** rastrear o carregador real, a classe de `$this`/`$core`, a origem de identificadores estruturais e o contrato de paginação/retorno.
4. **Implementar a menor correção:** preferir `queryPrepared()` ou `fetchPrepared()` com tipos explícitos; nunca transformar entrada externa em identificador SQL; não ampliar a baseline.
5. **Adicionar regressão:** cobrir o contrato seguro e rejeitar a concatenação antiga. Para filtros, testar aspas simples/duplas, unicode, nulos, arrays inesperados e payloads clássicos.
6. **Validar focalizadamente:** executar PHPStan no grupo tratado, `php -l` nos arquivos alterados e `git diff --check`.
7. **Validar globalmente:** executar PHPUnit, PHPStan global, lint completo, auditoria de dependências e build/scan Docker no CI PHP 8.3.
8. **Documentar:** registrar corrigido, analisado e pendente no relatório do ciclo e no changelog quando a mudança for pública.
9. **Publicar:** criar commit pequeno e coerente; acompanhar todos os check-runs do SHA publicado. Não considerar o lote concluído com apenas um job verde.
10. **Decidir o próximo ciclo:** se todos os checks forem `completed/success`, remover o item do backlog e iniciar o próximo; se falhar, abrir um ciclo corretivo do mesmo item antes de avançar.

## Critérios de parada

Parar o loop e registrar bloqueio quando ocorrer qualquer uma destas condições:

- o contrato do SQL legado não puder ser confirmado sem risco funcional;
- a alteração exigir decisão de compatibilidade externa ou mudança de API pública;
- o CI revelar regressão em PHP 8.3, PHPUnit, PHPStan, dependências ou imagem;
- a correção depender de credencial, ambiente de staging ou banco representativo não disponível;
- houver tentação de silenciar o diagnóstico com `mixed`, `@phpstan-ignore` ou nova entrada na baseline;
- a pendência estiver documentada apenas em plano histórico, mas não houver evidência de que ainda existe no código atual.

## Primeiro ciclo recomendado

Começar pelo item 1, `getTags()`/`getArchieveDates()`, somente após confirmar se essas APIs possuem consumidores fora deste repositório. Se não houver consumidores, preservar compatibilidade com um adaptador que converta filtros permitidos em estrutura tipada ou marcar a API como legado sem call sites e avançar para o item 2. O item 2 é o próximo alvo de maior risco no código executado pelo endpoint de thread.

## Evidências desta auditoria

- Skill versionada: `skills/phpstan-legacy-remediation/SKILL.md`.
- Relatório de progresso PHPStan: `docs/RELATORIO_PHPSTAN_PROGRESSO.md`.
- Plano do próximo lote: `docs/PLANO_PHPSTAN_PROXIMO_LOTE.md`.
- Plano de segurança: `docs/PLANO_ACAO_SEGURANCA.md`.
- CI principal: `.github/workflows/php83.yml`.
- APIs livres identificadas por busca no commit auditado: `getTags()`, `getArchieveDates()` e `getContents()`.

> Este documento é um backlog operacional. Cada ciclo deve atualizar o status e anexar a evidência antes de declarar a pendência concluída.
