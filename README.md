# Documentação do Plugin Flow para GLPI

O plugin **Flow** permite a criação de fluxos de trabalho (workflows) dinâmicos e automatizados dentro do GLPI, baseados em entidades e categorias de chamados.

## 1. Funcionamento do Plugin

O plugin funciona através de uma máquina de estados aplicada a cada ticket. Quando um ticket é criado ou atualizado, o plugin verifica se ele corresponde a um **Fluxo** configurado.

### Componentes Principais:

- **Fluxo (Flow)**: Vinculado a uma Entidade e uma Categoria de TI. Define o escopo de atuação.
- **Passos (Steps)**: Representam estados ou etapas dentro do fluxo.
- **Transições (Transitions)**: Definem o caminho entre um passo e outro. Podem ser automáticas ou manuais.
- **Ações (Actions)**: Tarefas executadas automaticamente ao entrar em um passo (ex: mudar status, adicionar observador).
- **Validações (Validations)**: Requisitos que devem ser satisfeitos para que a transição ocorra ou para definir o caminho em condições.

---

## 2. Tipos de Passos

Os passos definem o comportamento do fluxo em cada etapa:

- **Initial (Inicial)**: O ponto de entrada do fluxo. É criado automaticamente quando o ticket casa com os critérios do fluxo.
- **Common (Comum)**: Passos intermediários que geralmente aguardam uma interação humana (atualização do chamado) para disparar as validações e seguir para a próxima transição.
- **Condition (Condição)**: Passos de decisão automática. Ao chegar neste passo, o sistema avalia as validações vinculadas:
  - Se passarem -> segue pela transição `condition_positive`.
  - Se falharem -> segue pela transição `condition_negative`.
- **Request (Requisição)**: Passo específico para integrações via HTTP. Executa uma requisição (Action) e valida a resposta (Validation) imediatamente, decidindo o fluxo com base no código HTTP retornado.

- **End (Fim)**: Representa a conclusão do fluxo. Não possui transições de saída.

---

## 3. Tipos de Ações

As ações são executadas na ordem definida assim que o ticket entra em um novo passo.

| Ação                    | Descrição                                        | Configuração Principal                 |
| :---------------------- | :----------------------------------------------- | :------------------------------------- |
| **ADD_ACTOR**           | Adiciona requerentes, observadores ou técnicos.  | Tipo de ator e ID do usuário/grupo.    |
| **CHANGE_STATUS**       | Altera o status do chamado automaticamente.      | Novo status (ID do GLPI).              |
| **ADD_SLA_TTO**         | Vincula um SLA de tempo de resposta.             | ID do SLA.                             |
| **ADD_TASK_TEMPLATE**   | Insere uma tarefa baseada em um modelo.          | ID do modelo de tarefa.                |
| **REQUEST_VALIDATION**  | Solicita aprovação para um usuário ou grupo.     | Usuário/Grupo e comentário.            |
| **ADD_TAG**             | Adiciona uma etiqueta (tag) ao chamado.          | ID da etiqueta.                        |
| **TRANSFER_FROM_QUERY** | Transfere o chamado baseado em uma consulta SQL. | Tabela, campo e mapeamento de valores. |
| **REQ_VAL_FROM_QUERY**  | Solicita aprovação baseada em consulta SQL.      | Tabela e critérios de busca.           |
| **REQUEST_HTTP**        | Envia requisição HTTP (GET, POST, etc).          | URL, Método, Headers, Body (JSON).     |

---

## 4. Tipos de Validações

Validações garantem a integridade do processo ou decidem caminhos.

- **FIELD_NOT_EMPTY**: Verifica se um campo específico do chamado (físico ou de formulário) está preenchido.
- **QUERY_CHECK (Consulta Avançada)**: Permite verificar valores diretamente no banco de dados.
- **HTTP_RESPONSE_CHECK**: Valida o código de resposta HTTP da última ação `REQUEST_HTTP`. Essencial para o passo do tipo **Requisição**.

### Severidade das Validações:

- **BLOCKER**: Impede a atualização do chamado e o avanço do fluxo até que seja satisfeito.
- **WARNING/INFO**: Apenas informativo (usado principalmente em passos de condição para decidir o caminho).

---

## 5. Fluxo de Transição

1. **Trigger Manual**: Em passos `Common`, o usuário precisa clicar em "Salvar" no chamado.
2. **Trigger Automático**: Em passos `Condition` e `Request`, o sistema avalia as validações automaticamente (ex: resposta da API) e transita para o próximo passo (Sucesso/Falha).

## 6. Instalação e Build (Frontend)

O plugin possui uma interface React no diretório `web/`.
Caso faça alterações no frontend:

1. Acesse `web/`.
2. Execute `npm install`.
3. Execute `npm run build`.
4. Os arquivos serão gerados em `web/dist` e copiados para `glpi/public/flow`.
