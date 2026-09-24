# Relatório da issue #240 — rejeição de CSRF ausente

## Escopo

A issue #240 solicita confirmar e proteger o contrato de rejeição de requisições mutáveis sem token CSRF, sem alterar o comportamento de requisições somente de leitura. O escopo deste lote não inclui rotação de tokens, tokens inválidos ou antigos, autenticação, sessão, upload ou rate limiting.

## Contrato confirmado

O ponto comum de entrada é `prescia/lib/main.php`: depois de iniciar a sessão, o bootstrap chama `presciaValidateCsrf()` antes de continuar o processamento da aplicação. A função `presciaRequestIsMutating()` classifica `POST`, `PUT`, `PATCH` e `DELETE` como mutáveis. Para esses métodos, a ausência simultânea do campo `csrf_token` e do header `X-CSRF-TOKEN` produz resposta HTTP 403 e encerra o fluxo. `GET` retorna sem exigir token. Um token válido no campo ou no header continua aceito.

A implementação de produção já estava presente no `master` desde o commit `1127c0e`; portanto, este lote não altera `prescia/lib/csrf.php` nem `prescia/lib/main.php`. A mudança é a evidência automatizada do comportamento público e da ordem do ponto de entrada.

## Regressões adicionadas

`tests/CsrfTest.php` agora executa um probe PHP isolado para confirmar a rejeição real em cada método mutável, incluindo ausência de campo e header, campos/header vazios, aceitação de `GET` sem token e aceitação de token válido. O teste também confirma estaticamente que a validação ocorre antes da continuação do bootstrap. O probe não registra tokens, sessão ou dados sensíveis.

Não há um fixture de mutação de banco no teste unitário atual; a garantia de ordem cobre o limite comum de entrada, enquanto a validação de cada endpoint mutável com banco representativo permanece dependente do ambiente de integração e fora deste lote.

## Validação

A validação local fica limitada por este sandbox: `php`, Composer, PHPUnit, PHPStan e Docker não estão instalados. Foram executados `git diff --check` e inspeção do diff; os testes PHPUnit, lint e PHPStan devem ser confirmados pelos workflows da PR. `phpstan-baseline.neon` não foi alterado.

## Próximo passo

A issue #239 permanece separada para cobertura de tokens inválidos e antigos. Não deve ser encerrada por este lote.
