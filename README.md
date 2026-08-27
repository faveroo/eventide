# Eventide

> **Detect. Correlate. Respond.**

## Sobre o projeto

**Eventide** é uma plataforma de monitoramento de aplicações e gerenciamento de incidentes voltada para equipes de desenvolvimento.

O objetivo é centralizar a visualização da saúde de aplicações, APIs e microservices pertencentes a uma organização, permitindo que a equipe identifique rapidamente quando um sistema está indisponível ou apresentando comportamento degradado.

Uma organização poderá possuir diversos projetos:

```text
Organization
│
├── Members
│
└── Projects
    ├── Ecommerce
    ├── Payments API
    ├── Authentication API
    └── Notification Worker
```

O Eventide utilizará diferentes fontes de informação para entender o estado dessas aplicações:

```text
PULL
Eventide → Application
Health Checks

PUSH
Application → Eventide
Application Events

EXTERNAL EVENTS
GitHub / outras integrações → Eventide
```

A combinação dessas informações permitirá classificar aplicações como:

```text
Operational
Degraded
Down
```

e criar incidentes automaticamente quando determinados problemas forem detectados.

---

## Problema

Saber apenas se uma aplicação está online não significa saber se ela está funcionando corretamente.

Uma API pode responder:

```text
200 OK
```

enquanto internamente apresenta problemas como:

```text
Pagamentos falhando

Jobs falhando

Database timeouts

Queries lentas

Filas congestionadas

Erros internos
```

Ao mesmo tempo, depender apenas da própria aplicação para informar esses erros também não é suficiente.

Se uma aplicação ficar completamente indisponível, ela não conseguirá enviar um evento informando que caiu.

Além disso, em ambientes compostos por várias aplicações ou microservices, informações importantes acabam distribuídas entre diferentes lugares:

```text
Logs da aplicação

GitHub

Deployments

Ferramentas de monitoramento

Filas

Health endpoints

Mensagens da equipe
```

Isso dificulta identificar rapidamente:

* qual aplicação apresenta problemas;
* quando o problema começou;
* o que aconteceu antes da falha;
* se houve algum deployment recente;
* quais erros estão ocorrendo;
* se já existe um incidente sendo investigado;
* quanto tempo levou para resolver o problema.

---

## Solução

O Eventide centraliza essas informações e as transforma em uma visão operacional das aplicações da organização.

### Monitoramento ativo

O Eventide poderá consultar periodicamente endpoints de saúde:

```text
Eventide
   ↓
GET /health
   ↓
Application
```

Isso permitirá detectar:

* indisponibilidade;
* timeouts;
* erros HTTP;
* alta latência;
* falhas de dependências.

---

### Eventos da aplicação

As aplicações também poderão enviar eventos para o Eventide:

```text
Application
     ↓
POST /api/v1/events
     ↓
Eventide
```

Exemplos:

```text
application.exception
job.failed
database.timeout
payment.failed
checkout.failed
```

Um SDK poderá futuramente facilitar essa integração, permitindo chamadas como:

```php
Eventide::capture('payment.failed', [
    'payment_id' => $payment->id,
]);
```

---

### Integrações externas

O Eventide poderá receber eventos de serviços como GitHub.

Por exemplo:

```text
10:22 Deployment realizado

10:31 Erros começam a aumentar

10:34 Health Check falha

10:36 Incidente criado
```

Assim, durante a investigação, a equipe poderá visualizar que um deployment aconteceu pouco antes do problema.

---

## Detecção de incidentes

Eventos e Health Checks serão avaliados por regras.

Por exemplo:

```text
20 erros HTTP

dentro de

5 minutos

↓

Criar incidente
```

Ou:

```text
3 Health Checks consecutivos falharam

↓

Aplicação DOWN

↓

Criar incidente
```

O Eventide poderá então criar automaticamente:

```text
INC-1042

Payments API unavailable

Severity
High

Status
Investigating
```

Eventos relacionados ao mesmo problema serão agrupados no incidente em vez de gerar vários incidentes duplicados.

---

## Visão geral

A proposta do Eventide é oferecer uma visão centralizada sobre **o que está acontecendo nas aplicações de uma equipe, detectar problemas automaticamente e fornecer contexto suficiente para que incidentes sejam investigados e resolvidos mais rapidamente.**

---

## Objetivo técnico

Além de resolver o problema de monitoramento e gerenciamento de incidentes, o Eventide foi pensado como um projeto para explorar conceitos de engenharia de software que normalmente não aparecem em aplicações CRUD simples, incluindo:

* processamento assíncrono;
* filas e workers;
* monitoramento;
* WebSockets;
* webhooks;
* autenticação de APIs;
* idempotência;
* integração entre sistemas;
* arquitetura orientada a eventos;
* regras de detecção;
* multi-tenancy;
* observabilidade.

---

**Eventide — Detect. Correlate. Respond.**
