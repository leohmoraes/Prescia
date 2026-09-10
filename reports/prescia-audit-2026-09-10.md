# Auditoria do Prescia — 10 de setembro de 2026

## Conclusão executiva

O repositório foi sincronizado com `origin/master` e não possui pull requests abertas. Existem **sete issues abertas**, todas correspondentes ao backlog de segurança e encapsulamento já conhecido; não foi identificada uma nova issue criada após o fechamento da issue #43. A elevação da análise estática para o **PHPStan nível 3** revelou **26 diagnósticos**, concentrados em sete arquivos e agrupados principalmente em contratos de arrays legados, validação de resultados de banco de dados e tipagem de parâmetros por referência.

A varredura de segurança não encontrou chamadas genéricas de `query()` ou `simpleQuery()` fora da camada de banco, execução de comandos do sistema ou uso de `eval()`. O cliente `loadURL()` aplica restrições de esquema, porta, DNS público, TLS, tamanho de resposta e ausência de redirecionamentos. A auditoria de dependências também não encontrou advisories conhecidos. Permanecem, entretanto, duas frentes prioritárias: a migração completa de SQL dinâmico acompanhada pela issue #94 e a revisão dos 114 arquivos legados do CKFinder acompanhada pela issue #67.

## Estado do repositório

| Item | Resultado |
|---|---|
| Branch analisada | `master` sincronizada com `origin/master` |
| Pull requests abertas | Nenhuma |
| Arquivos PHP em `prescia/` | 183 |
| PHPStan nível 3 | 26 diagnósticos |
| PHPUnit focalizado | 101 testes, 532 assertions, 0 falhas |
| Lint PHP | Aprovado nos arquivos analisados |
| Composer audit | Nenhum advisory conhecido |
| Arquivos com permissões graváveis por grupo/outros | Nenhum encontrado em `prescia`, `tools` e `tests` |

## Issues abertas

