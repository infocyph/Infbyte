# Infbyte — Foundation 3 Persistent Runtime & Runwire Integration Plan

## Status

Target: **Infbyte on Foundation 3**

Primary framework: **infocyph/foundation 3.x**

Primary HTTP application runtime: **Webrick 5.x**

Primary lower server/runtime: **Runwire 1.0**

Branch: `infbyte-foundation-3/runtime-integration-plan`

Priority:

> application correctness → request-state isolation → deployment safety → runtime portability → performance → ergonomics

Infbyte is the primary application/distribution proof point for Foundation 3's persistent-runtime model. It must prove that a real Foundation application can run repeatedly and safely across Runwire-native and supported host runtimes without leaking request/application state, while keeping Webrick as the HTTP application-semantics boundary.

This plan intentionally does not duplicate Runwire's process/network implementation plan or Webrick's transport-adapter plan. It validates the final application-facing composition.

---

# 1. Ownership model

```text
Infbyte
  application routes/controllers/config/assets/deployment choices
        ↓
Foundation 3
  application graph / execution scope / release lifecycle / policy
        ↓
Webrick 5
  HTTP request / response / routing / middleware semantics
        ↓
Runwire 1.0
  selected runtime driver / native server / process supervision
        ↓
PHP / host runtime / OS
```

Infbyte must not implement its own:

- persistent worker lifecycle engine;
- HTTP runtime adapter;
- request reset registry;
- process supervisor;
- event loop;
- socket server;
- shell/process runner;
- framework-specific clone/sandbox mechanism.

Those belong to Foundation/Webrick/Runwire.

---

# 2. Runtime targets

Infbyte should be able to run through Foundation's single runtime-selection model without changing application routes/controllers/services.

Target runtime values:

```text
auto
native
fpm
frankenphp
swoole
roadrunner
```

OPcache remains separate:

```text
auto
on
off
required
```

Expected application invariant:

> Switching runtime driver changes hosting mechanics, not Infbyte application semantics.

Infbyte must not contain large runtime-name conditionals. Runtime-specific deployment settings belong in configuration/deployment profiles and Foundation/Runwire adapters.

---

# 3. Persistent request lifecycle

Every logical web request must receive a fresh Foundation/Webrick execution scope even when the PHP process persists.

Required model:

```text
long-lived worker
   ├─ request A -> fresh execution A -> terminate/reset
   ├─ request B -> fresh execution B -> terminate/reset
   └─ request C -> fresh execution C -> terminate/reset
```

For HTTP/2:

```text
one long-lived connection
   ├─ stream 1 -> execution A
   ├─ stream 3 -> execution B
   └─ stream 5 -> execution C
```

Concurrent/interleaved streams must not share request-scoped state.

---

# 4. Application-state lifetime audit

Before Foundation 3 release, classify every mutable Infbyte/Foundation-facing service or state holder as one of:

```text
process lifetime
worker lifetime
connection lifetime
execution/request lifetime
```

Anything not intentionally safe at process/worker lifetime must be execution-owned or explicitly reset.

The audit must cover at least:

- request object/input;
- route parameters/current route;
- authenticated principal/user;
- session state;
- authorization context;
- locale/timezone/request locale overrides;
- cookies queued during response construction;
- CSRF/request security state where enabled;
- validation state/results;
- pagination/current-request references;
- URL generator/request-context state;
- DB connection transaction state;
- DB query logs/duration trackers;
- cache locks/request-scoped cache state;
- log context/processors containing request metadata;
- output buffers;
- exception/error-handler state installed for an execution;
- temporary uploaded-file state;
- temporary response/body streams;
- mutable static caches that can contain request-specific data;
- application singleton objects that accidentally capture request-scoped dependencies;
- Fiber-local/execution-local state.

The preferred fix is correct lifetime ownership through Foundation/InterMix scopes, not a growing list of ad-hoc `reset()` calls.

---

# 5. Deterministic execution cleanup

Cleanup must run in `finally` or an equivalent Foundation-guaranteed termination boundary for every started execution.

Acceptance scenarios:

- normal 2xx response;
- 3xx response;
- 404/405;
- middleware short-circuit;
- validation failure;
- authentication/authorization rejection;
- application exception;
- streaming response completion;
- client disconnect during request body;
- client disconnect during response;
- request timeout/cancellation;
- HTTP/2 stream reset;
- worker drain/reload.

