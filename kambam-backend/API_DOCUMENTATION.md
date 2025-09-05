# API Kanban - Documentação

## Autenticação

O sistema utiliza tokens customizados com as seguintes características:
- **Access Token**: Expira em 1 hora
- **Refresh Token**: Expira em 14 dias
- **Header**: `Authorization: Bearer {access_token}`

## Endpoints

### Autenticação

#### POST /api/login
Login com email e senha.

**Request:**
```json
{
    "email": "joao@teste.com",
    "password": "123456"
}
```

**Response (200):**
```json
{
    "message": "Login realizado com sucesso",
    "user": {
        "id": 1,
        "name": "João Silva",
        "email": "joao@teste.com"
    },
    "tokens": {
        "access_token": "abc123...",
        "refresh_token": "def456...",
        "expires_at": "2024-01-01 15:00:00",
        "refresh_expires_at": "2024-01-15 15:00:00"
    }
}
```

#### POST /api/refresh
Renovar access token usando refresh token.

**Request:**
```json
{
    "refresh_token": "def456..."
}
```

**Response (200):**
```json
{
    "message": "Token renovado com sucesso",
    "tokens": {
        "access_token": "new_abc123...",
        "refresh_token": "def456...",
        "expires_at": "2024-01-01 16:00:00"
    }
}
```

#### POST /api/logout
Invalidar token atual.

**Headers:** `Authorization: Bearer {access_token}`

**Response (200):**
```json
{
    "message": "Logout realizado com sucesso"
}
```

#### GET /api/me
Obter informações do usuário autenticado.

**Headers:** `Authorization: Bearer {access_token}`

**Response (200):**
```json
{
    "user": {
        "id": 1,
        "name": "João Silva",
        "email": "joao@teste.com",
        "created_at": "2024-01-01 10:00:00",
        "updated_at": "2024-01-01 10:00:00"
    }
}
```

### Boards (Públicos)

#### GET /api/boards
Listar todos os boards com resumo.

**Response (200):**
```json
{
    "boards": [
        {
            "id": 1,
            "title": "Projeto Kanban",
            "description": "Board para gerenciar tarefas",
            "owner": {
                "id": 1,
                "name": "João Silva"
            },
            "columns_count": 3,
            "cards_count": 4,
            "created_at": "2024-01-01 10:00:00",
            "updated_at": "2024-01-01 10:00:00"
        }
    ]
}
```

#### GET /api/boards/{id}
Obter detalhes completos de um board.

**Response (200):**
```json
{
    "board": {
        "id": 1,
        "title": "Projeto Kanban",
        "description": "Board para gerenciar tarefas",
        "owner": {
            "id": 1,
            "name": "João Silva"
        },
        "columns": [
            {
                "id": 1,
                "name": "To Do",
                "order": 1,
                "wip_limit": 999,
                "cards_count": 2,
                "cards": [
                    {
                        "id": 1,
                        "title": "Implementar autenticação",
                        "description": "Criar sistema de login",
                        "position": 1,
                        "created_by": {
                            "id": 1,
                            "name": "João Silva"
                        },
                        "created_at": "2024-01-01 10:00:00",
                        "updated_at": "2024-01-01 10:00:00"
                    }
                ]
            }
        ],
        "created_at": "2024-01-01 10:00:00",
        "updated_at": "2024-01-01 10:00:00"
    }
}
```

### Boards (Privados - Owner Only)

#### POST /api/boards
Criar novo board.

**Headers:** `Authorization: Bearer {access_token}`

**Request:**
```json
{
    "title": "Novo Projeto",
    "description": "Descrição do projeto"
}
```

**Response (201):**
```json
{
    "message": "Board criado com sucesso",
    "board": {
        "id": 2,
        "title": "Novo Projeto",
        "description": "Descrição do projeto",
        "owner": {
            "id": 1,
            "name": "João Silva"
        },
        "columns": [
            {
                "id": 4,
                "name": "To Do",
                "order": 1,
                "wip_limit": 999
            },
            {
                "id": 5,
                "name": "Doing",
                "order": 2,
                "wip_limit": 999
            },
            {
                "id": 6,
                "name": "Done",
                "order": 3,
                "wip_limit": 999
            }
        ],
        "created_at": "2024-01-01 10:00:00",
        "updated_at": "2024-01-01 10:00:00"
    }
}
```