| Issue | Tema | Prioridade observada |
|---:|---|---|
| [#94][1] | Migrar leituras e escritas SQL genéricas restantes dos módulos | Alta; segurança |
| [#67][2] | Revisão completa dos 114 arquivos PHP legados do CKFinder | Segurança |
| [#66][3] | Monitoramento de tentativas de SSRF em runtime | Segurança |
| [#65][4] | Testes e mitigações adicionais contra DNS rebinding em `loadURL` | Segurança |
| [#42][5] | Encapsular funções globais de sanitização e arquivos | Média; segurança e PHP 8.3 |
| [#24][6] | Substituir sanitização HTML por escaping contextual e biblioteca segura | Média; segurança |
| [#9][7] | Substituir SQL concatenado por prepared statements | Alta; segurança |

Não há uma nova issue aberta além desse conjunto. A issue #43 foi resolvida e fechada após a eliminação dos diagnósticos de PHPStan nível 2.

## Diagnósticos PHPStan nível 3

| Arquivo | Quantidade | Classe do problema | Próxima ação sugerida |
|---|---:|---|---|
| `prescia/lib/dbo/mysqli.php` | 1 | Parâmetro `&$numrows` recebe união `int|string` | Normalizar o contador para `int` antes da chamada ou corrigir o contrato de saída do driver |
| `prescia/plugins/bi_adm/payload/content/preview.php` | 4 | Acesso a offset em valor que pode ser `false` | Guardar o resultado antes de acessar offsets e tratar falha de resolução de imagem |
| `prescia/plugins/bi_auth/authControl.php` | 3 | Array de sessão inferido como vazio sem a chave `id_user` | Validar a forma da sessão antes do uso e declarar o shape esperado |
| `prescia/plugins/bi_dev/module.php` | 1 | Offset `0` em array potencialmente vazio | Verificar resultado antes de acessar o primeiro elemento |
| `prescia/plugins/bi_stats/payload/content/stats_analytics.php` | 6 | Shapes de arrays incompletos (`hits`, `h`) | Declarar shapes completos e normalizar linhas de agregação SQL |
| `prescia/plugins/bi_stats/payload/content/stats_pathajax.php` | 6 | Shapes de arrays incompletos (`hits`) | Ajustar shapes e validar campos agregados antes da renderização |
| `prescia/plugins/bi_stats/payload/content/stats_ref.php` | 5 | Shapes de arrays incompletos (`hits`, `h`) | Centralizar shape das linhas de estatística e adicionar guards |
| **Total** | **26** |  |  |

A ordem técnica recomendada é iniciar pelo contrato do driver `mysqli`, seguir para os guards de `bi_adm`, `bi_auth` e `bi_dev`, e concluir com um shape compartilhado para as três telas de estatísticas. Essa ordem reduz diagnósticos estruturais antes de tratar os acessos repetidos aos arrays agregados.

## Varredura de segurança

### SQL e injeção

A busca por chamadas `->query()` e `->simpleQuery()` fora da camada de banco não encontrou resultados. As consultas remanescentes observadas utilizam predominantemente `queryPrepared()` e `fetchPrepared()`. A interpolação de nomes de tabela, coluna e cláusulas ainda aparece em módulos genéricos, estatísticas e undo, mas esses valores são derivados de metadados internos do módulo ou de listas construídas pelo framework. Mesmo quando não há entrada direta do usuário, esse padrão mantém uma superfície de manutenção arriscada e deve permanecer no escopo das issues #9 e #94.

O principal risco residual não é uma chamada SQL genérica isolada, mas a combinação entre SQL dinâmico legado, identificadores derivados de configuração e múltiplos caminhos de montagem de filtros. A próxima ação de segurança deve ser concluir uma auditoria por origem de cada identificador e aplicar whitelists centralizadas para tabelas, colunas, ordenação e limites.

### SSRF e rede

O `loadURL()` atualmente rejeita esquemas diferentes de HTTP/HTTPS, credenciais embutidas, portas não permitidas, hosts inválidos e endereços que não resolvem para IPs públicos. A conexão é aberta contra os IPs previamente validados, usa verificação TLS de peer e nome, não segue redirecionamentos e limita o tamanho da resposta. Esses controles cobrem o caminho principal e justificam a continuidade das issues #65 e #66 para testes de regressão, telemetria e defesa contra mudanças futuras.

Existe uma função FTP legada `fget()` em `prescia/lib/loadURL.php`, mas a varredura não encontrou chamadas no código do projeto. Ela ainda aceita um host sem passar pelo mesmo pipeline de validação do `loadURL()`. Recomenda-se desativá-la, removê-la ou protegê-la antes de qualquer reuso futuro.

### XSS, desserialização e execução de comandos

A busca não encontrou `exec()`, `system()`, `shell_exec()`, `passthru()`, `proc_open()`, `popen()` ou `eval()` no código analisado. A desserialização está concentrada em `prescia/lib/serialization.php` e usa `allowed_classes`, o que reduz o risco de instanciação arbitrária. Os sinks de template ainda exigem revisão contextual, especialmente nos fluxos administrativos e no CKFinder; essa frente permanece coberta pelas issues #24 e #67.

### Dependências e exposição de arquivos

`composer audit` retornou **“No security vulnerability advisories found.”** Não foram encontrados arquivos com permissões de escrita para grupo ou outros em `prescia`, `tools` e `tests`. A listagem contém configurações de exemplo e arquivos de configuração de páginas, que devem continuar fora de credenciais reais e ser protegidos pela configuração de exposição HTTP do projeto.

## Varredura de otimização

A busca identificou aproximadamente 196 referências aos helpers globais de arquivo e sanitização, indicando dívida relevante para a issue #42. Também foram encontrados vários pontos de SQL dentro de fluxos de módulos e loops de processamento, sobretudo em estatísticas, undo e administração. Esses pontos podem produzir padrão N+1 quando percorrem agregações ou entidades relacionadas; a confirmação deve ser feita com profiling de consultas em um ambiente de integração MySQL.

Os hotspots mais promissores são:

1. consultas por entidade dentro dos loops de agregação em `bi_stats`;
2. reconstruções de dados e consultas de relacionamento em `bi_undo`;
3. carregamento de conteúdo e referências no módulo administrativo;
4. chamadas repetidas de `date()` e consultas de contadores no fluxo de estatísticas;
5. usos globais de leitura, escrita e criação de diretórios que dificultam instrumentação e testes isolados.

A recomendação é não fazer micro-otimizações antes de medir. O próximo lote deve adicionar contadores ou logs de tempo de consulta nos testes de integração, identificar os três maiores consumidores e só então consolidar consultas ou introduzir cache com invalidação explícita.

## Próximo ciclo recomendado

O próximo ciclo deve começar por `prescia/lib/dbo/mysqli.php`, pois o contrato de contador por referência influencia vários diagnósticos e chamadas preparadas. Em seguida, devem ser corrigidos os quatro diagnósticos de `bi_adm`, os três de `bi_auth` e o diagnóstico de `bi_dev`. O último lote deve tratar os 17 diagnósticos de `bi_stats` com shapes explícitos e uma função comum de normalização de linhas agregadas.

Depois da elevação do PHPStan, cada PR deve executar PHPStan nível 3, PHPUnit completo, lint global, `composer audit` e os testes de regressão de segurança. As issues #94 e #67 devem ser tratadas como trilhas paralelas de segurança, sem misturar refatorações de tipagem com alterações de autorização, caminhos de arquivos ou montagem de SQL.

## Referências

[1]: https://github.com/leohmoraes/Prescia/issues/94 "Issue #94 — Migrar SQL genérico restante"
[2]: https://github.com/leohmoraes/Prescia/issues/67 "Issue #67 — Revisão completa do CKFinder"
[3]: https://github.com/leohmoraes/Prescia/issues/66 "Issue #66 — Monitoramento de SSRF"
[4]: https://github.com/leohmoraes/Prescia/issues/65 "Issue #65 — DNS rebinding em loadURL"
[5]: https://github.com/leohmoraes/Prescia/issues/42 "Issue #42 — Encapsulamento de funções globais"
[6]: https://github.com/leohmoraes/Prescia/issues/24 "Issue #24 — Sanitização HTML e escaping contextual"
[7]: https://github.com/leohmoraes/Prescia/issues/9 "Issue #9 — Prepared statements"
