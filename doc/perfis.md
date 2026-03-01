# Perfis de Usuário

O sistema CallCenter possui **dois perfis** de usuário, cada um com permissões e funcionalidades específicas.

---

## 📋 Resumo Executivo

| Recurso | Admin | Atendente |
|---------|-------|-----------|
| **Gerenciar Usuários** | ✅ CRUD completo (criar, editar, excluir, ativar/bloquear, alterar perfil) | ❌ Acesso próprio apenas (visualizar perfil) |
| **Gerenciar Empresas** | ✅ CRUD completo (criar, editar, excluir) | ❌ Apenas leitura |
| **Filtro de Urgência** | ✅ CRUD keywords, tester, análise | ❌ Apenas leitura |
| **Painel de Atendimento** | ✅ Acesso completo | ✅ Acesso completo |
| **Visualizar Tudo** | ✅ Todos usuários, empresas, histórico | ✅ Apenas seus atendimentos |
| **Configurações** | ✅ Acesso completo | ❌ Sem acesso |

---

## 👤 Perfil: ADMIN

**Descrição**: Administrador do sistema. Gerencia configurações, usuários e empresas clientes. Tem acesso completo a todas as funcionalidades.

### Permissões Detalhadas

#### 🏢 Gestão de Empresas
- ✅ Listar todas as empresas
- ✅ Criar nova empresa
- ✅ Editar dados da empresa (nome, email, API key, keywords personalizadas)
- ✅ Excluir empresa
- ✅ Visualizar histórico de atendimentos por empresa
- ✅ Acessar canais configurados (WhatsApp, VOIP)

#### 👥 Gestão de Usuários
- ✅ Listar todos os usuários
- ✅ Criar novo usuário (definir nome, email, senha, perfil, status)
- ✅ Editar dados de qualquer usuário (nome, email, perfil, status de ativo/bloqueado)
- ✅ Deletar usuário
- ✅ **Tornar Atendente** → Admin → converter para atendente
- ✅ **Tornar Admin** → Atendente → promover para admin
- ✅ **Bloquear/Ativar** usuário (desativar temporariamente sem deletar)
- ✅ Visualizar último acesso de cada usuário

#### 🔑 Filtro de Urgência
- ✅ Listar todas as keywords de urgência (padrão + customizadas por empresa)
- ✅ Criar nova keyword
- ✅ Editar keyword (texto, peso, expressão regular)
- ✅ Deletar keyword
- ✅ Acessar ferramenta de teste (analisa um texto e mostra se é urgente)
- ✅ Visualizar estatísticas de keywords usadas
- ✅ Configurar keywords padrão globais

#### 📊 Painel e Relatórios
- ✅ Acesso ao painel de atendimento (como atendente)
- ✅ Visualizar todos os atendimentos de todas as empresas
- ✅ Acessar relatórios gerenciais (volume, tempo médio, taxas de resolução)
- ✅ Exportar dados para análise

#### ⚙️ Configurações do Sistema
- ✅ Acessar configurações gerais
- ✅ Definir valores padrão (timeouts, limites, templates)
- ✅ Visualizar logs de atividade do sistema

### Navegação (Frontend)

Menus visíveis para Admin:

```
CallCenter (logo)
├── Dashboard
├── Empresas          ← Admin only
├── Keywords         ← Admin only
└── Usuários         ← Admin only

[Seu Nome] [Admin]  [Sair]
```

### Endpoints (Backend)

Admin pode acessar todos os endpoints com prefixo `/api/v1/`:

```
POST   /auth/login               ✅ (qualquer um)
POST   /auth/logout              ✅
GET    /auth/me                  ✅

GET    /companies                ✅ ADMIN
POST   /companies                ✅ ADMIN
GET    /companies/:id            ✅ ADMIN
PUT    /companies/:id            ✅ ADMIN
DELETE /companies/:id            ✅ ADMIN

GET    /users                    ✅ ADMIN
POST   /users                    ✅ ADMIN
GET    /users/:id                ✅ ADMIN
PUT    /users/:id                ✅ ADMIN
DELETE /users/:id                ✅ ADMIN
PATCH  /users/:id/toggle-active  ✅ ADMIN
PATCH  /users/:id/set-role       ✅ ADMIN

GET    /urgency-keywords         ✅ ADMIN
POST   /urgency-keywords         ✅ ADMIN
GET    /urgency-keywords/:id     ✅ ADMIN
PUT    /urgency-keywords/:id     ✅ ADMIN
DELETE /urgency-keywords/:id     ✅ ADMIN
POST   /urgency-keywords/test    ✅ ADMIN + ATTENDANT
POST   /urgency-keywords/analyze ✅ ADMIN + ATTENDANT
```

