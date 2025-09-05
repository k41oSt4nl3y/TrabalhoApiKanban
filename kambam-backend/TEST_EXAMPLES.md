# Exemplos de Teste da API Kanban

## Configuração Inicial

1. Execute as migrations:
```bash
php artisan migrate
```

2. Execute o seeder de dados de teste:
```bash
php artisan db:seed --class=TestDataSeeder
```

## Testando com cURL

### 1. Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "joao@teste.com",
    "password": "123456"
  }'
```

**Resposta esperada:**
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

### 2. Listar Boards (Público)
```bash
curl -X GET http://localhost:8000/api/boards
```

### 3. Obter Detalhes do Board (Público)
```bash
curl -X GET http://localhost:8000/api/boards/1
```

### 4. Criar Novo Board (Privado)
```bash
curl -X POST http://localhost:8000/api/boards \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_ACCESS_TOKEN" \
  -d '{
    "title": "Meu Novo Projeto",
    "description": "Descrição do projeto"
  }'
```

### 5. Adicionar Coluna (Privado - Owner Only)
```bash
curl -X POST http://localhost:8000/api/boards/1/columns \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_ACCESS_TOKEN" \
  -d '{
    "name": "Review",
    "wip_limit": 2
  }'
```

### 6. Criar Card (Privado)
```bash
curl -X POST http://localhost:8000/api/boards/1/cards \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_ACCESS_TOKEN" \
  -d '{
    "title": "Nova Tarefa",
    "description": "Descrição da tarefa",
    "column_id": 1
  }'
```

### 7. Mover Card (Privado)
```bash
curl -X PATCH http://localhost:8000/api/cards/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_ACCESS_TOKEN" \
  -d '{
    "column_id": 2,
    "position": 1
  }'
```

### 8. Testar WIP Limit
```bash
# Tentar criar card em coluna com WIP limit atingido
curl -X POST http://localhost:8000/api/boards/1/cards \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_ACCESS_TOKEN" \
  -d '{
    "title": "Card que deve falhar",
    "description": "Este card não deve ser criado",
    "column_id": 2
  }'
```

**Resposta esperada (422):**
```json
{
    "error": "WIP_LIMIT_REACHED",
    "message": "A coluna 'Doing' atingiu o limite de WIP (3)"
}
```

### 9. Obter Histórico do Card
```bash
curl -X GET http://localhost:8000/api/cards/1
```

### 10. Refresh Token
```bash
curl -X POST http://localhost:8000/api/refresh \
  -H "Content-Type: application/json" \
  -d '{
    "refresh_token": "SEU_REFRESH_TOKEN"
  }'
```

### 11. Logout
```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer SEU_ACCESS_TOKEN"
```

## Testando com Postman

### Collection JSON
```json
{
    "info": {
        "name": "Kanban API",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "item": [
        {
            "name": "Login",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Content-Type",
                        "value": "application/json"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n    \"email\": \"joao@teste.com\",\n    \"password\": \"123456\"\n}"
                },
                "url": {
                    "raw": "{{base_url}}/api/login",
                    "host": ["{{base_url}}"],
                    "path": ["api", "login"]
                }
            }
        },
        {
            "name": "Get Boards",
            "request": {
                "method": "GET",
                "url": {
                    "raw": "{{base_url}}/api/boards",
                    "host": ["{{base_url}}"],
                    "path": ["api", "boards"]
                }
            }
        },
        {
            "name": "Create Board",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Content-Type",
                        "value": "application/json"
                    },
                    {
                        "key": "Authorization",
                        "value": "Bearer {{access_token}}"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n    \"title\": \"Novo Projeto\",\n    \"description\": \"Descrição do projeto\"\n}"
                },
                "url": {
                    "raw": "{{base_url}}/api/boards",
                    "host": ["{{base_url}}"],
                    "path": ["api", "boards"]
                }
            }
        }
    ],
    "variable": [
        {
            "key": "base_url",
            "value": "http://localhost:8000"
        },
        {
            "key": "access_token",
            "value": ""
        }
    ]
}
```

## Cenários de Teste

### 1. Fluxo Completo de Autenticação
1. Fazer login
2. Usar access_token em requisições protegidas
3. Quando token expirar, usar refresh_token
4. Fazer logout

### 2. Gerenciamento de Board
1. Criar board
2. Verificar se colunas padrão foram criadas
3. Editar título/descrição
4. Deletar board

### 3. Gerenciamento de Colunas
1. Adicionar nova coluna
2. Editar nome e WIP limit
3. Tentar deletar coluna com cards (deve falhar)
4. Deletar coluna vazia

### 4. Gerenciamento de Cards
1. Criar card
2. Mover entre colunas
3. Editar título/descrição
4. Verificar histórico
5. Deletar card

### 5. Validação de WIP Limit
1. Definir WIP limit baixo em coluna
2. Criar cards até atingir limite
3. Tentar criar mais um card (deve falhar)
4. Tentar mover card para coluna com limite atingido (deve falhar)

### 6. Testes de Autorização
1. Tentar editar board de outro usuário (deve falhar)
2. Tentar adicionar coluna em board de outro usuário (deve falhar)
3. Verificar se usuários podem criar cards em qualquer board

## Dados de Teste Disponíveis

Após executar o seeder, você terá:

**Usuários:**
- joao@teste.com / 123456 (João Silva)
- maria@teste.com / 123456 (Maria Santos)

**Boards:**
- "Projeto Kanban" (João Silva)
- "Projeto Frontend" (Maria Santos)

**Cards de exemplo:**
- "Implementar autenticação" (To Do)
- "Criar CRUD de boards" (To Do)
- "Implementar validação WIP" (Doing)
- "Configurar banco de dados" (Done)
