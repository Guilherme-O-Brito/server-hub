# examples/action-job-service.md

Use this example when a feature uses Action + Job + Service, such as start/stop/provision/update/delete flows.

Prompt template:

Create tests for the [FEATURE NAME] flow.

Architecture:

* Controller calls: [ACTION]
* Action validates business rules and updates DB state.
* Action dispatches: [JOB]
* Job calls: [SERVICE]
* Service calls/mocks: [CLIENT/BUILDER]
* Expected success state: [STATE]
* Expected failure state: [STATE]
* Important exceptions: [EXCEPTIONS]

Instructions:

* Activate `laravel-feature-test-suite`.
* Read the real controller, action, job, service, models, policies, factories, and existing tests.
* Feature tests must validate HTTP response, permissions, DB state, and job dispatch.
* Action tests must validate state rules, rollback, exceptions, and no dispatch on failure.
* Job tests must mock the service and validate success plus `failed()` behavior.
* Service tests must mock client/builder and never call real infrastructure.
* Use `Queue::fake()` for dispatch tests.
* Use `RefreshDatabase` unless nearby tests use another pattern.
* Do not change production code.
* Stop and report if production code has a blocking bug.

---