---

## 🎧 Perfil: ATENDENTE

**Descrição**: Operador do callcenter. Acessa o painel para atender solicitações de cliente. Visualiza apenas informações públicas do sistema.

### Permissões Detalhadas

#### 🏢 Gestão de Empresas
- ❌ **NÃO pode** listar/criar/editar/deletar empresas
- ✅ Vê nome da empresa no contexto de um atendimento (informativo)

#### 👥 Gestão de Usuários
- ❌ **NÃO pode** listar/criar/editar/deletar usuários
- ✅ Pode visualizar seu próprio perfil (`GET /auth/me`)
- ✅ Pode editar sua própria senha (implementado em próxima fase)

#### 🔑 Filtro de Urgência
- ❌ **NÃO pode** listar/criar/editar/deletar keywords
- ✅ Pode **testar** uma mensagem contra as keywords (`POST /urgency-keywords/test`)
  - Exemplo: "pessoa presa" → resposta: `{ urgency: true, keywords: ['preso'], weight: 10 }`
- ✅ Pode **analisar** texto completo (`POST /urgency-keywords/analyze`)
  - Retorna análise de sentenças urgentes encontradas

#### 📊 Painel e Relatórios
- ✅ Acesso ao painel de atendimento (fila completa)
- ✅ Assumir um atendimento (setor de seu interesse)
- ✅ Enviar mensagens via channel (WhatsApp, VOIP, etc.)
- ✅ Confirmar/registrar abertura de chamado
- ✅ Visualizar histórico de seus atendimentos (últimas 30 dias)
- ❌ Relatórios gerenciais (agregados, volumes, estatísticas) — apenas admin

#### ⚙️ Configurações do Sistema
- ❌ **NÃO tem acesso** a configurações
- ✅ Pode ver status do sistema (online/offline de canais)

### Navegação (Frontend)

Menus visíveis para Atendente:

```
CallCenter (logo)
├── Dashboard       ← único menu

[Seu Nome] [Atendente]  [Sair]
```

Tentativa de acessar rotas admin (ex: `/companies`, `/users`, `/urgency-keywords`) resulta em redirecionamento para `/dashboard`.

### Endpoints (Backend)

Atendente acessa apenas endpoints de atendimento e testes:

```
POST   /auth/login               ✅ (qualquer um)
POST   /auth/logout              ✅
GET    /auth/me                  ✅

GET    /companies                ❌ 403 Forbidden
POST   /companies                ❌ 403 Forbidden
GET    /companies/:id            ❌ 403 Forbidden
PUT    /companies/:id            ❌ 403 Forbidden
DELETE /companies/:id            ❌ 403 Forbidden

GET    /users                    ❌ 403 Forbidden
POST   /users                    ❌ 403 Forbidden
GET    /users/:id                ❌ 403 Forbidden
PUT    /users/:id                ❌ 403 Forbidden
DELETE /users/:id                ❌ 403 Forbidden
PATCH  /users/:id/toggle-active  ❌ 403 Forbidden
PATCH  /users/:id/set-role       ❌ 403 Forbidden

GET    /urgency-keywords         ❌ 403 Forbidden
POST   /urgency-keywords         ❌ 403 Forbidden
GET    /urgency-keywords/:id     ❌ 403 Forbidden
PUT    /urgency-keywords/:id     ❌ 403 Forbidden
DELETE /urgency-keywords/:id     ❌ 403 Forbidden
POST   /urgency-keywords/test    ✅ TODOS
POST   /urgency-keywords/analyze ✅ TODOS
```

---

## 🔒 Implementação Técnica

### Backend (Laravel)

#### Campo `role` na tabela `users`

```sql
ALTER TABLE users ADD COLUMN role ENUM('admin', 'attendant') DEFAULT 'attendant';
```

#### Modelo `User`

