# Relatório geral de status das issues abertas

**Repositório:** [leohmoraes/Prescia](https://github.com/leohmoraes/Prescia)  
**Data do levantamento:** 2026-09-08 22:34 (America/Sao_Paulo)  
**Branch de referência:** `master`  
**HEAD:** `411732316ed9fb2e80a409e65945775cf21f0bf1`  
**PRs abertas no momento do levantamento:** nenhuma

## Resumo executivo

O repositório está limpo e o `master` contém cinco lotes recentes de hardening SQL, nas PRs #121 a #125. O CI pós-merge do lote mais recente está sendo acompanhado separadamente. Há **15 issues abertas**. As issues de segurança SQL (#9, #27, #33, #34 e #94) já receberam implementação parcial relevante, mas continuam abertas porque os escopos são mais amplos que os lotes mergeados. A próxima frente prioritária é `bi_stats/module.php`, onde ainda existem consultas concatenadas com dados de sessão, datas, navegador, página e identificadores.

Nenhuma nova issue foi criada neste levantamento: as pendências encontradas estão cobertas pelas issues abertas existentes, principalmente #9, #34 e #94. O loop deve criar uma nova issue somente quando surgir um escopo independente, verificável e não coberto por estas issues.

## Status por issue

| Issue | Tema | Prioridade | Status atual | Próximo passo do loop |
|---:|---|---|---|---|
| #94 | Migrar SQL restante do módulo genérico | Alta | **Em execução parcial**; `getContents()`, backup e notificações foram migrados nas PRs #123–#125. | Auditar consumidores genéricos restantes e separar lotes pequenos com regressão. |
| #67 | Revisão dos 114 PHP legados do CKFinder | Não classificada | **Pendente; não iniciada neste ciclo.** | Inventariar por arquivo, priorizar entrada externa e dividir em lotes revisáveis. |
| #66 | Monitoramento de tentativas de SSRF em runtime | Não classificada | **Pendente; não iniciada neste ciclo.** | Definir pontos de observabilidade, eventos e política de retenção sem expor dados sensíveis. |
| #65 | Testes e mitigações contra DNS rebinding em `loadURL` | Não classificada | **Pendente; não iniciada neste ciclo.** | Auditar resolução, validação de IP e revalidação no momento da conexão. |
| #45 | Variáveis indefinidas do PHPStan | Alta | **Pendente; baseline/diagnósticos ainda não loteados.** | Recalcular inventário após os lotes atuais e corrigir por arquivo, sem alterar baseline indevidamente. |
| #44 | Variáveis indefinidas do PHPStan | Alta | **Pendente; provável sobreposição com #45.** | Confirmar escopo e consolidar ou fechar como duplicada somente após verificar o corpo da issue. |
| #43 | Atualizar PHPStan e elevar nível gradualmente | Alta | **Pendente; CI atual passa no nível existente.** | Planejar elevação incremental após reduzir diagnósticos legados e fixar critérios de aceite. |
| #42 | Encapsular funções globais de sanitização e arquivos | Não classificada | **Pendente; não iniciada neste ciclo.** | Inventariar funções e consumidores, começando por superfícies com entrada externa. |
| #38 | Tipar contratos dos drivers de banco e variáveis de saída | Alta | **Pendente; não iniciada neste ciclo.** | Mapear assinaturas e retornos dos drivers, depois adicionar tipos compatíveis gradualmente. |
| #34 | Testes de regressão para SQL parametrizado | Média | **Em execução parcial**; várias regressões foram adicionadas em `SecurityRegressionTest.php`. | Expandir cobertura para `bi_stats`, `bi_adm` e demais consumidores ainda legados. |
| #33 | Atualizar consumidores de `getKeys` | Alta | **Em execução parcial**; consumidores críticos de `bi_auth` e notificações foram migrados. | Mapear consumidores restantes e converter somente os que transportam valores externos. |
| #27 | Refatorar `getKeys` para WHERE parametrizado | Alta | **Em execução parcial**; APIs preparadas foram adicionadas e usadas em lotes recentes. | Completar adoção pelos consumidores e preservar compatibilidade da API legada. |
| #25 | Container PHP 8.3, short tags e CI de compatibilidade | Não classificada | **Pendente; CI PHP 8.3 existente, container/short tags ainda não concluídos.** | Verificar configuração de imagem, short tags e matriz de versões antes de alterar o pipeline. |
| #24 | Sanitização HTML contextual e biblioteca segura | Não classificada | **Pendente; não iniciada neste ciclo.** | Inventariar `cleanHTML`/`stripHTML`, definir allowlist e criar testes XSS antes da substituição. |
| #9 | Substituir SQL concatenado por prepared statements | Alta | **Em execução parcial**; múltiplos módulos foram corrigidos, mas o inventário ainda aponta `bi_stats` e outros plugins. | Priorizar `bi_stats`, depois continuar por risco e presença de entrada externa. |

## Lotes concluídos neste ciclo

| PR | Escopo | SHA mergeado |
|---:|---|---|
| #121 | `bi_auth`: grupo guest e ownership direto | `972d7384deee7f9259cfe18af9d1d055a93b23b0` |
| #122 | `bi_auth`: ownership remoto | `7c2e290398609ba67a1261673957997b2b3f7b8e` |
| #123 | Notificações genéricas com `getPreparedKeys()` | `83581528d5f9d883f25ff49ee380efb9298c23e4` |
| #124 | Backup genérico com SQL-array e `queryPrepared()` | `cbfbe603ad182eab01cf01fa3b5d654d0cf99aad` |
| #125 | `getContents()` no caminho preparado | `411732316ed9fb2e80a409e65945775cf21f0bf1` |

## Ordem operacional do loop

1. Confirmar checks pós-merge da PR #125.
2. Auditar e parametrizar o primeiro lote de `bi_stats/module.php`, começando por hits e estatísticas de navegador.
3. Adicionar regressões estáticas e, quando viável, testes de comportamento para aspas, Unicode e payloads de injeção.
4. Publicar uma PR pequena, aguardar CI verde, fazer merge autorizado e atualizar este relatório.
5. Repetir a auditoria para `bi_stats` diário, `bi_seo`, `bi_fm`, `bi_dev`, `bi_cms` e fluxos administrativos, sempre separando lotes por risco.
6. Recalcular o inventário PHPStan e iniciar os lotes #38, #43, #44 e #45 somente após estabelecer a sobreposição entre eles.
7. Tratar SSRF/DNS rebinding (#65 e #66), CKFinder (#67) e sanitização HTML (#24) como frentes independentes, criando uma nova issue apenas se o escopo não estiver coberto por nenhuma issue existente.

## Critérios para criação de nova issue

Uma nova issue deverá ser criada somente se a auditoria encontrar uma vulnerabilidade ou tarefa independente que não esteja coberta por #9, #24, #27, #33, #34, #38, #42, #43, #44, #45, #65, #66, #67 ou #94. A issue deve conter arquivo/linha ou superfície afetada, impacto, critérios de aceite, testes esperados e prioridade.

## Referências

- [Issues abertas](https://github.com/leohmoraes/Prescia/issues?q=is%3Aissue+is%3Aopen)
- [Pull requests](https://github.com/leohmoraes/Prescia/pulls)
- [Relatório do ciclo](./RELATORIO_CICLO_2026-09-08.md)
- [Pendências e loop](./PENDENCIAS_LOOP.md)

## Atualização do loop — 2026-09-08 22:35

O relatório foi publicado no `master` pelo commit `60e26d2`. O primeiro lote posterior ao levantamento foi iniciado na branch `security/parameterize-bi-stats`, cobrindo as consultas de estatísticas de navegador em `bi_stats/module.php`. O lote converteu leitura, inserção e atualizações de contadores para `fetchPrepared()`/`queryPrepared()` e adicionou regressão específica. O restante de `bi_stats` permanece pendente e será dividido em lotes subsequentes.

### Subsequente — admin e bots de `bi_stats`

Após o merge da PR #126, o loop abriu o sublote `security/parameterize-bi-stats-hits`. O código agora usa `fetchPrepared()`/`queryPrepared()` para a leitura e os contadores de administradores e bots, com regressão adicional. O restante das consultas de referer, realtime, path e hits gerais permanece no backlog da issue #9.

### Subsequente — realtime e caminhos de navegação de `bi_stats`

Após o merge da PR #127, o loop iniciou `security/parameterize-bi-stats-navigation`. O sublote converte a consulta de sessão realtime, o registro/atualização de visita e os contadores de caminho para `fetchPrepared()`/`queryPrepared()`. A cobertura de regressão foi ampliada. Consultas de referer, hits gerais e rotinas diárias ainda serão tratadas em lotes posteriores.

### Subsequente — estatísticas de referer de `bi_stats`

Após o merge da PR #128, o loop iniciou `security/parameterize-bi-stats-referers`. As consultas de leitura, inserção e atualização de referers foram convertidas para `queryPrepared()`, preservando o cálculo de hits e páginas e o tratamento de concorrência. Foi adicionada regressão específica. Hits gerais e rotinas diárias continuam pendentes.

### Subsequente — hits gerais e identificação de página em `bi_stats`

Após o merge da PR #129, o loop iniciou `security/parameterize-bi-stats-hits-general`. A leitura, inserção e atualização dos contadores gerais de hits, aceitação, visitantes recorrentes e administradores foram convertidas para queries preparadas, mantendo os incrementos condicionais como parâmetros numéricos. Foi adicionada regressão específica. As rotinas diárias e demais consultas de manutenção ainda permanecem pendentes.

### Subsequente — manutenção diária de `bi_stats`

Após o merge da PR #130, o loop iniciou `security/parameterize-bi-stats-maintenance`. As leituras de bots e navegadores usadas no benchmark e a consolidação diária de hits e referers passaram para `fetchPrepared()`/`queryPrepared()`, com parâmetros separados para datas, textos e contadores. A regressão foi ampliada. A auditoria de `bi_stats` segue para os pontos restantes e depois avançará para outros plugins legados.

### Subsequente — aliases e cache de `bi_seo`

Após o merge da PR #131, o loop iniciou `security/parameterize-bi-seo`. A resolução de aliases, a reconstrução do índice de aliases e o carregamento de páginas SEO publicadas agora usam `queryPrepared()`, com filtros de alias e idioma separados. Foi adicionada regressão específica. O próximo ciclo continuará a varredura dos plugins com SQL legado e valores externos.

### Próximo lote — analytics de caminhos de `bi_stats`

Após o merge da PR #132, a varredura identificou `stats_pathajax.php` como o próximo fluxo com filtros externos concatenados. O lote `security/parameterize-bi-stats-analytics` converte as consultas de histórico diário, total do intervalo e páginas de entrada/saída para parâmetros preparados, com regressão específica.

### Continuação — analytics agregado de `bi_stats`

O lote seguinte, `security/parameterize-bi-stats-analytics-summary`, converte as consultas de séries históricas, referers, janelas de 24 horas e lookup de títulos de páginas para execução preparada. O fluxo contém consultas adicionais de ranking que permanecerão em lotes subsequentes, mantendo o loop incremental e verificável.
