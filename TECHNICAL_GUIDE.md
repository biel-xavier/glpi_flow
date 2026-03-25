# Flow Technical Guide

## Arquitetura

- `setup.php`: registro do plugin
- `hook.php`: criacao de tabelas, seeds e hooks
- `front/api.php`: API do builder
- `inc/Service/FlowBuilderService.php`: persistencia do fluxo e metadados
- `inc/Service/FlowExecutionService.php`: execucao operacional
- `inc/Repository/FlowRepository.php`: acesso a dados
- `inc/Validations/*`: fabrica e validadores
- `web/src/*`: editor React

## Hooks

- `item_add` em `Ticket`
- `pre_item_update` em `Ticket`

## Tabelas

- `glpi_plugin_flow_action_types`
- `glpi_plugin_flow_validation_types`
- `glpi_plugin_flow_flows`
- `glpi_plugin_flow_steps`
- `glpi_plugin_flow_actions`
- `glpi_plugin_flow_validations`
- `glpi_plugin_flow_transitions`
- `glpi_plugin_flow_ticket_states`
- `glpi_plugin_flow_step_history`
- `glpi_plugin_flow_request_logs`

## Fluxo tecnico

Na criacao:

1. localizar fluxo por `entities_id` e `itilcategories_id`
2. iniciar estado do ticket
3. executar actions da etapa inicial
4. processar transicoes automaticas

Na atualizacao:

1. localizar etapa atual do ticket
2. carregar validacoes
3. instanciar validadores via `ValidationFactory`
4. bloquear gravacao se houver falha `BLOCKER`
5. mover etapa e executar actions se aplicavel

## Tipos semeados

Actions:

- `ADD_ACTOR`
- `CHANGE_STATUS`
- `ADD_SLA_TTO`
- `ADD_TASK_TEMPLATE`
- `REQUEST_VALIDATION`
- `ADD_TAG`
- `TRANSFER_FROM_QUERY`
- `REQUEST_VALIDATION_FROM_QUERY`
- `REQUEST_HTTP`

Validations:

- `FIELD_NOT_EMPTY`
- `QUERY_CHECK`
- `HTTP_RESPONSE_CHECK`

## Contratos de configuracao

- `flow`: nome, entidade, categoria, ativo
- `step`: nome, tipo
- `transition`: origem, destino, tipo
- `action`: tipo, configuracao JSON
- `validation`: tipo, severidade, configuracao JSON

## Builder

O `FlowBuilderService` tambem entrega metadados ao frontend:

- `csrf_token`
- action types
- validation types
- entidades
- categorias
- usuarios
- grupos
- task templates

## Riscos

- validacoes bloqueantes podem travar operacao de tickets
- requests HTTP podem falhar por dependencia externa
- transicoes recursivas exigem desenho cuidadoso

## Extensao

- novos tipos de action
- novos tipos de validation
- import/export mais formal com schema versionado

