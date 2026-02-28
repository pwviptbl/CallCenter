# Painel do Atendente

## Visão Geral

O painel é a interface principal dos operadores do callcenter. Consolida em uma única tela todas as solicitações de todas as empresas clientes. É uma SPA (Vue 3 + Tailwind) com atualizações em tempo real via WebSocket (Laravel Reverb).

---

## Telas Principais

### 1. Dashboard (Fila de Atendimentos)

Visão em tempo real de todos os atendimentos ativos e pendentes.

```
┌─────────────────────────────────────────────────────────────────┐
│  CallCenter — Painel do Atendente                    João Silva │
│                                                    [Supervisor] │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  🔴 EMERGÊNCIAS (2)                                             │
│  ┌────────────────────────────────────────────────────────┐    │
│  │ 🔴 Edifício Aurora — "pessoa presa no elevador"       │    │
│  │    ElevaTech | WhatsApp | Há 30s | [ASSUMIR]           │    │
│  ├────────────────────────────────────────────────────────┤    │
│  │ 🔴 Cond. Bela Vista — "fumaca saindo do poco"         │    │
│  │    MasterLift | WhatsApp | Há 1min | [ASSUMIR]         │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                 │
│  📋 PENDENTES (5)                                               │
│  ┌────────────────────────────────────────────────────────┐    │
│  │ Cond. São Jorge — Dados prontos para abertura manual   │    │
│  │    TechElev | Assistido | IA coletou | [REVISAR]       │    │
│  ├────────────────────────────────────────────────────────┤    │
│  │ Cond. Park View — IA coletando dados...                │    │
│  │    ElevaTech | API Auto | IA atendendo | Há 2min       │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                 │
│  ✅ RECENTES (últimas 2h)                                       │
│  ┌────────────────────────────────────────────────────────┐    │
│  │ Cond. Itaim — Enviado via API (OS-2024-0451)          │    │
│  │ Cond. Morumbi — Confirmado manual por Maria            │    │
│  │ Cond. Pinheiros — Registro local, repassado por tel    │    │
│  └────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

**Comportamento em tempo real:**
- Novas emergências aparecem no topo com alerta sonoro
- Atendimentos IA em andamento atualizam status automaticamente
- Mudanças de status refletem instantaneamente

---

### 2. Tela de Atendimento (Conversa)

Quando o atendente assume um atendimento:

```
┌─────────────────────────────────────────────────────────────────┐
│  ← Voltar    Atendimento #SR-2024-0892         [EMERGÊNCIA 🔴] │
├─────────────────────────────────┬───────────────────────────────┤
│                                 │                               │
│  CONVERSA                       │  DADOS DO ATENDIMENTO         │
│  ─────────                      │  ──────────────────           │
│                                 │  Empresa: ElevaTech           │
│  👤 Condomínio (14:32)          │  Modo: API Automática         │
│  "tem gente presa no elevador   │  Canal: WhatsApp              │
│   do bloco B"                   │  Tipo: Emergência             │
│                                 │                               │
│  🤖 IA (14:32)                  │  CAMPOS OBRIGATÓRIOS          │
│  "Transferindo para atendente"  │  ──────────────────           │
│                                 │  ☑ Condomínio: Ed. Aurora     │
│  👨‍💼 João (14:33)               │  ☑ Elevador: Bloco B          │
│  "Entendi! Já estou acionando   │  ☐ Andar: ___                 │
│   a equipe. Alguém está         │  ☑ Problema: Pessoa presa     │
│   ferido?"                      │  ☑ Solicitante: Carlos        │
│                                 │  ☑ Telefone: 11999887766      │
│  👤 Condomínio (14:33)          │                               │
│  "Não, estão assustados mas     │  [ABRIR CHAMADO]              │
│   sem ferimentos"               │  [COPIAR DADOS]               │
│                                 │  [ABRIR SISTEMA EXTERNO]      │
│  ┌──────────────────────────┐  │                               │
│  │ Digite sua mensagem...   │  │  STATUS                       │
│  │                    [📎]  │  │  ──────                        │
│  └──────────────────────────┘  │  ⏳ Aguardando abertura        │
│                                 │                               │
├─────────────────────────────────┴───────────────────────────────┤
│  [ENVIAR P/ API] [CONFIRMAR ABERTURA MANUAL] [REGISTRAR LOCAL]  │
└─────────────────────────────────────────────────────────────────┘
```

---

### 3. Painel de Solicitações (Histórico)

Consulta de todas as solicitações registradas, com filtros.

```
┌─────────────────────────────────────────────────────────────────┐
│  Solicitações                                                    │
├─────────────────────────────────────────────────────────────────┤
│  Filtros:                                                        │
│  [Empresa ▾] [Período ▾] [Canal ▾] [Tipo ▾] [Status ▾] 🔍     │
│                                                                  │
│  ┌──────┬────────────┬──────────┬─────────┬──────────┬────────┐│
│  │ Data │ Empresa    │ Canal    │ Tipo    │ Status   │ Ações  ││
│  ├──────┼────────────┼──────────┼─────────┼──────────┼────────┤│
│  │14:32 │ ElevaTech  │ WhatsApp │ Emerg.  │ Enviado  │ [Ver]  ││
│  │14:15 │ MasterLift │ WhatsApp │ Rotina  │ API OK   │ [Ver]  ││
│  │13:50 │ TechElev   │ WhatsApp │ Rotina  │ Manual   │ [Ver]  ││
│  │13:22 │ ElevaTech  │ WhatsApp │ Rotina  │ Local    │ [Ver]  ││
│  │12:45 │ LiftPro    │ WhatsApp │ Emerg.  │ API Erro │ [Ver]  ││
│  └──────┴────────────┴──────────┴─────────┴──────────┴────────┘│
│                                                                  │
│  Mostrando 1-20 de 1.847 resultados    [← 1 2 3 ... 93 →]     │
└─────────────────────────────────────────────────────────────────┘
```

**Filtros disponíveis:**
- Por empresa cliente
- Por período (hoje, semana, mês, intervalo customizado)
- Por canal (WhatsApp, VOIP)
- Por tipo de atendimento (IA, humano)
- Por status de integração
- Por palavra-chave nos dados coletados (busca full-text)

---

### 4. Cadastro de Empresas

CRUD de empresas clientes com configuração completa.

**Tabs:**
- **Dados gerais**: nome, identificadores, canais ativos
- **Campos obrigatórios**: configuração drag-and-drop dos campos para chamado
- **Integração API**: endpoint, chave, mapeamento de campos, teste de conexão
- **IA**: script de abertura, comportamento
- **Urgência**: keywords extras específicas da empresa
- **Configurações**: horário, reincidência, modo de integração

---

### 5. Gerenciamento de Keywords (Admin)

```
┌─────────────────────────────────────────────────────────────────┐
│  Keywords de Urgência                          [+ Nova Keyword]  │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  GLOBAIS                                                         │
│  ┌──────────────┬──────────┬────────┐                           │
│  │ Keyword      │ Status   │ Ações  │                           │
│  ├──────────────┼──────────┼────────┤                           │
│  │ preso        │ ✅ Ativa │ [Edit] │                           │
│  │ fumaca       │ ✅ Ativa │ [Edit] │                           │
│  │ queda        │ ✅ Ativa │ [Edit] │                           │
│  │ socorro      │ ✅ Ativa │ [Edit] │                           │
│  │ fogo         │ ✅ Ativa │ [Edit] │                           │
│  │ incendio     │ ✅ Ativa │ [Edit] │                           │
│  └──────────────┴──────────┴────────┘                           │
│                                                                  │
│  POR EMPRESA                                                     │
│  [Selecionar empresa ▾]                                          │
│  ┌──────────────┬──────────┬────────────┬────────┐              │
│  │ Keyword      │ Status   │ Empresa    │ Ações  │              │
│  ├──────────────┼──────────┼────────────┼────────┤              │
│  │ desabamento  │ ✅ Ativa │ MasterLift │ [Edit] │              │
│  │ inundacao    │ ✅ Ativa │ FixElev    │ [Edit] │              │
│  └──────────────┴──────────┴────────────┴────────┘              │
│                                                                  │
│  TESTAR FILTRO                                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ Digite uma mensagem para testar: [________________] [▶] │    │
│  │ Resultado: ✅ Urgência detectada — keyword "preso"      │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

---

## Alertas de Emergência

### Comportamento

1. **Alerta sonoro**: som distinto que toca quando chega emergência
2. **Alerta visual**: card vermelho pulsante no topo da fila
3. **Notificação do browser**: push notification (se permitido)
4. **Badge no título**: `(2) 🔴 CallCenter` mostra quantidade de emergências

### Lógica de Atribuição

- Emergências aparecem para **todos** os atendentes disponíveis
- O primeiro a clicar "Assumir" fica responsável
- Outros atendentes veem que já foi assumido (atualização WebSocket)
- Se ninguém assumir em X segundos → escalação para supervisor

---

## Roles e Permissões

| Role | Permissões |
|---|---|
| **Admin** | Tudo: CRUD empresas, CRUD usuários, keywords, configurações |
| **Supervisor** | Visualizar tudo, reatribuir atendimentos, relatórios |
| **Atendente** | Assumir atendimentos, enviar mensagens, confirmar abertura |

---

## Responsividade

- O painel é **desktop-first** (operadores usam computador)
- Mínimo suportado: 1280px de largura
- PWA para acesso mobile em emergências (supervisor)
- Layout adaptável, mas otimizado para desktop com duas colunas
