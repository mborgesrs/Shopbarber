# Planejamento de Migração para Modelo SaaS (Multi-Tenancy)

Este documento descreve as etapas necessárias para transformar o ShopBarber em uma plataforma SaaS (Software as a Service) capaz de atender múltiplas barbearias simultaneamente de forma segura e isolada.

## 1. Estratégia de Banco de Dados

Utilizaremos a estratégia de **Base de Dados Compartilhada com Coluna Discriminadora** (Shared Database, Shared Schema). Esta é a abordagem mais eficiente para startups e sistemas de médio porte, pois facilita a manutenção e backups.

### Alterações Necessárias no Schema (SQL):

1.  **Nova Tabela `companies` (Empresas)**
    *   Armazena os dados da barbearia assinante.
    *   Colunas: `id`, `name`, `fantasy_name`, `document` (CNPJ), `plan_id` (plano contratado), `status` (ativo/inativo), `created_at`.

2.  **Alteração na Tabela `users`**
    *   Adicionar coluna `company_id` (Foreign Key para `companies`).
    *   Adicionar coluna `role` (superadmin, admin, funcionário) para distinguir o dono da plataforma dos donos das barbearias.

3.  **Alteração em TODAS as Tabelas de Negócio**
    *   Tabelas afetadas: `clients`, `professionals`, `products` (serviços), `quotes` (agendamentos), `finance`.
    *   Adicionar coluna `company_id` em todas elas.
    *   Criar índices compostos (ex: `INDEX(company_id, email)` em clientes) para performance.

## 2. Autenticação e Sessão

O sistema de login precisará identificar não apenas o usuário, mas a qual empresa ele pertence.

*   **Login**: Ao logar, o sistema carrega o `company_id` do usuário na Sessão PHP (`$_SESSION['company_id']`).
*   **Isolamento**: Todo o sistema deve confiar *apenas* na sessão para saber qual empresa está acessando. Nunca confiar em IDs passados via URL sem validar se pertencem à empresa da sessão.

## 3. Alterações no Código (Backend)

Esta é a parte mais crítica. Precisamos garantir que **nenhuma** query SQL vaze dados de uma barbearia para outra.

### Regra de Ouro: Escopo Global
Todas as consultas `SELECT`, `UPDATE`, `DELETE` e `INSERT` devem incluir o `company_id`.

**Exemplo de Refatoração:**

*   *Antes (Atual):*
    ```php
    $pdo->query("SELECT * FROM clients");
    ```
*   *Depois (SaaS):*
    ```php
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE company_id = ?");
    $stmt->execute([$_SESSION['company_id']]);
    ```

### Sugestão de Arquitetura
Para evitar mexer em todos os arquivos repetidamente e reduzir risco de erro humano, recomenda-se criar uma classe `TenantScope` ou funções helpers:

```php
function getCompanyId() {
    return $_SESSION['company_id'];
}

// Exemplo de uso
$products = $db->fetchAll("SELECT * FROM products WHERE company_id = ?", [getCompanyId()]);
```

## 4. Cadastro de Novas Empresas (Onboarding)

Será necessário criar um fluxo de registro público:
1.  Página de "Cadastre sua Barbearia".
2.  Cria a empresa na tabela `companies`.
3.  Cria o usuário administrador na tabela `users` vinculado a essa empresa.
4.  (Opcional) Cria dados de exemplo (um serviço "Corte", um profissional padrão) para o novo usuário não começar com o sistema vazio.

## 5. Super Admin (Backoffice)

Você precisará de uma área onde você (dono do software) possa:
*   Ver todas as barbearias cadastradas.
*   Bloquear uma barbearia inadimplente (setar `status = 'inactive'`).
*   Ver estatísticas globais.

## 6. Passos para Execução da Migração

1.  **Backup**: Faça backup completo do banco e código atual.
2.  **Migration SQL**: Crie e rode o script SQL para adicionar tabela `companies` e colunas `company_id`.
3.  **Seed**: Crie uma "Empresa Padrão" no banco e associe todos os dados existentes (clientes, agendamentos atuais) a ela Id 1. Assim o sistema atual continua funcionando para o primeiro cliente.
4.  **Backend Refactor**: Atualize arquivo por arquivo adicionando a cláusula `WHERE company_id = ?`.
    *   Comece pelo Login (para setar a sessão).
    *   Vá módulo por módulo (Clientes, Profissionais, etc.).
5.  **Teste de Isolamento**: Crie uma segunda empresa manualmente e tente acessar dados da empresa 1 estando logado na 2. Se não retornar nada, está seguro.

---
**Observação sobre o Frontend/Responsividade:**
A responsividade (Mobile) já foi iniciada com o novo Menu Lateral implementado hoje. O sistema já está visualmente adaptável, permitindo o uso em celulares por donos de barbearias e profissionais.
