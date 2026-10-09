# examples/new-feature.md

Use this example when the user asks to create tests for a new Laravel feature.

Prompt template:

Create tests for the feature: [FEATURE NAME].

Context:

* Main route(s): [ROUTES]
* Main controller method(s): [METHODS]
* Related model(s): [MODELS]
* Authorization rules: [OWNER/ADMIN/PLATFORM ADMIN/GUEST]
* Important relationships: [RELATIONSHIPS]
* Async behavior, if any: [JOBS/QUEUES]
* External integration, if any: [SERVICE/CLIENT]

Instructions:

* Activate `laravel-feature-test-suite`.
* Analyze the real project files before editing tests.
* Check if tests already exist for this feature.
* Create missing tests with Artisan.
* Follow existing folder/name conventions.
* Cover success, auth, authorization, validation, database effects, relationships, and failure paths.
* Use Queue::fake/Bus::fake/Http::fake/mocks where appropriate.
* Do not modify production code.
* Do not read or modify `.env` or `.env.example`.
* Run the new/changed tests first, then the broader relevant suite.
* Report all changes and any production issues found.

---