#### PATCH /api/boards/{id}
Editar board.

**Headers:** `Authorization: Bearer {access_token}`

**Request:**
```json
{
    "title": "Título Atualizado",
    "description": "Nova descrição"
}
```

#### DELETE /api/boards/{id}
Deletar board.

**Headers:** `Authorization: Bearer {access_token}`

### Colunas (Privadas - Owner Only)

#### POST /api/boards/{id}/columns
Adicionar coluna ao board.

**Headers:** `Authorization: Bearer {access_token}`

**Request:**
```json
{
    "name": "Nova Coluna",
    "wip_limit": 5
}
```

#### PATCH /api/columns/{id}
Editar coluna.

**Headers:** `Authorization: Bearer {access_token}`

**Request:**
```json
{
    "name": "Nome Atualizado",
    "wip_limit": 3
}
```

#### DELETE /api/columns/{id}
Deletar coluna.

**Headers:** `Authorization: Bearer {access_token}`

### Cards (Privados - Usuários Logados)

#### GET /api/cards/{id}
Obter detalhes de um card.

**Response (200):**
```json
{
    "card": {
        "id": 1,
        "title": "Implementar autenticação",
        "description": "Criar sistema de login",
        "position": 1,
        "board": {
            "id": 1,
            "title": "Projeto Kanban"
        },
        "column": {
            "id": 1,
            "name": "To Do"
        },
        "created_by": {
            "id": 1,
            "name": "João Silva"
        },
        "history": [
            {
                "id": 1,
                "type": "created",
                "from_column": null,
                "to_column": {
                    "id": 1,
                    "name": "To Do"
                },
                "user": {
                    "id": 1,
                    "name": "João Silva"
                },
                "at": "2024-01-01 10:00:00"
            }
        ],
        "created_at": "2024-01-01 10:00:00",
        "updated_at": "2024-01-01 10:00:00"
    }
}
```

#### POST /api/boards/{id}/cards
Criar card.

**Headers:** `Authorization: Bearer {access_token}`

**Request:**
```json
{
    "title": "Nova Tarefa",
    "description": "Descrição da tarefa",
    "column_id": 1
}
```

#### PATCH /api/cards/{id}
Editar ou mover card.

**Headers:** `Authorization: Bearer {access_token}`

**Request (editar):**
```json
{
    "title": "Título Atualizado",
    "description": "Nova descrição"
}
```

**Request (mover):**
```json
{
    "column_id": 2,
    "position": 1
}
```

#### DELETE /api/cards/{id}
Deletar card.

**Headers:** `Authorization: Bearer {access_token}`

## Códigos de Erro

- **400**: Dados inválidos
- **401**: Token ausente/expirado
- **403**: Ação restrita ao owner
- **404**: Recurso não encontrado
- **422**: Validação (incluindo WIP_LIMIT_REACHED)
- **500**: Erro interno do servidor

## Validações

### Board
- Título: obrigatório, 1-80 caracteres
- Descrição: opcional, máximo 500 caracteres

### Coluna
- Nome: obrigatório, 1-40 caracteres
- WIP Limit: obrigatório, >= 0

### Card
- Título: obrigatório, 1-120 caracteres
- Descrição: opcional, máximo 1000 caracteres

### WIP Limit
- Verificação automática antes de criar/mover cards
- Erro 422 com código `WIP_LIMIT_REACHED` quando limite é excedido

## Histórico Automático

O sistema registra automaticamente:
- Criação de cards
- Movimentação entre colunas
- Atualizações de cards
- Exclusão de cards

Cada entrada no histórico inclui:
- Tipo da ação
- Coluna de origem (se aplicável)
- Coluna de destino
- Usuário que executou a ação
- Timestamp da ação
