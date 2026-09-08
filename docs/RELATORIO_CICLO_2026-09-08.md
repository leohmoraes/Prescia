# Relatório do ciclo — 2026-09-08

## Escopo

Este ciclo instalou a skill versionada `phpstan-legacy-remediation`, auditou o estado do repositório `leohmoraes/Prescia` e verificou as pendências executáveis, o histórico de merge e os checks do commit atualmente publicado.

## Resultado da auditoria

O working tree local estava limpo e alinhado com `origin/master`, sem commits à frente ou atrás. Não havia issues abertas nem pull requests abertas no repositório consultado. A skill já fazia parte do repositório e foi instalada em `/home/ubuntu/skills/phpstan-legacy-remediation/SKILL.md` sem alteração; a cópia instalada é byte a byte idêntica à versão versionada.

A skill foi validada pelo validador oficial `quick_validate.py` com resultado **Skill is valid!**. A baseline `phpstan-baseline.neon` não foi alterada. A verificação `git diff --check` não encontrou problemas.

## Evidência de qualidade

O commit publicado no início do ciclo foi `2c8ed4efd484a0b99c136499364da73afcd3a220` (`fix: update Debian packages in Docker image (#116)`). Os check-runs consultados diretamente para esse SHA foram concluídos com sucesso:

| Check | Estado | Conclusão |
|---|---|---|
| PHPStan on PHP 8.3 | completed | success |
| PHP 8.3 tests | completed | success |

O histórico de progresso documenta que o PHPStan global chegou a zero diagnósticos no fechamento da Issue #47, sem expansão da baseline, e que a prioridade seguinte é a frente de segurança de prepared statements. Esses itens aparecem como planejamento técnico histórico, não como issues ou PRs abertos no estado remoto consultado; portanto, não foram classificados como correções novas a inventar neste ciclo.

## Limitação operacional

O sandbox desta execução não possui `php`, `php8.3`, `composer` nem Docker instalados. Por isso, não foi possível repetir localmente PHPUnit, PHPStan, lint PHP 8.3, Composer ou o build da imagem. A validação executável disponível foi feita pelos check-runs verdes do commit remoto, além da integridade Git e da validação da skill.

## Decisão de publicação

Não havia uma alteração de código pendente nem uma PR aberta para mesclar. Criar uma mudança artificial apenas para produzir um merge violaria a regra de menor correção e a política de baseline. Este relatório é a única alteração administrativa do ciclo; ele registra precisamente o que foi verificado e mantém explícitas as limitações locais.

## Próximo alvo

Quando uma nova pendência de implementação for aberta, seguir a skill por lote pequeno: confirmar o contexto dinâmico, aplicar a menor correção, validar focalizadamente, executar a suíte global no CI, publicar a branch e mesclar somente após todos os checks do SHA mergeado estarem `completed`/`success`. A frente técnica documentada como próxima prioridade é a revisão de prepared statements restante, especialmente os consumidores de SQL dinâmico em plugins e fluxos administrativos.

## Próximo ciclo de melhorias — lote ZIP

A varredura identificou `eval()` em `prescia/lib/zipfile.php`, usado somente para converter o timestamp DOS em quatro bytes. O fluxo foi confirmado como serialização local de um valor inteiro; a implementação foi substituída por `pack('V', $this->unix2DosTime($time))`, preservando o formato little-endian do cabeçalho ZIP e eliminando interpretação dinâmica de código.

Foi adicionado `tests/ZipfileTest.php`, que verifica a ausência de `eval()`, a assinatura do cabeçalho local, o timestamp empacotado e o conteúdo descomprimido do arquivo. A baseline PHPStan permanece sem alteração. A validação local de PHP/PHPUnit/PHPStan continua indisponível neste sandbox.

