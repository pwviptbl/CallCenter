# Documentação Técnica — CallCenter de Manutenção de Elevadores

Índice centralizado de toda a documentação do projeto.

## Documentos

| Documento | Descrição |
|---|---|
| [Arquitetura](arquitetura.md) | Visão geral da arquitetura, camadas, comunicação entre serviços |
| [Stacks](stacks.md) | Tecnologias escolhidas, justificativas e custos |
| [Modelagem](modelagem.md) | Modelagem do banco de dados, entidades, relacionamentos |
| [Filtro de Urgência](filtro-urgencia.md) | Regras de roteamento, keywords configuráveis, lógica síncrona |
| [Fluxos de Atendimento](fluxos.md) | Fluxos completos: entrada → filtro → IA/humano → registro |
| [Canais](canais.md) | WhatsApp (Evolution API), VOIP (Asterisk), configuração por empresa |
| [API e Integrações](api.md) | Modos de integração, contratos de API, mapeamento de campos |
| [Painel do Atendente](painel.md) | Painel de solicitações, tempo real, filtros e buscas |
| **[Perfis de Usuário](perfis.md)** | **Admin vs Atendente: permissões, funcionalidades, endpoints** |
| [Segurança](seguranca.md) | Autenticação, isolamento de dados, criptografia, sem RLS |
| [Testes](testes.md) | Estratégia de testes automatizados e piloto |
| [MVP e Roadmap](mvp-roadmap.md) | Escopo do MVP, fases, cronograma de entrega |

## Contexto do Projeto

Sistema de atendimento (callcenter) para empresas de manutenção de elevadores. **Uma única empresa operadora** do callcenter atende **múltiplas empresas clientes** (~70). Não é multi-tenant — é uma aplicação single-tenant onde a empresa operadora gerencia os dados de todos os seus clientes.

> O sistema de callcenter é independente do sistema de gestão de manutenção. Os dois se comunicam opcionalmente via API.

## Decisões Arquiteturais Relevantes

- **Sem Row-Level Security (RLS)**: o sistema não é multi-tenant. Uma única empresa opera o callcenter e tem acesso legítimo a todos os dados dos clientes. O isolamento entre empresas clientes é lógico (filtro por `company_id` na aplicação), não por tenant.
- **Filtro de urgência síncrono e sem IA**: palavras-chave determinísticas, regex puro, zero latência.
- **Keywords configuráveis**: cada empresa cliente pode ter palavras-chave extras além dos padrões globais.
