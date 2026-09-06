# Plano do próximo lote de redução dos erros do PHPStan

**Issue:** [#43 — Atualizar o PHPStan e elevar gradualmente o nível de análise][5]  
**Commit de referência:** `22c3615`  
**Versão analisada:** PHPStan 2.x, nível 1  
**Status:** Planejamento aprovado para execução incremental  
**Autor:** **Manus AI**

**Execução iniciada:** o primeiro lote recebeu contratos PHPDoc explícitos nos payloads de `bi_adm` e `bi_stats`, documentando `$core` como contexto `CPrescia` e `$this` como contexto `CModule` quando o arquivo é incluído pelo módulo.

**Lote B iniciado:** os resultados `$r` e `$n` foram inicializados nos caminhos de consulta preparada e paginação de `CModule`, e os usos incorretos de `$code` e `$valor` em `CintlControl` foram corrigidos pelos parâmetros e propriedades correspondentes.

**Lote C iniciado:** foram adicionados contratos específicos para `dieFreakingThumbs()`, `adodb_daylight_sv()` e a classe dinâmica `CDBO_0`, todos identificados no relatório do CI como símbolos ausentes.

**Lote D — propriedades:** corrigidas as sete sobrescritas incompatíveis em `bi_adm`, `bi_bb`, `bi_dev`, `bi_labels` e `bi_undo`. O contrato de `admFolder` foi ampliado de forma explícita para `string|array<int, string>`, refletindo a normalização por `explode()` usada pelo runtime.

**Lote D — métodos:** alinhadas as chamadas de `CKTemplate`, `runclasses()`, `checkPermission()` e `checkHTML()`. As duas funções locais anteriormente chamadas `appendActs()` foram separadas em `appendAdminActs()` e `appendLogActs()` para eliminar a colisão de assinaturas durante a análise.

**Lote E iniciado:** adicionados contratos genéricos para `arrayToString()`, `extractUri()`, `listFiles()`, `xmlParamsParser()` e `adodb_daylight_sv()` nos arquivos procedurais e nos stubs do PHPStan. Os contratos preservam as diferenças entre listas, arrays associativos e o retorno `string|array` de `xmlParamsParser()`.

**Lote F oficializado:** a revisão da baseline passa a ser uma etapa independente, executada após a validação do Lote E. Nenhum diagnóstico novo poderá ser incluído na baseline para encobrir regressões.

**Diagnóstico inicial do Lote F:** a execução [PHP static analysis — execução 33582848768][6], no commit `4c8846b`, falhou com mais de 1000 diagnósticos. O formatter limitou a saída aos primeiros 1000 registros. A baseline contém `ignoreErrors: []`, portanto nenhum diagnóstico está sendo ocultado.

**Fase F1 — primeiro ciclo:** foram inicializados os resultados `$r` e `$n` nos fluxos de autenticação, sessão, carregamento de usuário e no teste de integração `presciatester`. A correção preserva a distinção entre resultado inexistente (`false`/`null`) e contagem vazia (`0`), sem alterar a decisão de sucesso das consultas.

**Fase F2 — primeiro ciclo:** as propriedades de conexão de `CDBO` usadas pelos drivers filhos foram alteradas de `private` para `protected`, preservando o encapsulamento contra consumidores externos e permitindo a herança prevista pelo driver. Também foram corrigidos dois acessos incorretos a `$this->fields` e um typo em `$ownerlink` no controle de autorização, substituindo-os pelo módulo e pela variável de fluxo corretos.

**Fase F2 — segundo ciclo iniciado:** `CPresciaVar::$headerControl` foi declarado como estado do controlador inicializado pelo construtor de `CPrescia`. Os payloads de `bi_adm` e `bi_stats` que recebem o módulo por `$this` deixaram de declarar o tipo genérico `CModule` e passaram a referenciar `mod_bi_adm` ou `mod_bi_stats`. A propriedade `hasStats` foi exposta como `protected` porque é consumida pelos payloads administrativos incluídos no contexto do módulo.

**Fase F2 — terceiro ciclo planejado:** o próximo ciclo tratará exclusivamente os acessos a propriedades que continuam ausentes após o commit `aac6fc9` e a execução [PHP static analysis — execução 33704528222][7]. O ciclo não alterará a baseline e não adicionará propriedades genéricas às classes apenas para reduzir a contagem do relatório.

**Fase F2 — terceiro ciclo, análise executada:** a execução [PHP static analysis — execução 33707202232][8], associada ao commit `4a711bb`, atingiu o limite do formatter de 1.000 diagnósticos. O recorte disponível contém 962 registros `variable.undefined`, além de `property.notFound`, `require.fileNotFound`, `class.notFound`, `function.notFound`, `constant.notFound` e outras categorias. Como o limite impede estimar o total real, os números serão tratados como limite inferior do relatório exibido.

**Fase F2 — terceiro ciclo, correção aplicada:** os acessos a `modules`, `template` e `dbo` no payload de `bi_stats` foram reclassificados como estado de `CPrescia` e passaram a usar `$this->parent`. O payload `bi_adm` passou a alterar `$core->layout`, e não uma propriedade inexistente de `mod_bi_adm`. No `bi_auth`, `action` passou a ser lido de `$this->parent`, enquanto os rótulos de e-mail foram convertidos em chaves literais de tradução (`account_welcome` e `account_activation_required`) em vez de propriedades não declaradas. Nenhuma entrada foi adicionada à baseline.

O recorte de propriedades confirmou os seguintes alvos para o terceiro ciclo: `mod_bi_adm::$layout`; `mod_bi_auth::$action`, `$account_activation_required` e `$account_welcome`; e `mod_bi_stats::$modules`, `$template` e `$dbo`. A investigação deve começar pelos payloads de `bi_stats`, porque `modules`, `template` e `dbo` aparentam pertencer ao núcleo `CPrescia`, e depois revisar os três estados de autenticação e o acesso a `layout` no administrador. Os 962 diagnósticos de variáveis indefinidas permanecem registrados para a Fase F1 e não serão misturados às correções de contrato deste ciclo.

### Plano de ação da Fase F2 — terceiro ciclo

O objetivo do terceiro ciclo é separar propriedades que pertencem ao núcleo `CPrescia` de propriedades que pertencem aos módulos concretos. O diagnóstico será corrigido no ponto em que o contrato estiver errado, e não no consumidor por meio de casts indiscriminados ou supressões.

| Ordem | Escopo | Diagnósticos observados | Ação prevista | Evidência exigida |
|---:|---|---|---|---|
| 1 | `bi_stats` | `template`, `dbo` e `modules` | Confirmar se o include executa com o módulo ou com o núcleo como `$this`; ajustar o PHPDoc ou usar `$core` explicitamente | Trecho do carregador e lista de call sites |
| 2 | `bi_adm` | `layout` | Verificar se o valor é estado do núcleo, do módulo ou uma dependência injetada; declarar no contrato correto somente se o módulo realmente o possuir | Declaração da classe e fluxo de inclusão |
| 3 | `bi_auth` | `action`, `account_welcome` e `account_activation_required` | Distinguir parâmetros locais, propriedades de configuração e estado do núcleo; corrigir o acesso ou declarar propriedades tipadas quando fizerem parte do módulo | Assinatura, inicialização e consumidores |
| 4 | Todos os payloads tratados | `property.notFound` residual | Reexecutar PHPStan e comparar por arquivo, classe, propriedade e linha | Relatório antes/depois e contagem por identificador |

#### Procedimento de execução

Primeiro, será criado um inventário dos diagnósticos `property.notFound` no commit `aac6fc9`, incluindo arquivo, linha, classe inferida pelo PHPStan e contexto real do `include` ou `include_once`. Em seguida, cada propriedade será classificada como **núcleo**, **módulo**, **parâmetro local**, **propriedade legítima não declarada** ou **referência incorreta**.

Depois da classificação, as correções serão aplicadas em commits pequenos. Um PHPDoc será alterado somente quando o escopo de execução comprovar o tipo concreto de `$this`. Uma propriedade será declarada somente na classe que a inicializa e consome como parte de seu estado. Uma referência será substituída por uma API pública ou pelo objeto correto quando o valor pertencer a outra camada.

O ciclo terminará com a execução do PHPStan, do PHPUnit e do teste de compatibilidade com PHP 8.3. Os resultados serão comparados com a execução `33704528222`. Diagnósticos de métodos ausentes, funções ausentes e arquivos não encontrados serão registrados para a Fase F3 ou para o lote de símbolos, mas não serão misturados a este ciclo salvo quando forem consequência direta de uma correção de contexto.

#### Regras de decisão

| Situação encontrada | Decisão |
|---|---|
| A propriedade é inicializada em `CPrescia` e consumida pelo payload | Usar `CPrescia` como contexto ou acessar a dependência por `$core`. |
| A propriedade é inicializada em `mod_bi_adm`, `mod_bi_stats` ou `mod_bi_auth` | Declarar o tipo na classe concreta e manter o PHPDoc do payload especializado. |
| O nome corresponde a um parâmetro ou variável local | Corrigir o acesso para usar a variável existente; não criar uma propriedade. |
| A propriedade é acessada por um include, mas é privada na classe proprietária | Preferir método protegido ou público com contrato claro; ampliar visibilidade somente se o include fizer parte do escopo legítimo da classe. |
| A origem não puder ser comprovada | Não alterar o código nem a baseline; registrar o caso para investigação separada. |

#### Critérios de aceite do terceiro ciclo

O ciclo será considerado concluído quando cada diagnóstico de propriedade tratado possuir uma classificação e uma correção justificadas. Os payloads modificados deverão declarar o contexto real de `$this`, sem introduzir variáveis globais artificiais. As propriedades novas ou alteradas deverão ter inicialização compatível com todos os caminhos de construção da classe. O PHPStan deverá apresentar redução nos diagnósticos `property.notFound` sem aumento equivalente de `method.notFound`, `variable.undefined` ou erros de inclusão. PHPUnit e o teste de compatibilidade PHP 8.3 deverão permanecer aprovados. A baseline deverá continuar sem novas entradas.

#### Métricas obrigatórias

O registro do ciclo deverá informar o commit analisado, a URL da execução do CI, o total reportado ou o limite do formatter, a contagem de `property.notFound` antes e depois, a quantidade de propriedades reclassificadas, a quantidade de propriedades declaradas, a quantidade de referências corrigidas e qualquer regressão introduzida. Se o formatter voltar a limitar a saída a `1000+`, esse valor será registrado como limite superior, e não como total exato.

## Conclusão executiva

A última execução do PHPStan terminou com falha, mas as correções recentes reduziram os erros diretamente relacionados a cabeçalhos PHP inválidos, constantes de configuração e resultados de consultas não inicializados. O próximo lote não deve elevar o nível de análise. A prioridade é eliminar as causas estruturais que ainda geram a maior parte do relatório: contexto global ausente, chamadas de módulos/plugins sem contratos, variáveis indefinidas remanescentes e símbolos legados que ainda não possuem bootstrap ou stub confiável.

O relatório histórico do CI associado ao commit `22c3615` continha **2.126 diagnósticos**. O relatório atual do Lote F excedeu o limite de 1000 registros e, por isso, não deve ser usado para estimar o total real. A leitura dos registros exibidos confirma que `variable.undefined` é a categoria dominante, seguida por acessos a propriedades privadas ou ausentes, métodos e símbolos não encontrados. A estratégia deve tratar as causas por fluxo e contrato, não adicionar supressões globais.

| Categoria | Diagnósticos | Prioridade | Estratégia |
|---|---:|---:|---|
| Contexto global ou dinâmico (`$core`, `$this`) | 1.797 | P0 | Mapear includes, criar contratos de entrada e separar payload procedural de métodos de classe. |
| Variáveis indefinidas | 220 | P0 | Corrigir por fluxo, inicialização defensiva e validação de retorno. |
| Funções ausentes | 14 | P1 | Completar bootstrap/stubs somente após confirmar a assinatura real. |
| Classes ausentes | 7 | P1 | Registrar contratos de classes carregadas dinamicamente ou ajustar `scanFiles`. |
| Propriedades incompatíveis | 7 | P1 | Alinhar tipos de propriedades herdadas e sobrescritas. |
| Métodos e assinaturas | 3 | P1 | Corrigir chamadas incompatíveis ou ampliar contratos precisos. |
| Constantes ausentes | 3 | P1 | Definir no bootstrap estático quando forem configuração externa. |
| Tipos iteráveis e retornos | 1 | P2 | Completar tipos genéricos nas funções procedurais. |

### Estratégia operacional do Lote F

O Lote F será executado em ciclos curtos e mensuráveis. Cada ciclo deve partir do mesmo commit analisado pelo CI, preservar a baseline atual e produzir um relatório completo ou explicitamente limitado. Quando o formatter limitar a saída, a equipe deve registrar essa limitação e usar o identificador, arquivo e linha dos diagnósticos disponíveis sem inferir que o número exibido representa o total.

| Fase | Tratamento | Regra de decisão |
|---:|---|---|
| F1 | Variáveis indefinidas | Corrigir primeiro variáveis de banco, sessão, upload, imagem e requisição; inicializar no menor escopo comum e preservar `false`, `null`, lista vazia e valor válido. |
| F2 | Propriedades privadas ou ausentes | Confirmar a classe real do objeto; declarar a propriedade no contrato correto ou substituir o acesso por uma API pública. Não alterar visibilidade apenas para silenciar o PHPStan. |
| F3 | Métodos ausentes | Mapear a classe concreta e o carregamento dinâmico; corrigir o call site quando o método estiver incorreto ou adicionar uma declaração precisa quando o método existir em runtime. |
| F4 | Funções, classes e constantes ausentes | Localizar a origem real, ajustar `scanFiles` ou o bootstrap sem efeitos colaterais e criar stub somente quando o símbolo for opcional ou dinâmico. |
| F5 | Erros de inclusão e sintaxe de padrões | Corrigir caminhos, arquivos ausentes e expressões regulares inválidas no código ou na configuração; não criar `ignoreErrors` para problemas executáveis. |
| F6 | Baseline | Remover entradas somente quando o diagnóstico desaparecer e a correção estiver coberta por revisão ou teste. A baseline continua vazia até que um diagnóstico preexistente seja comprovado e justificado. |

Os diagnósticos de `variable.undefined` devem ser agrupados por arquivo, variável e fluxo de execução. Para cada correção, o registro deve indicar o valor inicial, a condição que garante a atribuição e o consumidor do valor. Os diagnósticos de contexto `$core` e `$this` devem continuar sendo tratados por contratos de payload e não por variáveis globais artificiais.

As categorias `property.private`, `property.notFound`, `method.notFound`, `class.notFound`, `function.notFound`, `constant.notFound`, `require.fileNotFound`, `includeOnce.fileNotFound`, `function.inner` e `constructor.unusedParameter` devem receber tarefas separadas quando a causa não puder ser resolvida no mesmo componente. Essa separação evita que uma alteração de tipagem esconda um problema de carregamento ou comportamento.

**Métrica de cada ciclo:** registrar o commit analisado, o total reportado ou o limite do formatter, a contagem por identificador, o número de erros fora da baseline, a quantidade de entradas removidas e o número de regressões. Quando o CI reportar `1000+`, a métrica deve ser registrada como limite superior e não como total exato.

## Escopo do próximo lote

### Lote A — Contrato de contexto para `$core` e `$this`

Os diagnósticos de contexto devem ser agrupados por diretório e não corrigidos com supressão global. Para cada payload, é necessário identificar se o arquivo é incluído por um controlador, executado diretamente ou avaliado dentro de uma classe. Arquivos incluídos devem receber uma anotação de contrato ou uma assinatura de função adaptadora. Código que depende de `$this` deve permanecer em classe ou ser convertido para receber explicitamente a dependência necessária.

A primeira entrega deve cobrir `prescia/plugins/bi_adm`, `prescia/plugins/bi_stats` e os payloads referenciados no relatório. O inventário deve registrar arquivo, variável, origem esperada e solução aplicada. O objetivo é reduzir a categoria sem criar uma variável global fictícia que possa mascarar defeitos de execução.

**Critério de aceite:** nenhum novo uso de `$this` fora de contexto de classe deve ser introduzido; os arquivos tratados devem possuir um contrato explícito; a contagem de diagnósticos de contexto deve diminuir sem aumento equivalente em erros de chamada.

### Lote B — Variáveis indefinidas por fluxo

As 220 ocorrências restantes devem ser agrupadas por variável e arquivo. Variáveis usadas como contadores, resultados de consulta ou acumuladores devem ser inicializadas no menor escopo comum antes do primeiro uso. Variáveis de upload, imagem e parâmetros de requisição devem ser derivadas de entradas validadas e receber valores padrão compatíveis com o contrato do consumidor.

O lote deve começar por variáveis que possam causar erro em produção: resultados de banco, identificadores de arquivo, valores de sessão e parâmetros de entrada. Cada correção deve preservar a distinção entre `false`, `null`, lista vazia e valor válido. A inicialização não deve esconder uma falha de consulta nem transformar entrada inválida em dado confiável.

**Critério de aceite:** as variáveis são definidas em todos os caminhos alcançáveis; os testes existentes continuam passando; cada correção possui uma verificação de fluxo ou teste de regressão quando a variável participa de autenticação, upload ou consulta.

### Lote C — Símbolos legados restantes

As funções ausentes, classes ausentes e constantes restantes devem ser resolvidas por classificação. Uma função usada em produção deve ser localizada e adicionada ao `scanFiles` ou ao bootstrap seguro. Uma função opcional de plugin deve receber um stub com assinatura compatível e uma justificativa. Classes carregadas dinamicamente devem receber contratos tipados ou configuração explícita de análise.

Não devem ser criados stubs genéricos com `mixed ...$arguments` quando a assinatura real estiver disponível. Essa prática reduz a qualidade da análise e pode esconder incompatibilidades entre plugins. O bootstrap não deve carregar configurações de produção, abrir conexões, iniciar sessões ou executar escrita em disco.

**Critério de aceite:** cada símbolo ausente possui origem documentada; o bootstrap continua sem efeitos colaterais; funções e classes efetivamente usadas pelo runtime têm contratos que refletem seus parâmetros e retornos.

### Lote D — Hierarquia de propriedades e métodos

Os sete diagnósticos de propriedades incompatíveis devem ser corrigidos alinhando as declarações das classes filhas com os tipos das classes base. Propriedades como `name`, `admFolder` e `customPermissions` devem ter um único contrato coerente na hierarquia. Quando o legado permite múltiplas formas, o tipo deve ser explicitamente ampliado e documentado, em vez de removido apenas para silenciar o PHPStan.

As chamadas de métodos incompatíveis devem ser revisadas junto com as assinaturas reais. O ajuste deve preservar compatibilidade entre módulos e plugins e deve incluir testes de instanciação ou carregamento dos componentes afetados.

**Critério de aceite:** não existem sobrescritas com tipos incompatíveis nos componentes tratados; chamadas de métodos usam a quantidade e os tipos de argumentos definidos pelo contrato; não são adicionados casts indiscriminados.

### Lote F — Revisão controlada da baseline

O Lote F consolida o resultado das correções dos Lotes A a E e revisa a baseline do PHPStan sem reduzir a qualidade da análise. A baseline é um registro temporário de diagnósticos conhecidos e justificados. Ela não deve ser usada para ocultar erros introduzidos por alterações recentes.

A primeira etapa consiste em executar o PHPStan no commit resultante do Lote E e salvar o relatório completo. A segunda etapa compara cada diagnóstico atual com a baseline, classificando-o como corrigido, preexistente, regressão ou não reproduzível. A terceira etapa remove da baseline os diagnósticos comprovadamente corrigidos. A quarta etapa registra separadamente os diagnósticos preexistentes que ainda exigem correção.

| Etapa | Atividade | Evidência obrigatória |
|---:|---|---|
| 1 | Executar PHPStan, PHPUnit e o teste de compatibilidade PHP 8.3 no mesmo commit | URLs das execuções e status dos jobs |
| 2 | Comparar o relatório atual com `phpstan-baseline.neon` | Contagem por identificador, arquivo e linha |
| 3 | Remover entradas referentes a diagnósticos corrigidos | Diff revisado da baseline |
| 4 | Classificar os diagnósticos restantes | Relatório de preexistentes, regressões e não reproduzíveis |
| 5 | Atualizar o plano e a Issue #43 | Commit e comentário técnico vinculados |

O Lote F não autoriza a inclusão automática de novos padrões `ignoreErrors`. Cada nova exceção deve conter uma justificativa, escopo de arquivo ou identificador específico, origem conhecida e uma condição para remoção. Diagnósticos de segurança, compatibilidade PHP 8.3 e erros introduzidos por commits recentes não podem ser adicionados à baseline.

**Métricas:** o relatório deve informar o total de diagnósticos, o total fora da baseline, a quantidade de entradas removidas, a quantidade de regressões e o número de símbolos ausentes. As métricas devem ser comparáveis com as execuções anteriores do CI.

**Critério de aceite:** a baseline não aumenta por causa de erros novos; todas as entradas removidas correspondem a diagnósticos comprovadamente corrigidos; não existem regressões fora da baseline sem uma tarefa registrada; o PHPUnit, o teste de compatibilidade PHP 8.3 e o PHPStan permanecem configurados no CI; e dois ciclos consecutivos do CI não apresentam aumento de diagnósticos fora da baseline antes da avaliação do nível 2.

## Ordem de execução

| Ordem | Entrega | Resultado esperado |
|---:|---|---|
| 1 | Inventário por arquivo dos 1.797 erros de contexto | Separar falsos positivos estruturais de usos realmente inválidos. |
| 2 | Correção dos payloads de `bi_adm` e `bi_stats` | Reduzir a maior categoria sem introduzir globais artificiais. |
| 3 | Variáveis indefinidas restantes | Remover riscos de execução e estabilizar o fluxo de análise. |
| 4 | Funções e classes ausentes | Completar contratos específicos e o `scanFiles`. |
| 5 | Propriedades e métodos incompatíveis | Consolidar a hierarquia tipada dos plugins. |
| 6 | Lote F — revisão controlada da baseline | Remover entradas corrigidas e classificar os diagnósticos restantes sem ocultar regressões. |
| 7 | Avaliação para nível 2 em diretórios modernizados | Elevar o nível somente onde o nível 1 estiver estável após dois ciclos consecutivos. |

## Regras de implementação

Cada commit deve tratar uma categoria ou um componente delimitado e referenciar a Issue #43. A baseline não deve ser ampliada para erros introduzidos por alterações novas. Stubs devem representar contratos reais e não substituir a correção de código executável. Correções de tipagem não devem ser misturadas com mudanças funcionais amplas, migração de SQL ou alterações de autenticação.

O CI deve continuar executando PHPUnit, o teste de compatibilidade PHP 8.3 e PHPStan no mesmo ambiente de PHP. A métrica de cada lote deve ser registrada pelo total de diagnósticos, pelos erros fora da baseline e pela quantidade de símbolos ausentes. A análise de nível 2 somente poderá começar depois de dois ciclos consecutivos sem aumento de erros fora da baseline nos diretórios modernizados.

## Evidências e referências

A contagem foi extraída do log da execução [PHP static analysis — execução 33580767192][4]. A compatibilidade de runtime foi validada separadamente pela execução [PHP 8.3 compatibility — execução 33580767190][3]. A configuração atual usa bootstrap, stubs, `scanFiles` e baseline conforme o arquivo `phpstan.neon.dist`.

[1]: https://phpstan.org/user-guide/discovering-symbols "PHPStan — Discovering Symbols"
[2]: https://phpstan.org/user-guide/config-reference "PHPStan — Configuration Reference"
[3]: https://github.com/leohmoraes/Prescia/actions/runs/33580767190 "Prescia — PHP 8.3 compatibility workflow run"
[4]: https://github.com/leohmoraes/Prescia/actions/runs/33580767192 "Prescia — PHP static analysis workflow run"
[5]: https://github.com/leohmoraes/Prescia/issues/43 "Prescia Issue #43 — Atualizar PHPStan e elevar gradualmente o nível de análise"
[6]: https://github.com/leohmoraes/Prescia/actions/runs/33582848768 "Prescia — PHP static analysis workflow run for baseline review"
[7]: https://github.com/leohmoraes/Prescia/actions/runs/33704528222 "Prescia — PHP static analysis workflow run after Fase F2 cycle two"

> Este documento define o próximo lote de execução. Ele não substitui o relatório do CI nem autoriza elevar o PHPStan ao nível 2 antes dos critérios de aceite serem atingidos.


### Fase F2 — ciclo atual: encapsulamento de propriedades administrativas

A execução do ciclo atual começou pela execução `34006340306`, associada ao commit `c8ec096`. O relatório confirmou quatro violações de visibilidade em payloads de `bi_adm`: três acessos protegidos a `mod_bi_adm::$hasStats` e um acesso privado a `$hasUndo`. O carregador confirma que os payloads são incluídos pelo próprio módulo administrativo, mas o acesso direto a propriedades privadas ou protegidas continua incompatível com o contrato de encapsulamento.

A correção aplicada substitui os acessos diretos por `hasStatsModule()` e `hasUndoModule()`, métodos públicos de leitura que preservam o estado interno das propriedades. A validação focalizada de `prescia/plugins/bi_adm/payload/content/default.php` e `prescia/plugins/bi_adm/payload/content/index.php` terminou sem erros no PHPStan, e os três arquivos modificados passaram no `php -l`.

| Métrica do ciclo | Resultado inicial | Resultado após o primeiro patch |
|---|---:|---:|
| Acessos `property.protected` tratados | 3 | 0 nos payloads focalizados |
| Acessos `property.private` tratados | 1 | 0 nos payloads focalizados |
| Propriedades tornadas públicas | 0 | 0 |
| Métodos públicos de leitura adicionados | 0 | 2 |
| Entradas novas na baseline | 0 | 0 |
| Regressões de sintaxe nos arquivos tratados | — | 0 |

O restante do relatório inclui diagnósticos independentes no próprio `module.php`, como variáveis indefinidas e uma função interna, que serão tratados em lotes separados da Fase F1 ou F3. Não serão adicionadas propriedades genéricas nem supressões à baseline para silenciar esses diagnósticos.


### Resultado da Fase F2 — ciclo de visibilidade administrativa

A execução [PHP static analysis — execução 34006455888][9], associada ao commit `d4d58ab`, não apresentou ocorrências de `property.private`, `property.protected` ou `property.notFound` no relatório disponível. A execução correspondente de compatibilidade PHP 8.3, [34006455901][10], terminou com sucesso.

O ciclo corrigiu quatro acessos diretos em payloads administrativos sem tornar propriedades internas públicas. A solução adicionou dois métodos públicos de leitura em `mod_bi_adm`, preservando `hasStats` como `protected` e `hasUndo` como `private`. A validação focalizada dos dois payloads modificados terminou com zero erros no PHPStan, e os arquivos alterados passaram na verificação sintática do PHP 8.3.

Com o conjunto de violações de visibilidade estabilizado, a próxima etapa é a **Fase F3 — métodos ausentes ou incompatíveis**. O primeiro inventário deve separar `method.notFound`, `method.private`, `method.protected` e `method.callable` de erros de funções globais ausentes. A análise deve partir do relatório do commit `d4d58ab` e manter a baseline vazia.

[9]: https://github.com/leohmoraes/Prescia/actions/runs/34006455888 "Prescia — PHP static analysis after F2 visibility correction"
[10]: https://github.com/leohmoraes/Prescia/actions/runs/34006455901 "Prescia — PHP 8.3 compatibility after F2 visibility correction"


### Fase F3 — primeiro ciclo: métodos do núcleo no payload bi_stats

O relatório da execução `34006455888` apresentou três diagnósticos `method.notFound` no arquivo `prescia/plugins/bi_stats/payload/content/module.php`: `loadAllModules()`, `loaded('STATSDAILY')` e `loaded($selmod)`. O carregador de `mod_bi_stats::onShow()` inclui o payload no contexto do módulo, mas o núcleo é disponibilizado como `$this->parent` e como `$core`.

A investigação confirmou que os métodos reais pertencem a `CPrescia`: `loadAllmodules()` e `loaded(string $moduleName, bool $noRaise = false)`. O primeiro patch adicionou o contrato de `$core` e transferiu as três chamadas para o objeto correto. O payload passou no PHPStan focalizado, no `php -l` e no `git diff --check`.

| Diagnóstico | Contexto incorreto | Correção |
|---|---|---|
| `mod_bi_stats::loadAllModules()` | Método chamado no módulo | `$core->loadAllmodules()` |
| `mod_bi_stats::loaded('STATSDAILY')` | Método chamado no módulo | `$core->loaded('STATSDAILY')` |
| `mod_bi_stats::loaded($selmod)` | Método chamado no módulo | `$core->loaded($selmod)` |

O próximo inventário F3 deve tratar métodos ausentes em `bi_adm/module.php` e payloads restantes, separando chamadas ao núcleo, chamadas ao módulo concreto e símbolos globais não encontrados. Funções locais, classes ausentes e constantes continuarão em lotes separados quando não forem consequência direta do contexto do método.


### Fase F3 — segundo ciclo: capitalização de método em bi_adm

O inventário do relatório associado ao commit `5f61356` não apresentou novos `method.notFound` em `bi_adm/module.php`, mas a revisão do call site identificou a chamada `loadAllModules()` com capitalização divergente da declaração real `CPrescia::loadAllmodules()`. Embora PHP trate nomes de métodos sem diferenciação de maiúsculas e minúsculas em runtime, a forma divergente prejudicava a análise estática e o contrato documentado.

A chamada foi normalizada para `$this->parent->loadAllmodules()`. A análise focalizada do arquivo ainda reporta diagnósticos independentes de `variable.undefined` e `function.inner`, mas não reporta `method.notFound`, `method.private`, `method.protected` ou `method.callable`. Esses diagnósticos restantes não serão misturados à Fase F3.


### Fase F4 — primeiro ciclo: classe dinâmica CDBO_0

O relatório da execução `34040207476` apresentou cinco ocorrências de `class.notFound`, incluindo quatro instanciações de `CDBO_0`. A origem foi confirmada em `index.php` e `prescia/index.php`: o nome da classe é montado dinamicamente a partir de `CONS_AFF_DATABASECONNECTOR`, e o driver legado correspondente não existe no checkout como `prescia/lib/dbo/0.php`. A classe-base real `CDBO` está em `prescia/lib/dbo/cdbo.php`, mas não deve ser executada pelo bootstrap do PHPStan.

A correção criou `tools/phpstan-dynamic-classes.php`, carregado por `scanFiles`, com um contrato explícito para `CDBO` e `CDBO_0`, incluindo o construtor de cinco parâmetros usado pelo runtime. O stub geral não contém mais essas classes, evitando conflito entre declarações. Após limpar o cache do PHPStan, a análise focalizada de `index.php` deixou de emitir `class.notFound` e `new.noConstructor` para `CDBO_0`; permanecem apenas caminhos de configuração ausentes e variáveis indefinidas já pertencentes a outros lotes.

| Métrica F4 | Antes | Após o patch |
|---|---:|---:|
| Diagnósticos `class.notFound` para `CDBO_0` | 4 | 0 |
| Diagnósticos `new.noConstructor` para `CDBO_0` | 4 | 0 |
| Arquivos de produção carregados no bootstrap | 0 | 0 |
| Novas entradas na baseline | 0 | 0 |

O próximo símbolo F4 deve ser selecionado pela origem: funções com implementação real devem ser adicionadas ao `scanFiles` ou bootstrap seguro; funções internas de payload não devem receber stubs genéricos sem antes avaliar sua refatoração para closure; classes opcionais devem receber contratos específicos.


### Fase F5 — primeiro ciclo: padrões regex configuráveis

O relatório da execução `34040430960` apresentou dois diagnósticos `regexp.pattern` em `prescia/lazyload/botprotect.php`. As constantes de blacklist e whitelist podem permanecer vazias na configuração, mas eram enviadas diretamente a `preg_match()`, criando padrões vazios inválidos para o PHP 8.3/PHPStan.

A correção normaliza cada configuração para um padrão local. Quando a lista está vazia, usa-se `/(?!)/`, uma expressão válida que nunca corresponde; quando existe configuração, o padrão original é preservado. A análise focalizada eliminou ambos os diagnósticos `regexp.pattern`. Permanece apenas o diagnóstico independente de contexto `$this` no mesmo arquivo, pertencente aos lotes de contratos/variáveis e não à Fase F5.

| Diagnóstico | Resultado |
|---|---:|
| `regexp.pattern` em blacklist | 0 após a correção |
| `regexp.pattern` em whitelist | 0 após a correção |
| Comportamento com padrão configurado | preservado |
| Comportamento com configuração vazia | padrão seguro sem correspondência |
| Novas entradas na baseline | 0 |

O próximo ciclo F5 deve tratar caminhos ausentes de forma conservadora: arquivos de configuração e drivers opcionais não devem ser inventados no runtime; bibliotecas legadas sem uso devem ser excluídas da análise por caminho documentado, enquanto includes necessários devem receber caminhos existentes e verificáveis.


### Correção adicional — funções locais recursivas em bi_cms

A execução `34040527721` ainda apresentava sete diagnósticos `function.notFound` e cinco `function.inner`. Quatro de `function.notFound` e duas ocorrências de `function.inner` vinham dos dois blocos recursivos `removeNull()` em `prescia/plugins/bi_cms/module.php`. Como essas funções eram declaradas dentro de métodos e só eram usadas pelo respectivo fluxo de construção da árvore CMS, a correção adequada não era um stub global.

Os dois blocos foram convertidos para closures recursivas locais com `use (&$removeNull)`. O PHPStan focalizado passou a reportar somente o diagnóstico de contexto `$this` já conhecido; não restaram ocorrências de `function.inner` ou `function.notFound` relacionadas a `removeNull`. A sintaxe PHP 8.3 também foi aprovada.

Essa redução será acompanhada no próximo workflow completo, pois a execução que originou o diagnóstico foi anterior ao patch. A baseline permanece inalterada.


### Lote de propriedades — driver CDBO_mysqli

O relatório `34040527721` concentrava aproximadamente 87 ocorrências de `property.notFound` em `prescia/lib/dbo/mysqli.php`. A revisão mostrou que os campos (`connection`, `log`, `debugmode`, `delayedconn`, `dbc`, `dbt`, `quickmode`, `errorRaised` e outros) já pertencem à classe-base `CDBO`; o problema era a ausência dos arquivos reais do driver no conjunto `scanFiles` do PHPStan.

A configuração agora descobre `prescia/lib/dbo/cdbo.php` e `prescia/lib/dbo/mysqli.php`. O contrato dinâmico `CDBO_0` continua separado em `tools/phpstan-dynamic-classes.php` e passa a estender a classe-base real, sem duplicar propriedades. A análise focalizada dos dois drivers terminou com `[OK] No errors`, eliminando os diagnósticos de propriedade desse arquivo. Nenhuma entrada foi adicionada à baseline.

O próximo lote deve repetir essa classificação para os demais arquivos com `property.notFound`, distinguindo propriedades herdadas que precisam de descoberta estática de propriedades realmente ausentes em módulos e payloads.


### Lote B — primeiro ciclo: contexto e paginação do bi_bb index

A verificação completa do commit `0237763` contabilizou 595 diagnósticos `variable.undefined`, com alta concentração nos payloads do `bi_bb`. O primeiro arquivo tratado foi `prescia/plugins/bi_bb/payload/content/index.php`, que apresentava referências indefinidas a `$this` e `$core` por depender de variáveis injetadas pelo include do módulo.

Foram adicionados contratos PHPDoc explícitos para `CPrescia $core` e `mod_bi_bb $this`, refletindo o contexto confirmado em `mod_bi_bb::onRender()`. A revisão também encontrou um acesso funcionalmente incorreto a `$this->templateParams['ipp']`: `templateParams` pertence ao núcleo e é preparado pelo motor de módulos. A referência foi corrigida para `$core->templateParams['ipp']`.

O PHPStan focalizado agora retorna `[OK] No errors` para o payload. Não foram adicionadas entradas à baseline e nenhuma variável global fictícia foi criada.


### Lote B — segundo ciclo: contrato do lazyload friendlyurl

Após o payload `bi_bb/index.php`, o ranking atualizado apontou `prescia/lazyload/friendlyurl.php` com 32 ocorrências. A investigação confirmou que o arquivo é incluído pelo método `CPrescia::friendlyurl($param)`, portanto `$this` representa o núcleo e `$param` é o array de opções fornecido pelo chamador.

Foram adicionados contratos PHPDoc para `CPrescia $this` e `array<string, mixed> $param`. A análise focalizada retornou `[OK] No errors`, eliminando todas as ocorrências `variable.undefined` do arquivo sem inicializar artificialmente opções obrigatórias ou alterar a lógica de consulta.