Every execution starts once and terminates once.

No request-scoped resource may remain reachable as authoritative state for the next execution.

---

# 6. No Octane-style application cloning requirement

Laravel Octane is a useful reference for persistent-runtime hazards, but Infbyte/Foundation should not adopt a framework-wide clone-the-container strategy merely to mimic Octane.

Preferred Foundation/Infbyte model:

```text
frozen/generated worker graph
        +
explicit application lifetimes
        +
fresh execution scope
        +
deterministic cleanup
```

Application cloning may be used only if a specific component genuinely requires it and measured evidence supports it. It must not become the primary isolation boundary.

---

# 7. Static/public asset fast path

Infbyte should exercise the Webrick/Foundation public-asset fast path when available.

Correct ownership:

```text
Runwire HTTP transport
      ↓
Webrick/Foundation public-asset policy
      ↓
Pathwise trusted public-root resolution
      ↓
Runwire efficient file-response primitive
      ↓
client
```

Requirements:

- no arbitrary filesystem path can be served directly from request input;
- public-root containment is authoritative before transport optimization;
- HEAD/range/cache/application response semantics remain Webrick-owned;
- Runwire may optimize transfer/streaming only after the upper layer supplies a trusted resolved artifact;
- zero-copy/sendfile-style optimization may be used when supported without changing semantics;
- dynamic routes continue through normal Foundation execution.

Infbyte must include representative static assets so this path is exercised by the real distribution.

---

# 8. Worker recycling policy consumption

Infbyte should consume Foundation/Runwire worker recycling rather than create an application-specific process watchdog.

Generic lower policy may include:

```text
max executions
max worker lifetime
max memory
optional idle lifetime
configured graceful drain deadline
```

Infbyte/Foundation may choose deployment defaults, but Runwire owns the process replacement mechanics.

Recycling is defense-in-depth for gradual process growth; it must not substitute for fixing deterministic request-state leakage.

---

# 9. Runtime lifecycle hooks

Infbyte may observe application-level lifecycle events only when useful for application boot/shutdown behavior.

Expected lower lifecycle sequence conceptually:

```text
Runwire WorkerStarting
      ↓
Foundation worker application boot
      ↓
Runwire WorkerReady
      ↓
repeated Foundation/Webrick executions
      ↓
Runwire WorkerDraining
      ↓
Foundation application shutdown/cleanup
      ↓
Runwire WorkerStopping/Exited
```

Infbyte must not perform application request work directly from Runwire signal callbacks.

---

# 10. Database/cache/resource rules

For persistent and prefork modes:

- process-bound DB/cache/broker resources must not be opened in a parent that will be forked and then shared accidentally;
- child/worker-owned long-lived clients may exist only when the owning package/framework marks them safe for that lifetime;
- transaction/session/lock state remains execution-owned;
- a failed request must not leave an open transaction or authoritative lock into the next request;
- connection recovery after backend outage must not leak stale request state.

Infbyte acceptance should use real Foundation DB/cache configuration where practical rather than only isolated unit fixtures.

---

# 11. Runtime portability acceptance

The same representative Infbyte application behavior should be exercised across available drivers:

```text
FPM
Runwire native
FrankenPHP worker mode
Swoole/OpenSwoole
RoadRunner
```

Where a host is unavailable in the development environment, retain the contract/test fixture and document the unavailable capability rather than changing application semantics.

Verify parity for:

- routing;
- middleware;
- authentication/session behavior;
- request input;
- validation;
- response status/headers/body;
- cookies;
- redirects;
- streaming/file responses;
- error handling;
- request cleanup.

HTTP/1.1 and HTTP/2 through Runwire native must reach the same Webrick/Foundation application semantics.

---

# 12. Release-generation acceptance

Infbyte should validate Foundation's release-generation lifecycle under Runwire worker generations.

Required scenario:

```text
Infbyte release A active
      ↓
start workers for release B
      ↓
B workers ready
      ↓
drain A workers
      ↓
complete/terminate active A requests within policy
      ↓
A no longer accepts new work
```

Verify:

- no worker serves mixed release artifacts;
- old release state is not reused after completed drain;
- rollback still follows Foundation release policy;
- application caches/generated artifacts point to the release the worker actually booted.

---

# 13. Failure and soak scenarios

