# Pendências e loop de execução — Prescia

**Data da auditoria:** 2026-09-17
**Commit auditado:** `e7740b6` (`master`)
**Skills instaladas:** `phpstan-legacy-remediation` e `prescia-install-config`
**Estado das skills:** ambas validadas pelo `quick_validate.py`; as cópias instaladas em `/home/ubuntu/skills/` são idênticas às versões versionadas.

> **Atualização de estado — 2026-09-17:** a fotografia foi atualizada para `e7740b6`. O item 4 continua sendo o primeiro item não concluído: a análise administrativa anterior não encontrou chamadas DBO legadas executáveis, mas a validação PHP/Composer/PHPUnit/PHPStan continua bloqueada pela ausência do toolchain local. As PRs abertas #227 e #228 permanecem conflitantes e não foram alteradas. O ciclo está registrado em `docs/RELATORIO_CICLO_2026-09-17.md`; não houve alteração de código, baseline, issue ou PR.

## Estado atual

A árvore de trabalho local estava limpa e o `master` local estava alinhado a `origin/master`. A consulta ao GitHub não encontrou issues abertas no momento da auditoria. Portanto, as referências a issues nos planos antigos são **histórico de governança**, não evidência de que existam tickets abertos atualmente.

O PHPStan nível 1 aparece como zerado no histórico documentado, sem expansão da baseline. Os achados SEC-001, SEC-002 e SEC-003 da auditoria de 2026-09-13 já estão corrigidos no `master` e cobertos por `SecurityRegressionTest`; SEC-004 e SEC-006 foram analisados e permanecem protegidos por configuração/deployment. Não foi possível repetir PHP, Composer, PHPUnit, PHPStan ou Docker neste sandbox porque esses executáveis não estão instalados; a validação executável deve ser feita no CI ou em ambiente PHP 8.3.

## Backlog priorizado

| Ordem | Pendência | Local principal | Prioridade | Dependência | Critério de aceite |
|---:|---|---|---|---|---|
| 1 | Parametrizar os filtros SQL livres das APIs `getTags()` e `getArchieveDates()` | `prescia/plugins/bi_bb/module.php` | **Concluído** | Filtros estruturados sem call sites externos no repositório | Valores vinculados com tipos explícitos; regressões e evidência registradas no relatório de ciclo |
| 2 | Migrar a consulta principal do endpoint de thread | `prescia/plugins/bi_bb/payload/content/thread.php` e caminho `runContent()` | **Concluído** | Contrato de SQL-array confirmado no lote | Consulta preparada, paginação preservada e regressão contra concatenação antiga |
| 3 | Parametrizar os filtros da árvore parental em `getContents()` | `prescia/components/module.php` e consumidores administrativos | **Concluído** | Transporte de tipos/parâmetros compatível com o legado | Valores vinculados; identificadores estruturais continuam derivados de metadados |
| 4 | Fazer varredura de todos os fluxos administrativos ainda não cobertos pelos lotes SQL registrados | `prescia/plugins/bi_adm/**`, `prescia/components/**` | P1 | Concluir os três lotes P0 ou executar em paralelo por componente isolado | **Analisado em 2026-09-15:** não há chamadas DBO legadas executáveis no escopo; falta validação executável local e revisão contínua dos diagnósticos PHPStan nível 4 |
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

## Estado do ciclo 2026-09-14

O repositório foi clonado em `/home/ubuntu/Prescia` e a fotografia original não possuía alterações de código pendentes. As skills versionadas foram instaladas e validadas. A revisão dos achados da auditoria confirmou que os três itens de segurança acionáveis já estão no `master`, com regressões em `tests/SecurityRegressionTest.php`; portanto, não foi criado um commit artificial de correção. A atualização documental do README foi publicada posteriormente na PR #210. A suíte local continua bloqueada pela ausência de PHP 8.3, Composer, PHPUnit, PHPStan e Docker; o CI remoto é a fonte de validação executável.

## Estado do ciclo 2026-09-15

