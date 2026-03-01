# Segurança

## Contexto

O sistema gerencia dados de ~70 empresas concorrentes no mesmo banco. Embora não seja multi-tenant (uma única empresa opera o callcenter), os dados dos clientes devem ser protegidos contra acessos indevidos, vazamentos e comprometimento de credenciais.

---

## Decisão: Sem Row-Level Security (RLS)

### Por que NÃO usar RLS?

| Fator | Análise |
|---|---|
| **Modelo de acesso** | Todos os atendentes atendem todas as empresas — não há isolamento entre operadores |
| **Single-tenant** | Existe apenas um "tenant": a empresa do callcenter. Empresas clientes são dados, não tenants |
| **Complexidade** | RLS adiciona camada de políticas no banco que dificulta debugging e manutenção |
| **Performance** | Queries sem RLS são mais simples e previsíveis |
| **Benefício real** | Nenhum — RLS protege tenant A de acessar dados do tenant B, mas aqui há apenas um tenant |

### O que fazer em vez de RLS

- **Scoping consistente**: todas as queries que filtram por empresa usam `->where('company_id', $companyId)` via Eloquent scopes
- **Testes automatizados**: verificar que endpoints sempre filtram corretamente
- **Code review**: garantir que nenhuma query vaza dados entre empresas involuntariamente

```php
// Model scope padrão
class ServiceRequest extends Model
{
    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
```

---

## Camadas de Segurança

### 1. Autenticação

| Aspecto | Implementação |
|---|---|
| **Método** | Laravel Sanctum (SPA authentication) |
| **Token** | Cookie httpOnly + CSRF token |
| **Sessão** | Armazenada no Redis (não no filesystem) |
| **Expiração** | Configurável (padrão: 8h de inatividade) |
| **Refresh** | Token renovado automaticamente durante uso |

### 2. Autorização (Roles e Permissões)

O sistema implementa **dois perfis de usuário** com permissões diferenciadas:

| Recurso | Admin | Atendente |
|---------|-------|-----------|
| Gerenciar Usuários | ✅ CRUD completo | ❌ Acesso próprio apenas |
| Gerenciar Empresas | ✅ CRUD completo | ❌ Apenas leitura |
| Filtro de Urgência | ✅ CRUD keywords | ✅ Apenas teste/análise |
| Painel de Atendimento | ✅ Acesso completo | ✅ Acesso completo |
| Configurações | ✅ Acesso completo | ❌ Sem acesso |

**Implementação:**

```
Admin
├── CRUD empresas (criar, editar, deletar)
├── CRUD usuários (criar, editar, deletar, promover/revogar admin, bloquear/ativar)
├── CRUD keywords de urgência
├── Configurações do sistema
└── Painel completo (todas as empresas)

Atendente
├── Painel de atendimento (fila, assumir atendimentos)
├── Testar/analisar keywords
└── Visualizar dados públicos do sistema
```

**Detalhes completos em [Perfis de Usuário](perfis.md).**

**Implementado com:**
- Laravel Sanctum (autenticação por token)
- Middleware `RequireAdmin` para proteção de rotas
- Middleware `RequireActiveUser` para bloquear usuários inativos
- Guards no router Vue.js (`requiresAdmin` meta)

### 3. Criptografia de Credenciais de API

As chaves de API que cada empresa fornece para integração são **críticas**:

| Regra | Detalhe |
|---|---|
| Armazenamento | Cifradas no banco com AES-256-CBC |
| Exibição | Nunca exibidas em texto legível após cadastro |
| Acesso | Apenas o sistema usa (para enviar chamados) |
| Logs | Nunca aparecem em logs do sistema |
| Frontend | Nunca enviadas para o frontend |
| Atendentes | Não conseguem ver a chave de API do cliente |

```php
// Model: criptografia automática via cast
class Company extends Model
{
    protected $casts = [
        'api_key_encrypted' => 'encrypted',
    ];
}
```

### 4. Transporte

| Regra | Detalhe |
|---|---|
| HTTPS | Obrigatório em todos os canais, sem exceção |
| HSTS | Header `Strict-Transport-Security` habilitado |
| TLS | Mínimo TLS 1.2 |
| Certificado | Let's Encrypt via Coolify (automático) |

### 5. Logging Seguro

| O que vai nos logs | O que NUNCA vai nos logs |
|---|---|
| IDs de requisição | Nomes de pessoas |
| Timestamps | Telefones |
| Status codes | Mensagens de condomínios |
| IDs de entidades | Conteúdo de conversas |
| Tipo de evento | Chaves de API |
| Erros (sem dados pessoais) | Dados coletados |

```php
// Exemplo: log seguro
Log::info('API integration completed', [
    'service_request_id' => $request->id,
    'company_id' => $request->company_id,
    'status' => 'sent_api',
    'external_id' => $response['chamado_id'],
    // NUNCA: 'api_key' => ..., 'phone' => ..., 'message' => ...
]);
```

### 6. Proteção contra Vulnerabilidades Comuns

| Vulnerabilidade | Proteção |
|---|---|
| **SQL Injection** | Eloquent ORM (queries parametrizadas) + Enlightn SAST |
| **XSS** | Vue 3 (escaping automático) + CSP headers |
| **CSRF** | Sanctum CSRF protection |
| **Mass Assignment** | `$fillable` em todos os models |
| **Rate Limiting** | Throttle middleware nos endpoints |
| **Broken Auth** | Sanctum + middleware auth em todas as rotas protegidas |
| **Sensitive Data Exposure** | Criptografia, logs limpos, `$hidden` nos models |

### 7. Análise Estática (SAST)

**Enlightn** roda no CI/CD a cada push:
- Verifica vulnerabilidades de segurança
- Detecta configurações inseguras
- Analisa queries não parametrizadas
- Verifica exposição de dados sensíveis

```yaml
# CI/CD pipeline
- name: Security Analysis
  run: php artisan enlightn --ci
```

---

## Headers de Segurança

```php
// Middleware de headers
return $next($request)
    ->header('X-Content-Type-Options', 'nosniff')
    ->header('X-Frame-Options', 'DENY')
    ->header('X-XSS-Protection', '1; mode=block')
    ->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains')
    ->header('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'")
    ->header('Referrer-Policy', 'strict-origin-when-cross-origin');
```

---

## Auditoria

Ações críticas são registradas na tabela `activity_logs` via **spatie/laravel-activitylog**:

| Evento auditado | Dados registrados |
|---|---|
| Empresa criada/editada/deletada | user_id, mudanças (diff) |
| Chave de API atualizada | user_id, timestamp (sem a chave) |
| Atendimento assumido | user_id, service_request_id |
| Status alterado | user_id, status anterior → novo |
| Keyword adicionada/removida | user_id, keyword, scope |
| Usuário criado/editado | user_id do admin |
| Login/logout | user_id, IP, timestamp |

---

## Backup e Recovery

| Aspecto | Estratégia |
|---|---|
| **Backup do banco** | Diário, automatizado via cron no Coolify |
| **Retenção** | 30 dias de backups |
| **Destino** | Storage externo (S3 ou equivalente) |
| **Teste de restore** | Mensal, em ambiente de staging |
| **Áudios/mídias** | Backup separado, lifecycle policy de retenção |
