# Unit tests

These tests are **bootstrap-light** (no [Orchestra Testbench](../TestCase.php) unless you explicitly add it). They focus on **core logic** in `src/`: parsing, year expansion, validation query guards, file-backed wilayah lookup, and region matching.

## Policy

- **Mirror** `src/` directory segments under `tests/Unit/` (e.g. `NIK/`, `Support/`, `Regions/Lookup/`, `Regions/Matching/`).
- **Do not** add Unit tests for enums, DTOs, or thin wiring (`Gender`, `Parsed` / `ValidationResult` as isolated targets, `KTP`, `IndonesianKtpServiceProvider`, `HasIndonesianKtp`). Assert `Parsed` only through `Parser::parse()` where needed.
- Prefer **few, high-signal** cases on public APIs; avoid exhaustive coverage of helpers or private methods.
- End-to-end README behaviour and Laravel container binding live under [tests/Feature](../Feature/README.md).

## `src` → `tests/Unit` map

| `src` | `tests/Unit` |
| --- | --- |
| [src/NIK/Parser.php](../../src/NIK/Parser.php) | [NIK/ParserTest.php](NIK/ParserTest.php) |
| [src/NIK/Query.php](../../src/NIK/Query.php) | [NIK/QueryTest.php](NIK/QueryTest.php) |
| [src/Support/TwoDigitYearExpander.php](../../src/Support/TwoDigitYearExpander.php) | [Support/TwoDigitYearExpanderTest.php](Support/TwoDigitYearExpanderTest.php) |
| [src/Regions/Lookup/FileRegionHierarchyLookup.php](../../src/Regions/Lookup/FileRegionHierarchyLookup.php) | [Regions/Lookup/FileRegionHierarchyLookupTest.php](Regions/Lookup/FileRegionHierarchyLookupTest.php) |
| [src/Regions/Matching/NikRegionMatcher.php](../../src/Regions/Matching/NikRegionMatcher.php) | [Regions/Matching/NikRegionMatcherTest.php](Regions/Matching/NikRegionMatcherTest.php) |

Shared fixtures: [tests/fixtures/](../fixtures/).

When adding a new subdirectory, confirm Pest still discovers it ([phpunit.xml](../phpunit.xml) includes the whole `tests/` tree).
