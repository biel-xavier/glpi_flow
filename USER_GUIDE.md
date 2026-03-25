# Flow User Guide

## Objetivo

O `flow` permite modelar processos de atendimento com etapas controladas e regras automaticas sobre tickets.

## Conceitos

- `Flow`: processo completo
- `Step`: etapa do processo
- `Transition`: ligacao entre etapas
- `Action`: automacao executada ao entrar na etapa
- `Validation`: regra para bloquear ou orientar o fluxo

## Como configurar um fluxo

1. criar o fluxo
2. definir entidade e categoria de ticket
3. adicionar a etapa inicial
4. cadastrar etapas intermediarias e finais
5. criar transicoes entre as etapas
6. adicionar actions quando houver automacao
7. adicionar validations quando houver regra de negocio
8. salvar

## Tipos de etapa

- `Initial`: ponto de entrada
- `Common`: etapa comum, geralmente esperando interacao humana
- `Condition`: avalia validacoes e segue por caminho positivo ou negativo
- `Request`: etapa de requisicao ou integracao
- `End`: encerramento

## Tipos de transicao

- `default`
- `condition_positive`
- `condition_negative`

## Severidades de validacao

- `BLOCKER`: impede gravacao ou avanço
- `WARNING`: avisa sem bloquear
- `INFO`: mensagem informativa

## Actions disponiveis

- `ADD_ACTOR`
- `CHANGE_STATUS`
- `ADD_SLA_TTO`
- `ADD_TASK_TEMPLATE`
- `REQUEST_VALIDATION`
- `ADD_TAG`
- `TRANSFER_FROM_QUERY`
- `REQUEST_VALIDATION_FROM_QUERY`
- `REQUEST_HTTP`

## Validations disponiveis

- `FIELD_NOT_EMPTY`
- `QUERY_CHECK`
- `HTTP_RESPONSE_CHECK`

## Boas praticas

- mapear um fluxo por processo claro
- usar nomes de etapas objetivos
- reservar `BLOCKER` para regras realmente impeditivas
- testar o fluxo com tickets reais antes de publicar
- revisar transicoes de etapas `Condition` e `Request`

## Problemas comuns

- ticket nao entra no fluxo: validar entidade e categoria
- ticket nao salva: revisar validacoes `BLOCKER`
- transicao incorreta: revisar tipo da transicao e regra da etapa anterior