O primeiro commit `00e8666` passou no PHPStan, mas o teste falhou porque a asserção tratava o payload ZIP comprimido como texto plano. O segundo commit `2fd3ba0` corrigiu a regressão usando `gzinflate()` sobre o comprimento declarado no cabeçalho. No SHA final `2fd3ba01806f8e0bf312706927cadd2ae00a503c`, os dois workflows ficaram verdes: [PHPStan on PHP 8.3](https://github.com/leohmoraes/Prescia/actions/runs/34272493043/job/102217303077) e [PHP 8.3 tests](https://github.com/leohmoraes/Prescia/actions/runs/34272493040/job/102217303029).

A próxima frente independente continua sendo a revisão de consultas SQL legadas em plugins, especialmente os fluxos de `bi_bb` que ainda montam filtros de tags/data e contagem de mensagens, além dos caminhos administrativos com comparações derivadas de dados de formulário.

## Lote SQL bi_bb/admin — 2026-09-08

O primeiro lote da nova varredura parametrizou `mod_bi_bb::countMessages()`, transportando o identificador do destinatário e o filtro opcional de data por `fetchPrepared()`. No fluxo `bi_adm/payload/content/edit.php`, as chaves de edição simples passaram a ser anexadas como placeholders ao SQL-array e seus tipos/parâmetros são encaminhados ao `runContent()`; a seleção múltipla passou a gerar placeholders e usar `queryPrepared()`.

Foi adicionada cobertura estática em `tests/SecurityRegressionTest.php`. A baseline permaneceu sem alteração e `git diff --check` passou. O commit `189bbe2c1c1cd85781f655806b8e5394d2c7c740` foi publicado no `master`; os check-runs PHPStan e PHP 8.3 tests concluíram com sucesso. Referências: [PHPStan](https://github.com/leohmoraes/Prescia/actions/runs/34273834077/job/102221831412) e [PHP 8.3 tests](https://github.com/leohmoraes/Prescia/actions/runs/34273835122/job/102221832404).

Ainda pendem, sem alteração neste lote, as APIs `getTags()` e `getArchieveDates()`, que aceitam cláusulas SQL livres e não possuem call sites no repositório, a resolução parametrizada dos lookups de link em `bi_adm/payload/actions/import.php` e comparações SQL derivadas de valores em `bi_adm/payload/content/edit.php` para opções relacionadas. Esses fluxos exigem contratos preparados próprios e devem ser tratados em lotes separados.

## Segundo lote SQL — importação e opções relacionadas — 2026-09-08

O segundo lote parametrizou o lookup de links em `bi_adm/payload/actions/import.php`. O título pesquisado e a chave remota passaram a ser valores vinculados por `queryPrepared()`, enquanto tabela, título e chave continuam derivados exclusivamente do módulo remoto carregado. A distinção entre lookup exato e parcial foi preservada por meio do valor de título com ou sem curingas.

Em `bi_adm/payload/content/edit.php`, as opções relacionadas não-parentais passaram a transportar por `_preparedTypes` e `_preparedParams` tanto o valor atual usado no marcador `selected` quanto os valores dos campos pré-requisitos. As identificações de tabela e coluna continuam sendo obtidas dos metadados do módulo. O suporte de `getContents()` para árvores parentais não foi ampliado neste lote porque sua API atual executa SQL-array por `query()` sem metadados de parâmetros; esse é um próximo alvo independente.

Foram adicionadas regressões em `tests/SecurityRegressionTest.php`. O primeiro commit `5a8bd3d06cebefb0637511ae66e2d544462e78d0` passou no PHPStan e nos testes PHP 8.3. O commit das opções `4a7c2a4` teve inicialmente uma expectativa estática ampla demais; o commit corretivo `809406073fc99f509362e019d2ec252e5f729191` estreitou a asserção ao padrão legado específico. No SHA final, ambos os check-runs ficaram verdes: [PHPStan](https://github.com/leohmoraes/Prescia/actions/runs/34283406736/job/102253282941) e [PHP 8.3 tests](https://github.com/leohmoraes/Prescia/actions/runs/34283406740/job/102253282883). A baseline não foi alterada.

Permanecem pendentes os filtros SQL livres de `getTags()`/`getArchieveDates()`, os filtros da árvore parental via `getContents()` e outros fluxos administrativos não incluídos neste lote.


## Lote SQL bi_bb — contagem de posts do thread — 2026-09-08

O contexto foi confirmado em `prescia/plugins/bi_bb/payload/content/thread.php`: `idf` e `idt` vêm de `friendlyurldata`, já resolvido pelo fluxo de URL amigável, e são identificadores internos de fórum e thread. A contagem agregada ainda interpolava esses valores diretamente em `fetch()`. A menor correção substituiu a chamada por `fetchPrepared()` com dois placeholders e tipos `ii`, preservando a consulta, a agregação e a semântica de paginação.

Foi adicionada a regressão `testBiBbThreadPostCountUsesPreparedInternalIds()` em `tests/SecurityRegressionTest.php`, cobrindo a presença dos placeholders e parâmetros inteiros e rejeitando a concatenação anterior. A baseline PHPStan não foi alterada. `git diff --check` e a auditoria focalizada do plugin foram executados; PHP lint, PHPUnit e PHPStan local não estão disponíveis porque o sandbox não possui PHP nem Composer.

Permanecem pendentes, sem alteração neste lote, a consulta principal de posts do mesmo endpoint, que é consumida pela API `runContent()` a partir de SQL legado e requer transporte estruturado de parâmetros, além de `getTags()`/`getArchieveDates()` e filtros administrativos já registrados nas seções anteriores. O próximo lote deve tratar a consulta principal do thread somente após confirmar o contrato completo de SQL-array e paginação.


## Checks do lote de contagem de posts

No SHA `b3bd56fdd2f7d4919369752ece8d976f8a5bddfc`, os checks obrigatórios concluíram com sucesso: [PHP 8.3 tests](https://github.com/leohmoraes/Prescia/actions/runs/34287664122/job/102266879831) e [PHPStan on PHP 8.3](https://github.com/leohmoraes/Prescia/actions/runs/34287664164/job/102266879759).

## Lote SQL bi_bb — filtros de tags e datas — em execução

O inventário confirmou que `mod_bi_bb::getTags()` e `getArchieveDates()` não possuem call sites no repositório, mas aceitavam uma cláusula SQL livre por meio do parâmetro `$filter`. O primeiro lote do loop substituiu esse contrato por filtros de igualdade estruturados (`array<string, scalar|null>`), validando cada campo contra os metadados do módulo e transportando os valores por placeholders com tipos explícitos. Tabelas, aliases, colunas estruturais, ordenação e as condições base de tags/datas continuam controlados pelo código.

Foi adicionada a regressão `testBiBbArchiveFiltersUsePreparedValuesAndMetadataFields()` em `tests/SecurityRegressionTest.php`, que confirma as assinaturas estruturadas, a validação de campos e o uso de `queryPrepared()`, rejeitando o contrato de SQL livre. A baseline não foi alterada.

Validações locais concluídas neste ambiente: `git diff --check` e inspeção focalizada do diff. PHP 8.3, PHPUnit, PHPStan, Composer e Docker não estão instalados no sandbox; os checks completos devem ser confirmados no CI antes do merge. Após o merge, consultar os check-runs do SHA mergeado e então avançar para a consulta principal de posts do thread, mantendo os filtros parentais de `getContents()` como lote separado.
