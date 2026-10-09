---
name: laravel-feature-test-suite
description: Create or refactor Laravel feature/unit tests for project features, controllers, actions, jobs, services, policies, validation, queues, and integrations. Use when asked to "make tests", "generate tests", "adapt failing tests", or test a Laravel feature while preserving project architecture.
---

# Laravel Feature Test Suite

Use this skill to create or refactor Laravel tests safely and consistently.

## Hard rules

* Never read, open, edit, or depend on `.env`, `.env.example`, or any environment file.
* Do not modify production code: controllers, routes, actions, jobs, services, models, policies, requests, exceptions, providers, migrations, seeders, config, Docker/Kubernetes files, or composer files.
* Only modify tests. Modify factories only when the user explicitly allows it or when a new required column makes tests impossible; keep factory changes minimal.
* If production code has a bug, do not fix it. Report it.
* If an issue is blocking and the feature cannot work, stop and report before editing tests.

## Required workflow

1. Analyze the feature first, even if the user provides context. Read the real routes, controller, request, action, job, service, model, policy, relationships, factories, and existing related tests.
2. Check whether tests already exist for the requested feature. If yes, update/refactor them instead of duplicating coverage.
3. Compare project test organization with this skill’s naming/folder rules. Follow the project when it is more specific.
4. Plan the test cases before editing. Cover success, auth, authorization, validation, state rules, failure paths, jobs, database effects, and external integration boundaries.
5. Create missing test files with Artisan when possible: `php artisan make:test NameTest` or `php artisan make:test NameTest --unit`. Create manually only if Artisan has no suitable command.
6. Implement tests using existing project style.
7. Run only the new/changed test suite first. If it fails, inspect the failure:

   * if the test is wrong, fix the test and rerun;
   * if production code is wrong, stop and report.
8. If the changed suite passes, run the full suite. If unrelated tests fail, do not fix unrelated code; report.

## Feature test folders and names

Feature tests mirror routes/resource areas under `tests/Feature`.

Use file/class names as `ActionModelTest` in PascalCase:

* `CreateMinecraftServerTest`
* `IndexMinecraftServerTest`
* `GetMinecraftServerTest`
* `StartMinecraftServerTest`
* `StopMinecraftServerTest`

For game server routes, use:

* `tests/Feature/servers/minecraft/` for MinecraftServer itself.
* `tests/Feature/servers/minecraft/whitelist/` for whitelist features.
* `tests/Feature/servers/minecraft/admins/` for server-admin features.
* Put other nested resources under the parent model folder when they belong to it.

For standalone platform resources, follow existing folders such as:

* `tests/Feature/ExecutionSlot/`
* `tests/Feature/User/`

## Unit test folders and names

Unit tests mirror `app/` architecture more than routes.

Use:

* `tests/Unit/Actions/...`
* `tests/Unit/Jobs/...`
* `tests/Unit/Services/...`

File/class names still use `ActionModelTest` or the class under test:

* `CreateMinecraftServerActionTest`
* `StartMinecraftServerJobTest`
* `ProvisioningServiceTest`
* `MinecraftManifestBuilderTest`

If existing tests place a class in a more specific subfolder, follow that pattern.

## Test style

* Use `RefreshDatabase` unless the existing nearby tests use another database strategy.
* Test method names must be descriptive snake_case starting with `test_`.
* Prefer behavior-focused tests over implementation-only tests.
* Use factories and explicit attributes for important state.
* Use `actingAs()` for authenticated paths.
* Assert response status, JSON message/shape, database state, relationships, and dispatched jobs when relevant.
* For routes requiring authorization, test allowed users and forbidden users.
* For policy-based resources, cover owner/admin/non-authorized/guest when applicable.
* For validation, test required fields, invalid values, and accepted valid values.
* Do not weaken or remove existing assertions just to make tests pass.

## Actions, jobs, services

When a feature uses Action + Job + Service:

* Feature tests validate HTTP response, permissions, database state, and dispatch.
* Action tests validate business rules, transactions/rollback, state changes, exceptions, and dispatch after commit.
* Job tests mock services and validate state changes and `failed()` behavior.
* Service tests mock clients/builders and assert method calls/payloads.
* Use `Queue::fake()` for dispatch tests, `Bus::fake()` if the project uses Bus, `Http::fake()` for HTTP clients, and Mockery/container mocks for services.
* Never call Kubernetes, Docker, external HTTP, real queues, workers, or cluster resources.

## Gotchas

* Route parameters with generic model binding may conflict with static routes; check constraints like `whereNumber`.
* Relationship names in `with()`/`load()`/`whereHas()` must use model method names, not class names.
* If a model gained a required FK, update tests/factories to create valid related models.
* If the current controller returns an odd JSON shape, test the current behavior and report it instead of changing production code.

## Final report

End with:

* files analyzed, created, and changed;
* Artisan commands used;
* tests added/refactored and what they validate;
* mocks/fakes used, including Queue/Bus/Http;
* how auth/authorization and failure cases were covered;
* test commands run and results;
* production bugs found, if any, without fixes;
* explicit confirmation that no production code was changed;
* explicit confirmation that no `.env` or `.env.example` was read or changed.
