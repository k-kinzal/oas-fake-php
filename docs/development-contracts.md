# Development Contracts

OasFake uses `k-kinzal/php-ai-toolkit` to keep design constraints executable. The gates overlap deliberately: a type checker cannot enforce package direction, a coverage report cannot prove assertions are meaningful, and a formatter cannot define a public boundary.

## Gate responsibilities

| Gate | Contract it enforces |
|---|---|
| PHP CS Fixer | Deterministic formatting and strict comparisons. |
| PHPStan + strict/toolkit rules | Native and documented types, initialized state, checked exception declarations, test isolation, precise names, and override contracts. |
| PHPCompatibility | Production syntax and APIs remain valid on PHP 8.0 through 8.5. |
| LocGuard | Classes and methods remain small enough to retain one understandable responsibility. |
| TreeGuard | Production, tests, doctests, and support fixtures stay in their declared locations. |
| ScopeGuard | Public and namespace-only symbols state their intended audience through `@visibility`. |
| Deptrac | Dependencies flow from facade and pipeline layers toward contracts, schema, generation, and exceptions. |
| PHPUnit | Unit and doctest suites reject risky tests, output, global-state changes, warnings, and undeclared coverage collaboration. |
| Infection | Tests must distinguish behavioral mutations, not merely execute production lines. |
| DocGen | PHPDoc, examples, visibility, architecture, calls, and coverage remain renderable as one site. |

`composer lint` runs every fast gate. CI lists them as separate named steps so the failed contract is visible without unpacking an aggregate log.

## Design boundaries

The main dependency direction is:

```text
Facade → Pipeline → Handler / Generation → Schema → Contract → Exception
```

Some responsibilities that were previously accumulated in large classes are explicit objects:

- `ServerConfiguration` owns mutable server settings and environment overrides.
- `ServerLifecycle` owns registry and interceptor lifecycle state.
- `OperationIndexBuilder`, `OperationParameterResolver`, and `PathOperationResolver` own OpenAPI indexing decisions.
- `RequestBodyGenerator` and `OperationResponseResolver` own request and response schema selection.
- `FakeDataSource` is the narrow contract shared by `Server` and `FakeDataContext` for standalone generation.
- `Handler` always contains one callable response strategy, so it has no nullable or partially initialized mode state.

The historical `OperationInfo` entry point remains a compatibility alias, while internal code uses the responsibility-specific `OperationDefinition` name required by the naming contract.

## Failure contracts

Public boundaries translate dependency failures into domain exceptions. Callers can distinguish invalid configuration (`InvalidModeException`), locked or conflicting lifecycle state (`ServerStateException`), schema loading/parsing, missing operations, generation, handler registration/resolution, replay mismatch, and OpenAPI validation.

Checked exceptions are declared with `@throws`. Logic and runtime programming errors remain unchecked; recoverable library failures use the documented `OasFakeException` hierarchy.

## Test contracts

Each unit test class declares its primary target with `CoversClass`, `CoversTrait`, or `CoversClassesThatImplementInterface`. Intentional collaborators are declared with `UsesClass` or `UsesTrait`. This keeps strict coverage metadata useful: adding a new hidden dependency makes the test risky until that collaboration is acknowledged or removed.

Compatibility tests that cannot map to a class code unit use `CoversNothing` and assert the compatibility behavior directly. Test fixtures that need reflection, filesystem lifecycle, or concrete server spies live under `OasFake\Testing`; unit test classes consume those boundaries instead of carrying hidden mutable helpers.

Runnable `@example` blocks on public symbols are executed by the doctest suite on every supported PHP version.

## Mutation ratchet

CI generates a fresh coverage map before every Infection run. Pull requests score only changed lines against `origin/<base>`, while pushes to `main` score the whole source tree. `infection.json5` keeps the whole-tree ratchet; CI supplies the changed-line threshold.

The initial measured whole-tree scores are 73.47% MSI and 75.90% covered MSI, with 96.80% mutation coverage; the committed ratchet is 73/75. This pull request's changed lines measure 74.80% MSI and 77.36% covered MSI, so their ratchet is 74/77. The small margin absorbs runtime classification differences without allowing an untested behavioral regression to disappear into rounding.

The installed OpenAPI faker currently requires `thecodingmachine/safe` 2.x, which constrains Infection to its 0.29 line. The configuration therefore uses that line's `testFrameworkOptions` key while retaining a broad Composer constraint for future dependency resolution.

Mutation thresholds are a ratchet, not a target to game. When a mutation survives, first decide which observable contract should distinguish it. Add that assertion or simplify the implementation. Do not disable the mutator, exclude production files, or add mutation-ignore comments.

## Documentation

`composer test:coverage && composer doc-gen` generates `build/docs` locally and in CI. Publishing is intentionally separate from generation; enabling a GitHub Pages workflow requires a repository-level decision about the public documentation URL and permissions.