```php
class User extends Authenticatable
{
    const ROLE_ADMIN = 'admin';
    const ROLE_ATTENDANT = 'attendant';

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isAttendant(): bool
    {
        return $this->role === self::ROLE_ATTENDANT;
    }
}
```

#### Middleware de Proteção

`RequireAdmin` — valida se usuário é admin antes de executar ação:

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'user.active', 'role.admin'])->group(function () {
    Route::apiResource('companies', CompanyController::class);
    Route::apiResource('users', UserController::class);
    Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);
    Route::patch('/users/{user}/set-role', [UserController::class, 'setRole']);
    // ...
});
```

#### Controlador de Usuários

```php
class UserController extends Controller
{
    // Protege rotas listadas acima com middleware
    // Impede auto-exclusão e auto-desativação
    public function destroy(User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'Não pode excluir a si mesmo');
        $user->delete();
    }

    public function toggleActive(User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'Não pode desativar a si mesmo');
        $user->update(['is_active' => !$user->is_active]);
    }

    public function setRole(User $user, Request $request)
    {
        abort_if($user->id === auth()->id(), 403, 'Não pode alterar seu próprio perfil');
        $user->update(['role' => $request->role]);
    }
}
```

### Frontend (Vue 3 + TypeScript)

#### Computed Property no AuthStore

```typescript
// stores/authStore.ts
const isAdmin = computed(() => user.value?.role === 'admin')
const isAttendant = computed(() => user.value?.role === 'attendant')
```

#### Route Guard

```typescript
// router/index.ts
router.beforeEach((to, from, next) => {
    if (to.meta.requiresAdmin && !authStore.isAdmin) {
        next({ name: 'dashboard' })
    }
})
```

#### Navegação Condicional

```vue
<!-- App.vue -->
<template v-if="isAdmin">
    <RouterLink to="/companies">Empresas</RouterLink>
    <RouterLink to="/urgency-keywords">Keywords</RouterLink>
    <RouterLink to="/users">Usuários</RouterLink>
</template>
```

---

## 🚀 Fluxo de Onboarding

### Primeira inicialização do sistema

1. **Banco de dados é criado** → migração cria usuário admin padrão
   - Email: `admin@callcenter.local`
   - Senha: `Admin@123` (DEVE SER ALTERADA na primeira usar padrão é `Admin@123`)

2. **Admin faz login** → acesso completo

3. **Admin cria outras contas:**
   - Novos admins (promoção interna)
   - Atendentes (operadores do callcenter)

4. **Admin bloqueia/desativa** contas conforme necessário

---

## 📝 Responsabilidades por Perfil

| Atividade | Admin | Atendente |
|-----------|-------|-----------|
| Responder atendimentos | ✅ Sim (opcionalmente) | ✅ Sim (função principal) |
| Configurar sistema | ✅ Sim | ❌ Não |
| Adicionar empresa cliente | ✅ Sim | ❌ Não |
| Adicionar usuário | ✅ Sim | ❌ Não |
| Testar keywords | ✅ Sim | ✅ Sim |
| Acessar painel | ✅ Sim | ✅ Sim |
| Ver dados de outras empresas | ✅ Sim (por design) | ✅ Sim (no contexto de atendimento) |
| Gerar relatórios | ✅ Sim | ❌ Não (próxima fase) |

---

## 🔐 Segurança e Boas Práticas

1. **Nunca compartilhe credenciais** — admin recebe senha padrão, deve alterar na primeira login
2. **Audit log** — toda ação de admin é registrada (CRUD de usuários, roles alteradas, etc.)
3. **Proteção contra auto-exclusão** — admin não consegue se deletar ou desativar
4. **Token expiration** — tokens Sanctum expiram após 8 horas de inatividade
5. **Ativação/Bloqueio** — atendente bloqueado perde o token automaticamente

---

## 🗓️ Próximas Fases

- [ ] Supervisor: perfil intermediário (ver relatórios, reatribuir atendimentos, mas não gerenciar config)
- [ ] Permissões granulares: usar `spatie/laravel-permission` para controle por feature
- [ ] Auditoria completa: registrar quem fez o quê e quando
- [ ] Autossair (logout automático): após 8h de inatividade
- [ ] 2FA: autenticação de dois fatores para admins