No commit `9e581b7`, foi feita a varredura dos 53 arquivos PHP de `prescia/plugins/bi_adm` e dos fluxos compartilhados em `prescia/components`. Não foram encontradas chamadas DBO executáveis a `query()`, `fetch()` ou `simpleQuery()`; as ocorrências `fetch()` restantes carregam templates e a ocorrência de `simpleQuery()` está comentada. Os SQLs administrativos examinados usam `queryPrepared()`/`fetchPrepared()`, com valores externos em placeholders e identificadores derivados de metadados. Os testes estáticos existentes em `tests/SecurityRegressionTest.php` já cobrem os principais contratos preparados. O item foi analisado, mas não marcado como concluído porque PHP, Composer, PHPUnit, PHPStan e Docker não estão disponíveis localmente. A documentação foi publicada na PR #230, mesclada no SHA `8d09991552f0c8e44da880f8bbd3eeb2e468fc54`; os workflows pós-merge `34935479495` e `34935479688` concluíram com `completed/success`. Evidência detalhada: `docs/RELATORIO_CICLO_2026-09-15.md`.
No commit `9e581b7`, foi feita a varredura dos 53 arquivos PHP de `prescia/plugins/bi_adm` e dos fluxos compartilhados em `prescia/components`. Não foram encontradas chamadas DBO executáveis a `query()`, `fetch()` ou `simpleQuery()`; as ocorrências `fetch()` restantes carregam templates e a ocorrência de `simpleQuery()` está comentada. Os SQLs administrativos examinados usam `queryPrepared()`/`fetchPrepared()`, com valores externos em placeholders e identificadores derivados de metadados. Os testes estáticos existentes em `tests/SecurityRegressionTest.php` já cobrem os principais contratos preparados. O item foi analisado, mas não marcado como concluído porque PHP, Composer, PHPUnit, PHPStan e Docker não estão disponíveis localmente. A documentação foi publicada na PR #230, mesclada no SHA `8d09991552f0c8e44da880f8bbd3eeb2e468fc54`; os workflows pós-merge `34935479495` e `34935479688` concluíram com `completed/success`. Evidência detalhada: `docs/RELATORIO_CICLO_2026-09-15.md`.

## Estado do ciclo 2026-09-16

O repositório foi sincronizado em `master` no commit `20ae614`. O primeiro item não concluído continua sendo o item 4, mas não há ambiente local para confirmar runtime, lint, PHPUnit ou PHPStan: `php`, `composer`, `vendor/bin/phpunit` e `vendor/bin/phpstan` estão ausentes; Docker também não está disponível no shell efetivo. As PRs abertas #227 e #228 foram consultadas e estão `mergeable=CONFLICTING`/`mergeStateStatus=DIRTY`, portanto não foram misturadas ao ciclo. Não houve alteração de PHP, testes funcionais, baseline ou criação de issue. O registro foi publicado na PR #232, que recebeu 9 checks `completed/success` e foi mesclada; o SHA resultante de `master` é `999a08e0a20df2ffbb27e0bdedcecc09e02157c0`, e o workflow pós-merge #35062355570 concluiu com `success`, incluindo PHP 8.3/8.4/8.5, PHPStan e build/scan Docker no job PHP 8.3. O ciclo permanece bloqueado para correção de código e está documentado em `docs/RELATORIO_CICLO_2026-09-16.md`.

## Primeiro ciclo recomendado

Começar pelo item 1, `getTags()`/`getArchieveDates()`, somente após confirmar se essas APIs possuem consumidores fora deste repositório. Se não houver consumidores, preservar compatibilidade com um adaptador que converta filtros permitidos em estrutura tipada ou marcar a API como legado sem call sites e avançar para o item 2. O item 2 é o próximo alvo de maior risco no código executado pelo endpoint de thread.

## Evidências desta auditoria

- Skill versionada: `skills/phpstan-legacy-remediation/SKILL.md`.
- Relatório de progresso PHPStan: `docs/RELATORIO_PHPSTAN_PROGRESSO.md`.
- Plano do próximo lote: `docs/PLANO_PHPSTAN_PROXIMO_LOTE.md`.
- Plano de segurança: `docs/PLANO_ACAO_SEGURANCA.md`.
- CI principal: `.github/workflows/php83.yml`.
- Relatório do ciclo atual: `docs/RELATORIO_CICLO_2026-09-17.md`.
- APIs livres identificadas por busca no commit auditado: `getTags()`, `getArchieveDates()` e `getContents()`.

> Este documento é um backlog operacional. Cada ciclo deve atualizar o status e anexar a evidência antes de declarar a pendência concluída.
