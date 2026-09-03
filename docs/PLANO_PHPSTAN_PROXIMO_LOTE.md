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

> Este documento define o próximo lote de execução. Ele não substitui o relatório do CI nem autoriza elevar o PHPStan ao nível 2 antes dos critérios de aceite serem atingidos.
