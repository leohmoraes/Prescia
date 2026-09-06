# Relatório consolidado de progresso do PHPStan

**Projeto:** Prescia  
**Escopo:** compatibilidade com PHP 8.3 e redução incremental da dívida técnica identificada pelo PHPStan  
**Versão analisada:** PHPStan 2.2.13, nível 1  
**Branch:** `master`  
**Commit atual:** `a1564d7`
**Data de consolidação:** 6 de setembro de 2026
**Issue principal:** [#43 — Atualizar o PHPStan e elevar gradualmente o nível de análise][1]

## Resumo executivo

O projeto avançou de uma configuração inicial sem contratos suficientes para uma análise estática incremental com **PHPStan 2.x no nível 1**, baseline sem supressões e contratos explícitos para os principais contextos dinâmicos do framework. A suíte de compatibilidade com PHP 8.3 permanece aprovada nas execuções recentes, enquanto o workflow completo do PHPStan ainda falha por diagnósticos remanescentes em arquivos que ainda não foram tratados.

O progresso mais significativo ocorreu na separação entre o núcleo `CPrescia`, módulos concretos e payloads incluídos dinamicamente. Essa separação eliminou os diagnósticos de contexto em vários fluxos de administração, autenticação, fórum, cron e labels. Também foram corrigidos fluxos de variáveis indefinidas em listagens, ações de teste, cron e callbacks de módulos.

A principal limitação atual é que o workflow completo analisa todo o repositório e ainda exibe centenas de diagnósticos distribuídos por payloads, módulos e bibliotecas legadas. O formatter do PHPStan atingiu o limite de 1.000 registros em fases anteriores, portanto números históricos acima desse limite devem ser tratados como limites de observação, e não como contagens exatas.

## Estado atual

| Área | Estado | Evidência |
|---|---|---|
| PHP 8.3 | **Aprovada no commit atual** | Execução `34047768938` concluída com sucesso no commit `a1564d7` |
| PHPStan focalizado nos arquivos corrigidos | **Aprovado** | Todos os lotes recentes terminaram com `[OK] No errors` |
| PHPStan completo do repositório | **Ainda falha** | Execução `34047768949` associada ao commit `a1564d7` |
| PHPUnit | Configurado no Composer; execução global não foi usada como critério deste relatório | `phpunit/phpunit ^10.5` |
| Baseline | **Sem novos ocultamentos** | `phpstan-baseline.neon` permanece sem entradas de `ignoreErrors` adicionadas durante os ciclos recentes |
| Branch e working tree | **Limpos** | `master` em `a1564d7`, sem alterações pendentes na consolidação |

## Linha do tempo das correções

A execução foi organizada em lotes pequenos, preservando a capacidade de atribuir cada redução a uma causa específica.

| Commit | Resultado principal |
|---|---|
| `3443111` | Inclusão dos stubs de símbolos legados restantes |
| `c0e924f` | Alinhamento dos tipos de propriedades de plugins |
| `9dfbd97` | Alinhamento de assinaturas e chamadas de métodos |
| `9a3ce69` | Inclusão de contratos genéricos para arrays e retornos procedurais |
| `4c8846b` | Formalização da revisão da baseline como Lote F |
| `4b4e3e0` | Definição da estratégia operacional da Fase F |
| `e43e1b3` | Inicialização de resultados de consultas de autenticação |
| `570cd43` | Correção de visibilidade de propriedades de conexão do driver |
| `aac6fc9` | Especialização dos contratos de payloads para módulos concretos |
| `fa6f0b6` | Registro do início da execução F2 e dos contratos de propriedades |
| `9badeff` | Contratos PHPDoc nos payloads principais de `bi_adm` |
| `682b21e` | Correção do fluxo de variáveis em `bi_adm/payload/content/list.php` |
| `8b1aad5` | Contrato `CPrescia` e proteção de `unset()` em `presciatester` |
| `7819e27` | Contrato do cron, correção de `$core` e chamada de erro inexistente |
| `9de1ff5` | Contratos e inicialização de `$ok` em `bi_bb/bbaction.php` |
| `ab55fef` | Substituição da função global `getuseravatar()` por closure local |
| `d21cfb8` | Contratos e correção de fluxo em `bi_labels/labels_print.php` |

Todos os commits recentes foram publicados em `master` e vinculados à Issue #43 por meio de `Refs #43`.

## Correções validadas por arquivo

As contagens abaixo distinguem o diagnóstico observado antes da correção, quando disponível, da validação focalizada posterior. O workflow histórico possuía diagnósticos duplicados por contexto e, em alguns casos, refletia um commit anterior ao último contrato aplicado; por isso, a coluna de validação local é a evidência principal de conclusão do arquivo.

| Arquivo ou fluxo | Problema tratado | Resultado focalizado |
|---|---|---|
| `prescia/plugins/bi_adm/payload/content/list.php` | `$compare`, `$name`, `$lastThumb`, `$numThumbs`, `$possibleField` e `$isLinker` | **0 erros** |
| `prescia/plugins/bi_adm/payload/content/edit.php` | Contexto de `$core` e `$this`; fluxo de `$itemList` em validação anterior | **0 erros** na validação focalizada |
| `prescia/plugins/bi_adm/payload/content/default.php` | Contratos de contexto; restam propriedades privadas/protegidas a tratar em lote distinto | Diagnósticos de propriedade, não de contexto |
| `pages/presciatester/actions/nextstep.php` | Contrato de `CPrescia` e quatro `unset.offset` | **0 erros** |
| `prescia/lazyload/cron.php` | `$forceCron`, `$this`, referência incorreta a `$core` e `$this->raise()` | **0 erros** |
| `prescia/plugins/bi_bb/payload/actions/bbaction.php` | Contratos de `CPrescia`/`mod_bi_bb` e `$ok` | **0 erros** |
| `prescia/plugins/bi_bb/payload/content/thread.php` | Contratos e função global `getuseravatar()` | **0 erros**; callback convertido em closure |
| `prescia/plugins/bi_labels/payload/content/labels_print.php` | Contratos, `fastClose()` no objeto correto e fluxo de `$lData` | **0 erros** |

Nos arquivos tratados, as validações locais foram executadas com PHP 8.3.6, PHPStan 2.2.13 e `php -l`. Os artefatos `vendor/` e `composer.lock` foram removidos após cada validação quando gerados somente para uso local.

## Evolução arquitetural

### Contratos de contexto

O framework usa payloads procedurais incluídos no escopo de controladores e módulos. O PHPStan não consegue inferir automaticamente todos esses contratos. O padrão adotado agora documenta explicitamente o contexto real:

```php
/** @var CPrescia $core Runtime payload context injected by the framework. */
/** @var mod_bi_bb $this Runtime module context injected by the framework. */
```

O contrato só é aplicado depois de verificar o carregador do arquivo. Propriedades pertencentes ao núcleo são acessadas por `$core` ou por `$this->parent`; propriedades pertencentes ao plugin permanecem em `$this` com o tipo concreto do módulo.

### Fluxos de variáveis

As correções de variáveis seguem três regras. Primeiro, a inicialização ocorre no menor escopo comum ao consumidor. Segundo, os valores `false`, `null`, zero e arrays vazios continuam semanticamente distintos. Terceiro, referências que apontavam para variáveis fora do escopo são corrigidas para o campo ou objeto efetivamente usado, em vez de receber uma inicialização artificial.

### Callbacks

A função global `getuseravatar()` foi convertida em uma closure local no payload `thread.php`. O mecanismo de `CKTemplate::fullpage()` aceita callbacks invocáveis, portanto a alteração remove o risco de colisão e redeclaração sem mudar o contrato de execução por registro.

### Símbolos e baseline

Stubs e bootstrap foram utilizados somente para símbolos cuja origem dinâmica foi confirmada. A baseline não foi usada para encobrir regressões. Diagnósticos de arquivos ausentes, classes dinâmicas, métodos inexistentes e constantes de configuração continuam separados das correções de variáveis e contratos.

## Diagnósticos ainda pendentes

A execução completa do PHPStan ainda apresenta falhas em outros componentes. O ranking mais recente disponível antes dos últimos commits apontava os seguintes grupos prioritários:

| Prioridade | Arquivo ou grupo | Diagnósticos observados | Classificação inicial |
|---:|---|---:|---|
| 1 | `prescia/lazyload/friendlyurl.php` | 32 | Variáveis e contexto de payload |
| 2 | `prescia/plugins/bi_bb/payload/content/index.php` | 31 | Contratos de `$core` e `$this` |
| 3 | `prescia/lazyload/fastclose.php` | 31 | Contexto do núcleo e fluxo de encerramento |
| 4 | `prescia/lazyload/script.php` | 24 | Contexto procedural e variáveis locais |
| 5 | `prescia/plugins/bi_adm/module.php` | 22 | Variáveis de módulo e contratos de propriedades |
| 6 | `prescia/plugins/bi_stats/payload/content/stats_ref.php` | 19 | Contratos do núcleo e dados de relatório |
| 7 | `prescia/lazyload/udm.php` | 19 | Variáveis de roteamento e contexto |
| 8 | `prescia/plugins/bi_stats/payload/content/stats_pathajax.php` | 18 | Contexto do núcleo em AJAX |
| 9 | `prescia/plugins/bi_bb/payload/content/profile.php` | 18 | Contratos de núcleo e módulo |
| 10 | `prescia/plugins/bi_bb/payload/actions/default.php` | 17 | Contratos de ação e fluxo local |

O ranking acima é uma fotografia do relatório da execução anterior ao commit `d21cfb8`. Ele deve ser recalculado após a conclusão dos workflows atuais antes de iniciar o próximo patch, pois as correções recentes removem diagnósticos de outros arquivos por efeito de contratos compartilhados.

Também permanecem categorias estruturais que não devem ser misturadas ao lote de variáveis:

| Categoria | Tratamento previsto |
|---|---|
| `method.notFound` | Fase F3; confirmar a classe concreta e corrigir chamadas inválidas |
| `require.fileNotFound` e `includeOnce.fileNotFound` | Fase F5; corrigir caminhos ou excluir bibliotecas legadas não pertencentes ao framework |
| `class.notFound` | Fase F4; revisar `CDBO_0`, `CDBO` e carregamento dinâmico |
| `function.notFound` | Fase F4; confirmar origem e assinatura antes de criar stubs |
| `constant.notFound` | Fase F4; distinguir configuração externa de constante realmente ausente |
| `property.private` e `property.protected` | Fase F2; preferir API ou contexto correto antes de ampliar visibilidade |
| `function.inner` | Revisar funções locais declaradas em payloads e migrar para closures quando apropriado |
| `regexp.pattern` | Fase F5; corrigir padrões executáveis, sem supressão |

## Metas e critérios de aceite para a próxima etapa

A próxima etapa deve começar com um relatório novo do CI associado ao commit `5f61356` ou ao commit subsequente que concluir a validação atual. O relatório deve separar os diagnósticos por identificador, arquivo e linha, e não deve tratar o limite de 1.000 registros como contagem total.

O próximo ciclo deve priorizar os payloads de `bi_bb` e os carregadores procedurais que ainda possuem contratos ausentes. Cada alteração deverá ser validada localmente com PHPStan focalizado, `php -l` e `git diff --check`. Após cada grupo coerente, deverão ser executados os workflows de análise estática e compatibilidade PHP 8.3.

A elevação para o nível 2 do PHPStan não deve ocorrer enquanto a análise de nível 1 continuar falhando por diagnósticos estruturais. O nível deve ser elevado somente depois de a baseline permanecer vazia, os fluxos dinâmicos principais possuírem contratos e os erros remanescentes estarem classificados em lotes independentes.

## Atualização da Fase F3

O primeiro ciclo da Fase F3 tratou três diagnósticos `method.notFound` no payload `prescia/plugins/bi_stats/payload/content/module.php`. O payload tentava chamar `loadAllModules()` e `loaded()` no objeto `mod_bi_stats`, embora esses métodos pertençam ao núcleo `CPrescia`.

| Diagnóstico | Correção aplicada | Validação |
|---|---|---|
| `mod_bi_stats::loadAllModules()` | Substituído por `$core->loadAllmodules()` | PHPStan focalizado sem erros |
| `mod_bi_stats::loaded('STATSDAILY')` | Substituído por `$core->loaded('STATSDAILY')` | PHPStan focalizado sem erros |
| `mod_bi_stats::loaded($selmod)` | Substituído por `$core->loaded($selmod)` | PHPStan focalizado sem erros |

O contrato explícito de `$core` foi adicionado ao payload. A validação com PHP 8.3 não encontrou erros de sintaxe. O commit `5f61356` foi publicado na branch `master` e vinculado à Issue #43. O próximo alvo da Fase F3 é o inventário de métodos ausentes em `bi_adm/module.php` e nos payloads restantes, mantendo funções globais ausentes e classes dinâmicas em lotes separados.

## Conclusão

O projeto possui agora uma base operacional adequada para continuar a migração para PHP 8.3 sem depender de supressões globais. Os contratos de contexto e os fluxos de variáveis corrigidos já foram validados isoladamente em oito arquivos prioritários, e a compatibilidade PHP 8.3 continua passando nos workflows recentes.

O objetivo imediato não é declarar o PHPStan global como verde, pois ainda existem diagnósticos em arquivos não tratados. O objetivo alcançado foi reduzir as causas estruturais mais repetitivas, preservar a baseline sem novos ocultamentos e estabelecer um processo reproduzível de correção por fluxo.

## Referências

[1]: https://github.com/leohmoraes/Prescia/issues/43 "Issue #43 — Atualizar o PHPStan e elevar gradualmente o nível de análise"
[2]: https://github.com/leohmoraes/Prescia/actions/runs/34006264571 "PHP static analysis — execução 34006264571"
[3]: https://github.com/leohmoraes/Prescia/actions/runs/34006264562 "PHP 8.3 compatibility — execução 34006264562"
[4]: https://github.com/leohmoraes/Prescia/actions/runs/34006130078 "PHP static analysis — execução 34006130078"
[5]: https://github.com/leohmoraes/Prescia/blob/master/docs/PLANO_PHPSTAN_PROXIMO_LOTE.md "Plano do próximo lote de redução dos erros do PHPStan"
[6]: https://github.com/leohmoraes/Prescia/blob/master/phpstan.neon.dist "Configuração do PHPStan"
[7]: https://github.com/leohmoraes/Prescia/commits/master "Histórico de commits do Prescia"
[8]: https://github.com/leohmoraes/Prescia "Repositório Prescia"

*Autor: Manus AI* 
*Documento consolidado a partir do histórico do repositório, do plano PHPStan e das execuções de CI disponíveis na data de consolidação.*


## Verificação completa após o lote CDBO_mysqli

Foi executada uma análise completa do repositório no commit `0237763`, com PHPStan 2.2.13 no nível 1, cache limpo e a configuração oficial de `phpstan.neon.dist`. A execução local encontrou **620 diagnósticos**, contra **716** na execução completa anterior `34040527721`, uma redução de **96 diagnósticos**.

| Categoria | Diagnósticos atuais |
|---|---:|
| `variable.undefined` | 595 |
| `require.fileNotFound` | 4 |
| `class.nameCase` | 4 |
| `function.notFound` | 3 |
| `function.inner` | 3 |
| `constant.notFound` | 3 |
| `includeOnce.fileNotFound` | 2 |
| `array.duplicateKey` | 2 |
| `unset.offset` | 1 |
| `missingType.iterableValue` | 1 |
| `isset.variable` | 1 |
| `constructor.unusedParameter` | 1 |
| **Total** | **620** |

A categoria `property.notFound` não aparece mais no relatório completo após a descoberta dos drivers `CDBO` e `CDBO_mysqli`. A análise completa ainda falha no nível 1, principalmente pelas 595 variáveis indefinidas e por símbolos/caminhos legados. A baseline não foi ampliada. O log integral está disponível localmente em `/tmp/phpstan-full-0237763.log`; o artefato temporário não é versionado.


## Atualização de 6 de setembro de 2026 — lote bi_bb

O commit `a1564d7` continuou a remediação de `variable.undefined` no plugin `bi_bb` e foi publicado na branch `master`, com referência à Issue [#44 — Reduzir diagnósticos restantes do PHPStan][9].

### `prescia/plugins/bi_bb/payload/content/forum.php`

O arquivo foi confirmado como payload incluído por `mod_bi_bb::onRender()`. Foram documentados os contratos de contexto `CPrescia $core` e `mod_bi_bb $this` com PHPDoc. Também foi inicializado `$sql` antes do `switch` de `operationmode`, mantendo os três modos válidos (`bb`, `blog` e `articles`) e eliminando o fluxo possivelmente não atribuído.

A validação focalizada terminou com **0 erros no PHPStan 2.2.13** e o arquivo passou no `php -l` do PHP 8.3. O resultado foi publicado no commit `a1564d7`.

### `prescia/plugins/bi_bb/payload/content/profile.php`

O arquivo foi analisado como o próximo alvo de maior impacto no diretório `bi_bb`. A investigação confirmou que ele é incluído dinamicamente por `mod_bi_bb::onShow()` quando a ação é `profile`, recebendo `$core` como contexto `CPrescia`. A recomendação técnica registrada é adicionar o PHPDoc de `$core` no início do payload e inicializar `$ext` antes da chamada por referência `locateFile($image, $ext)`.

Neste fechamento, `profile.php` **ainda não foi alterado nem commitado**. Portanto, o relatório registra a análise e o plano de correção, mas não declara o arquivo como concluído. A validação e a publicação de `profile.php` permanecem como o próximo passo do Lote B.

| Arquivo | Situação | Evidência |
|---|---|---|
| `bi_bb/payload/content/forum.php` | **Concluído** | PHPStan focalizado sem erros; `php -l` aprovado; commit `a1564d7` |
| `bi_bb/payload/content/profile.php` | **Analisado, pendente de implementação** | Contexto de inclusão confirmado; PHPDoc e `$ext` definidos como próxima correção |

A execução de compatibilidade PHP 8.3 do commit `a1564d7` passou em [34047768938][10]. A análise completa do PHPStan ainda falhou em [34047768949][11], como esperado enquanto os arquivos remanescentes do Lote B não forem corrigidos. A baseline continua sem novas entradas.

[9]: https://github.com/leohmoraes/Prescia/issues/44 "Issue #44 — Reduzir diagnósticos restantes do PHPStan"
[10]: https://github.com/leohmoraes/Prescia/actions/runs/34047768938 "PHP 8.3 compatibility — execução 34047768938"
[11]: https://github.com/leohmoraes/Prescia/actions/runs/34047768949 "PHP static analysis — execução 34047768949"
