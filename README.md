# Flow

Plugin de workflow para tickets no GLPI, baseado em etapas, transicoes, acoes e validacoes.

## Documentos

- `USER_GUIDE.md`: guia de uso para configuracao dos fluxos
- `TECHNICAL_GUIDE.md`: referencia de arquitetura, entidades, hooks, tabelas e execucao

## Resumo funcional

Cada fluxo e associado a uma entidade e a uma categoria de ticket. Quando o ticket entra nesse escopo, o plugin controla sua etapa atual, executa actions, aplica validacoes e move o ticket conforme as transicoes configuradas.

## Conceitos principais

- `Flow`
- `Step`
- `Transition`
- `Action`
- `Validation`

## Tipos de etapa

- `Initial`
- `Common`
- `Condition`
- `Request`
- `End`

## Severidades de validacao

- `INFO`
- `WARNING`
- `BLOCKER`

