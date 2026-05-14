# Feature tests

These tests run with [Orchestra Testbench](../TestCase.php) via [`tests/Pest.php`](../Pest.php) (`uses(TestCase::class)->in(__DIR__ . '/Feature')`), so anything that needs a Laravel container (for example `KTP::nik()` resolving `RegionHierarchyLookup` from the app) belongs here.

## Layout

| Directory | Put tests here when… |
| --- | --- |
| [`Ktp/`](Ktp/) | Exercising the public [`KTP`](../../src/KTP.php) fluent API: validation, `parsed()`, expectations, region matching on the query, two-digit year behaviour. Prefer mirroring examples from the package [`README.md`](../../README.md). |
| [`Regions/`](Regions/) | Wilayah data loading and **container binding** for [`RegionHierarchyLookup`](../../src/Regions/Lookup/RegionHierarchyLookup.php) (custom `FileRegionHierarchyLookup`, service provider behaviour). |
| [`Eloquent/`](Eloquent/) | The [`HasIndonesianKtp`](../../src/Concerns/HasIndonesianKtp.php) trait on `Model` instances: explicit `nik*Is` matchers, NIK column, `indonesianKtpReferenceDate`, optional NIK accessor normalisation. |

Shared static files used by Feature tests live under [`tests/fixtures/`](../fixtures/).

## Conventions

- Name tests with a **`README:`** prefix when they correspond to a documented use case, so failures map back to the readme.
- Prefer **`mv`** to relocate files instead of copy-paste rewrites, so history and blame stay intact.
- If you add a **new subdirectory** under `Feature/`, confirm Pest still discovers it (parent `in(__DIR__.'/Feature')` is recursive). If not, register the path explicitly in [`tests/Pest.php`](../Pest.php).
