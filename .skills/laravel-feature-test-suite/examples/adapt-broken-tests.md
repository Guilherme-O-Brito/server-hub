# examples/adapt-broken-tests.md

Use this example when a schema/rule change breaks existing tests.

Prompt template:

Adapt failing tests after this rule change: [RULE CHANGE].

Context:

* Changed model/table: [MODEL/TABLE]
* New required fields/relationships: [FIELDS]
* Affected features likely include: [FEATURES]
* Allowed factory edits: [YES/NO AND LIMITS]

Instructions:

* Activate `laravel-feature-test-suite`.
* First analyze the real migrations/models/factories and the failing tests.
* Search for all tests that create or update the affected model directly or indirectly.
* Prefer fixing the shared factory if many tests need the same new required relation.
* If editing factories is allowed, keep changes minimal and do not add business logic.
* For HTTP tests, add required payload fields instead of weakening validation assertions.
* Preserve the original purpose of each test.
* Do not remove useful assertions.
* Do not alter production code.
* Run the affected tests first. If they pass, run the broader suite.
* If unrelated tests fail, report rather than changing unrelated code.