Use Infbyte as the end-to-end application soak target for:

- many sequential requests in one worker;
- HTTP/1.1 keep-alive;
- HTTP/2 multiplexed requests;
- concurrent authenticated/anonymous requests;
- validation failures between successful requests;
- exceptions followed by successful requests;
- uploads;
- large/file/streaming responses;
- slow clients;
- client disconnects;
- DB/cache outage and recovery;
- repeated worker recycling;
- rolling reload;
- release activation;
- long-running persistent worker memory measurement.

Acceptance:

- no cross-request principal/session/input/validation/transaction state;
- bounded memory and file descriptors;
- no stale temporary uploads/body streams;
- no duplicate application boot per request where worker reuse is intended;
- deterministic shutdown/reload behavior;
- no application-visible differences caused only by runtime driver selection.

---

# 14. Performance attribution

Keep performance measurements layered:

```text
Webrick kernel
Runwire raw transport
Runwire + Webrick
Foundation + Webrick + Runwire
Infbyte full application
```

Compare against the existing FPM path where useful.

Measure at least:

- cold application boot;
- warm request throughput;
- p50/p95/p99 latency;
- worker RSS and growth over time;
- keep-alive performance;
- HTTP/2 multiplexing;
- static-file fast-path performance;
- dynamic route performance;
- request cleanup/reset overhead;
- runtime-driver adapter overhead.

Do not optimize by skipping Foundation/Webrick semantics in the Infbyte benchmark.

---

# 15. Configuration and distribution changes

During implementation, update Infbyte's distribution configuration only through Foundation-supported surfaces.

Expected categories may include:

```text
RUNWIRE_RUNTIME
RUNWIRE_OPCACHE
server/runtime deployment defaults
public/static asset settings
worker recycle defaults
```

Exact option names remain Foundation-owned.

Avoid exposing every Runwire internal knob in the Infbyte `.env.example`.

Infbyte should demonstrate sane defaults, with advanced lower-runtime tuning left to Foundation/Runwire configuration.

---

# 16. Documentation

Before the Foundation 3/Infbyte release handoff, document:

- normal FPM deployment;
- Runwire-native deployment;
- runtime selection;
- OPcache distinction;
- persistent worker state rules for application developers;
- what must not be stored in mutable globals/statics/singletons;
- worker recycling semantics;
- public/static asset path;
- graceful reload/release behavior;
- host runtime notes for FrankenPHP/Swoole/RoadRunner;
- debugging differences between request-bound and persistent modes.

---

# 17. Completion gate

Infbyte's Foundation 3 runtime handoff is complete only when:

- [ ] Infbyte consumes the final released Foundation 3 dependency rather than a development-only branch;
- [ ] Foundation selects Runwire/runtime drivers without Infbyte application code changes;
- [ ] representative dynamic routes pass over FPM and Runwire native;
- [ ] native HTTP/1.1 and HTTP/2 have application-semantic parity;
- [ ] persistent-runtime state-lifetime audit is complete;
- [ ] every started request execution is deterministically cleaned up;
- [ ] repeated/interleaved requests do not leak auth/session/input/validation/DB/cache state;
- [ ] worker recycling works without becoming the request-isolation mechanism;
- [ ] static/public assets use the trusted Webrick/Foundation/Pathwise boundary before any Runwire fast transfer;
- [ ] release-generation rolling replacement is proven;
- [ ] persistent worker soak shows bounded memory/FD growth attributable to Infbyte/Foundation;
- [ ] selected host runtimes preserve the same application contract;
- [ ] production documentation describes persistent-runtime coding constraints and deployment choices;
- [ ] final end-to-end performance attribution is recorded.

---

# 18. Immediate handoff

Do not add runtime-specific application workarounds to Infbyte while Runwire/Webrick/Foundation APIs are still being finalized.

Once the lower runtime contract is stable, the first Infbyte proof should be deliberately small:

```text
boot Infbyte once in a persistent worker
      ↓
serve dynamic request A
      ↓
cleanup
      ↓
serve dynamic request B with different principal/input/state
      ↓
prove isolation
      ↓
serve trusted public asset through fast path
      ↓
recycle/reload worker
      ↓
prove clean new generation
```

Then broaden to HTTP/2 multiplexing, uploads, DB/cache behavior, host-runtime parity and production-style soak.