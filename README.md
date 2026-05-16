# 🇮🇩 Indonesian KTP (NIK) validation

Validate Indonesian NIK (*Nomor Induk Kependudukan*) with structural checks and bundled wilayah data (no MySQL, no Nusantara). Public API: [`KTP`](src/KTP.php).

## Table of contents

- [What gets validated](#what-gets-validated)
- [Requirements](#requirements)
- [Install](#install)
- [Quick start](#quick-start)
- [Usage](#usage)
  - [Basic validation](#basic-validation)
  - [Laravel Validator (rule object and ktp-nik)](#laravel-validator-rule-object-and-ktp-nik)
  - [Quick checks](#quick-checks)
  - [Expectations and aliases](#expectations-and-aliases)
  - [validate() and ValidationResult](#validate-and-validationresult)
  - [Parsed values](#parsed-values)
  - [Region inputs](#region-inputs)
  - [Two-digit birth years: ambiguity and asOf()](#two-digit-birth-years-ambiguity-and-asof)
  - [Region hierarchy lookup](#region-hierarchy-lookup)
  - [Eloquent HasIndonesianKtp](#eloquent-hasindonesianktp)
    - [Explicit matchers](#explicit-matchers)
    - [Trait methods](#trait-methods)
  - [NIK column and accessors](#nik-column-and-accessors)
- [Develop and test](#develop-and-test)
- [Data source](#data-source)
- [Security](#security)
- [Versioning and support](#versioning-and-support)

## What gets validated

- **Structure** — length, digits, birth date / gender encoding.
- **Region hierarchy** — district code in the NIK must exist in [`data/wilayah.php`](data/wilayah.php) (province → regency → subdistrict).

Optional checks (birth, age, gender, wilayah names/codes): [Usage](#usage). Dataset: [Data source](#data-source).

Invalid length or unknown district:

```php
use Bensondevs\IndonesianKtp\KTP;

KTP::nik('123')->isValid(); // false — wrong length
KTP::nik('9999991501900001')->isValid(); // false — unknown district (no expectations)
```

## Requirements

| Requirement | Notes |
| --- | --- |
| PHP | 8.3+ |
| Laravel | 10–13; `illuminate/contracts`, `illuminate/database`, `illuminate/support`, `illuminate/validation` match your app |
| Carbon | `nesbot/carbon` ^2.67 or ^3.0 |

## Install 📦

```bash
composer require bensondevs/indonesian-ktp
```

## Quick start ✅

Minimal check (structure + wilayah hierarchy):

```php
use Bensondevs\IndonesianKtp\KTP;

KTP::nik('3315131501901235')->isValid();
```

Structure + region only until you add expectations (see [Usage](#usage)).

## Usage

- `KTP::nik($raw)` returns a fluent, **immutable** `Query`: each chained call is a new instance.
- `isValid()` → one `bool`. `validate()` → [`ValidationResult`](src/NIK/ValidationResult.php) with per-flag detail.
- Laravel’s [`Validator`](https://laravel.com/docs/validation) is supported via [`KtpNik`](src/Rules/KtpNik.php) and string rules registered in [`IndonesianKtpServiceProvider`](src/IndonesianKtpServiceProvider.php) — see [Laravel Validator (rule object and ktp-nik)](#laravel-validator-rule-object-and-ktp-nik).

```php
use Bensondevs\IndonesianKtp\Gender;
use Bensondevs\IndonesianKtp\KTP;

KTP::nik('3315131501901235')
    ->expectGender(Gender::Male)
    ->isValid();
```

### Basic validation

```php
use Bensondevs\IndonesianKtp\KTP;

KTP::nik('3315131501901235')->isValid();
```

### Laravel Validator (rule object and ktp-nik)

With [package discovery](https://laravel.com/docs/packages#package-discovery), [`IndonesianKtpServiceProvider`](src/IndonesianKtpServiceProvider.php) registers translation lines and validation extensions automatically. You can validate request input either with a [rule object](https://laravel.com/docs/validation#using-rule-objects) or with a string rule.

**Rule object vs string rule**

```php
use Bensondevs\IndonesianKtp\Rules\KtpNik;

$request->validate([
    'nik' => ['required', 'string', new KtpNik],
]);

// Equivalent string rule (underscore alias: ktp_nik)
$request->validate([
    'nik' => ['required', 'string', 'ktp-nik'],
]);
```

**What this checks**

| | |
| --- | --- |
| Same as | Plain `KTP::nik($value)->isValid()` — **structure** (length, digits, birth/gender segment rules) plus **complete wilayah hierarchy** for the district code. |
| Does **not** include | Chained expectations such as `expectBirthDate`, `expectGender`, `expectProvince`, age rules, etc. For those, use the fluent `Query` API — [Expectations and aliases](#expectations-and-aliases). |

**Composing with `required` / `nullable`**

Use Laravel’s built-in rules for presence: `required|string|…` when the field must be present, or `nullable|string|…` when it is optional. The `KtpNik` rule **does not fail** on `null` or `''`, so optional fields stay easy to express without fighting the custom rule.

**Input types**

Integer or numeric string values are cast to string before validation. Arrays, objects, and booleans fail. In practice, pair the rule with Laravel’s `string` rule as in the examples above.

**Messages and localization**

The default English message lives under the `indonesian-ktp` namespace (`validation.ktp_nik`). Override or translate it like any vendor lang line (for example files under `lang/vendor/indonesian-ktp`). See [Laravel localization](https://laravel.com/docs/localization).

**Custom wilayah data**

If you rebind [`RegionHierarchyLookup`](src/Regions/Lookup/RegionHierarchyLookup.php), `KTP::nik()` uses it when the container is available — so these validator rules pick up the same lookup. See [Region hierarchy lookup](#region-hierarchy-lookup).

### Quick checks

`match*` helpers compare the NIK to a value; they do **not** add `expect*` rules to the query.

**Gender and birth date**

```php
use Bensondevs\IndonesianKtp\Gender;
use Bensondevs\IndonesianKtp\KTP;

$query = KTP::nik('3315131501901235');

$query->matchBirthDate('1990-01-15');
$query->matchGender(Gender::Male);
$query->matchGender('male');
```

**Age / minimum age:** `matchAge()` / `matchAtLeastYears()` need a resolved birth year — use `asOf()` as in [Two-digit birth years](#two-digit-birth-years-ambiguity-and-asof).

### Expectations and aliases

Chain then call `isValid()` or `validate()`. Each chained call returns a new `Query`.

| Area | `expect*` | Alias |
| --- | --- | --- |
| Birth date | `expectBirthDate` | `birthDate` |
| Integer age | `expectAge` | `age` |
| Minimum age | `expectAtLeastYears`, `expectSeventeenOrOlder`, `expectTwentyOneOrOlder` | — |
| Gender | `expectGender` | `gender` |
| Province | `expectProvince` | `province` |
| Regency | `expectRegency` | `regency` |
| Subdistrict | `expectSubdistrict` | `subdistrict` |

**Full names**

```php
use Bensondevs\IndonesianKtp\Gender;
use Bensondevs\IndonesianKtp\KTP;

KTP::nik('3315131501901235')
    ->expectBirthDate('1990-01-15')
    ->expectGender(Gender::Male)
    ->expectProvince('jawa tengah')
    ->isValid();
```

**Aliases**

```php
use Bensondevs\IndonesianKtp\Gender;
use Bensondevs\IndonesianKtp\KTP;

KTP::nik('3315131501901235')
    ->birthDate('1990-01-15')
    ->gender(Gender::Male)
    ->province('jawa tengah')
    ->isValid();
```

`isValid()` is the same as `validate()->isFullyValid()` (structure + hierarchy + every set expectation). Chaining `expectAge` / `age` needs `asOf()` — see [Two-digit birth years](#two-digit-birth-years-ambiguity-and-asof).

### validate() and ValidationResult

[`ValidationResult`](src/NIK/ValidationResult.php) exposes **methods** (not public properties).

| Method | Return | Notes |
| --- | --- | --- |
| `hasValidStructure()` | `bool` | |
| `hasValidRegionHierarchy()` | `bool` | |
| `hasValidBirthDate()` | `bool` or `null` | `null` = expectation not set |
| `hasValidGender()` | `bool` or `null` | |
| `hasValidProvince()` | `bool` or `null` | |
| `hasValidRegency()` | `bool` or `null` | |
| `hasValidSubdistrict()` | `bool` or `null` | |
| `hasValidAge()` | `bool` or `null` | |
| `hasValidMinimumAge()` | `bool` or `null` | |
| `isFullyValid()` | `bool` | Same as `isValid()` on the `Query` |

| Alias | Equivalent |
| --- | --- |
| `hasValidKabupaten()`, `hasValidCity()` | `hasValidRegency()` |
| `hasValidKecamatan()` | `hasValidSubdistrict()` |

```php
use Bensondevs\IndonesianKtp\KTP;

$validationResult = KTP::nik('3315131501901235')->validate();

$validationResult->hasValidStructure();
$validationResult->hasValidRegionHierarchy();
$validationResult->hasValidGender(); // null — no expectation

$validationResult = KTP::nik('3315131501901235')
    ->birthDate('1990-01-01')
    ->validate();

$validationResult->hasValidBirthDate(); // false — mismatch
```

```php
use Bensondevs\IndonesianKtp\KTP;

$validationResult = KTP::nik('3315131501901235')->validate();

$validationResult->isFullyValid(); // same as isValid() on the query
```

### Parsed values

`parsed()` returns a [`Parsed`](src/NIK/Parsed.php) snapshot (read-only fields from the NIK). `KTP::nik(...)->parsed()` attaches wilayah **names** from the app’s region lookup when the district code is known; use `provinceCode()` / `regencyCode()` / `districtCode()` for keys from the NIK alone.

| Method | Role |
| --- | --- |
| `raw()` | Normalized 16-digit string |
| `structureValid()` | Structural segment checks |
| `provinceCode()`, `regencyCode()`, `districtCode()` | Wilayah **codes** from the NIK (e.g. `33`, `33.15`, `33.15.13`) |
| `province()`, `provinsi()` | Province **display name** when the bound lookup resolves the district (e.g. `Jawa Tengah`); `null` if unknown or parser-only `Parsed` without `withRegionHierarchy()` |
| `regency()`, `kabupaten()`, `kota()`, `city()` | Regency / city **display name** when resolved (same value for all four; NIK does not distinguish kabupaten vs kota); `null` otherwise |
| `district()`, `kecamatan()` | Kecamatan **display name** when resolved; `null` otherwise |
| `birthDate()` | Single date, or `null` if two-digit year is ambiguous ([Two-digit birth years](#two-digit-birth-years-ambiguity-and-asof)) |
| `possibleBirthDates()` | All plausible dates when ambiguous |
| `gender()`, `serial()` | Parsed gender / serial |
| `age($asOf?)`, `isAtLeastYears($min, $asOf)`, `isSeventeenOrOlder($asOf)`, `isTwentyOneOrOlder($asOf)` | Age helpers (conservative when ambiguous). `age()` with no argument uses the current instant (`Carbon::now()`). |

On the `Query`, `resolvedAge()` uses the pivot instant when you chained `asOf()` ([Two-digit birth years](#two-digit-birth-years-ambiguity-and-asof)). For real validation, prefer `validate()` / `isValid()`.

```php
use Bensondevs\IndonesianKtp\KTP;
use Carbon\Carbon;

$parsed = KTP::nik('3315131501901235')->parsed();

$parsed->raw();
$parsed->structureValid();
$parsed->districtCode();              // NIK wilayah key, e.g. "33.15.13"
$parsed->provinceCode();              // "33"
$parsed->regencyCode();               // "33.15"
$parsed->province();                 // e.g. "Jawa Tengah" — null if lookup has no row
$parsed->regency();                  // e.g. "Kabupaten Grobogan"; alias: city(), kabupaten(), kota()
$parsed->district();                 // e.g. "Purwodadi"; alias: kecamatan()
$parsed->birthDate();                // null if ambiguous (no asOf on query)
$parsed->possibleBirthDates();
$parsed->gender();
$parsed->serial();
$parsed->age();                      // optional asOf; defaults to now()
$parsed->age(Carbon::parse('2026-01-01'));
$parsed->isSeventeenOrOlder(Carbon::parse('2026-01-01'));
```

### Region inputs

[`NikRegionMatcher`](src/Regions/Matching/NikRegionMatcher.php): province, regency, and subdistrict each accept **codes** (int / string shapes) or **names**. More cases: [`tests/Feature/Ktp/KtpRegionInputsTest.php`](tests/Feature/Ktp/KtpRegionInputsTest.php).

```php
use Bensondevs\IndonesianKtp\KTP;

$sampleNik = '3315131501901235';

KTP::nik($sampleNik)->expectProvince(33)->isValid();
KTP::nik($sampleNik)->expectProvince('JAWA TENGAH')->isValid();
```

```php
use Bensondevs\IndonesianKtp\KTP;

KTP::nik('3315131501901235')
    ->expectRegency(15) // province taken from NIK (33…)
    ->isValid();
```

```php
use Bensondevs\IndonesianKtp\KTP;

KTP::nik('3315131501901235')
    ->expectRegency(15)
    ->expectSubdistrict(13)
    ->isValid();
```

Unknown district:

```php
use Bensondevs\IndonesianKtp\KTP;

KTP::nik('9999991501900001')->isValid(); // false
```

### Two-digit birth years: ambiguity and asOf()

```php
use Bensondevs\IndonesianKtp\KTP;
use Carbon\Carbon;

// No asOf: every plausible century for YY that fits a 17–120 age window (evaluated at query build time)
KTP::nik('3315131501901235');

// With asOf: single resolved birth year for that pivot
KTP::nik('3315131501901235')->asOf(Carbon::parse('2026-01-01'));
```

**`matchAge` / minimum age** (needs the same pivot):

```php
use Bensondevs\IndonesianKtp\KTP;
use Carbon\Carbon;

$query = KTP::nik('3315131501901235')->asOf(Carbon::parse('2026-01-01'));

$query->matchAge(35);
$query->matchAtLeastYears(21);
```

| Topic | Behaviour |
| --- | --- |
| Birth / `expectBirthDate` | Ambiguous: any matching candidate wins. Pivot: one resolved year. |
| `parsed()->birthDate()` | `null` if multiple candidates; use `possibleBirthDates()`. Pivot: always set when structure is valid. |
| `age` / `resolvedAge()` | Can stay `null` until year is unique; use `asOf()` or derive from `possibleBirthDates()`. |
| Minimum-age helpers | **Conservative:** every plausible birth candidate must pass. |

**Edge cases:** 17–120 uses calendar boundaries; odd ages or midnight tests may need your own `asOf()`. Examples: [`tests/Feature/Ktp/KtpTwoDigitYearAndAsOfTest.php`](tests/Feature/Ktp/KtpTwoDigitYearAndAsOfTest.php), [`tests/Unit/NIK/ParserTest.php`](tests/Unit/NIK/ParserTest.php).

```php
use Bensondevs\IndonesianKtp\KTP;
use Carbon\Carbon;

Carbon::setTestNow('2026-09-01');

$ambiguousNik = '3315130109090002';

KTP::nik($ambiguousNik)->parsed()->birthDate();              // null
count(KTP::nik($ambiguousNik)->parsed()->possibleBirthDates()); // 2

KTP::nik($ambiguousNik)->asOf(Carbon::parse('2026-09-01'))->parsed()->birthDate(); // single date

Carbon::setTestNow();
```

Eloquent: same behaviour via `indonesianKtpReferenceDate()` — [Reference date](#reference-date-indonesianktpreferencedate) under [NIK column and accessors](#nik-column-and-accessors).

### Region hierarchy lookup

- **Auto-discovery:** [`IndonesianKtpServiceProvider`](src/IndonesianKtpServiceProvider.php) registers [`RegionHierarchyLookup`](src/Regions/Lookup/RegionHierarchyLookup.php) → bundled [`data/wilayah.php`](data/wilayah.php) via [`FileRegionHierarchyLookup`](src/Regions/Lookup/FileRegionHierarchyLookup.php).
- **[`KTP::nik()`](src/KTP.php)** uses the container when the contract is bound; otherwise the bundled path (e.g. some unit tests).

Custom compiled file (same PHP array format), rebind **after** the package provider:

```php
use Bensondevs\IndonesianKtp\Regions\Lookup\FileRegionHierarchyLookup;
use Bensondevs\IndonesianKtp\Regions\Lookup\RegionHierarchyLookup;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RegionHierarchyLookup::class, function (): FileRegionHierarchyLookup {
            $path = storage_path('app/wilayah.php'); // your compiled file

            return new FileRegionHierarchyLookup($path);
        });
    }
}
```

### Eloquent HasIndonesianKtp

[`HasIndonesianKtp`](src/Concerns/HasIndonesianKtp.php) reads the **NIK column** via `getAttribute()` (casts and accessors apply). Comparisons against birth date, age, gender, and wilayah fields are **explicit**: you pass the value you want checked (for example from another column, a relation, or request input). The trait does not scan `nik_*` or fallback attribute names for you.

```php
use Bensondevs\IndonesianKtp\Concerns\HasIndonesianKtp;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasIndonesianKtp;

    // Optional: if your NIK column is not named `nik`
    // protected function getIndonesianKtpNikColumn(): string
    // {
    //     return 'id_number';
    // }
}
```

```php
// $model is an Eloquent model using HasIndonesianKtp

$model->hasValidNik();
$model->nikGenderIs($model->gender);
$model->nikProvinceIs($model->province);
$model->nikAgeIs((int) $model->age);
```

Default NIK attribute name: `nik` (override `getIndonesianKtpNikColumn()`). Non-digits are stripped after read.

#### Explicit matchers

Each `nik*Is($value)` method compares the **argument** to what is encoded or implied by the NIK. Extra attributes on the model are ignored unless you pass them in. Each short name has a long alias (`indonesianIdNumber*Is`) for consistency with the rest of the package.

#### Trait methods

| Method | Purpose |
| --- | --- |
| `hasValidNik()` | Structure + district hierarchy; same as `hasValidIndonesianIdNumber()` |
| `nikBirthdateIs(mixed $birth)` | NIK birth segment matches `$birth`; alias `indonesianIdNumberBirthdateIs()` |
| `nikGenderIs()` | NIK gender matches the argument (`Gender` or `string`); alias `indonesianIdNumberGenderIs()` |
| `nikProvinceIs(mixed $expected)` | Wilayah province expectation; alias `indonesianIdNumberProvinceIs()` |
| `nikRegencyIs(mixed $expected)` | Regency expectation; aliases `indonesianIdNumberRegencyIs()`, `nikKabupatenIs()`, `nikCityIs()`, `nikDistrictIs()` and matching `indonesianIdNumber*` forms |
| `nikSubdistrictIs(mixed $expected)` | Subdistrict expectation; aliases `indonesianIdNumberSubdistrictIs()`, `nikKecamatanIs()`, `indonesianIdNumberKecamatanIs()` |
| `nikAgeIs(int $age)` | Completed age from the NIK (per reference date rules) equals `$age` when unambiguous; alias `indonesianIdNumberAgeIs()` |
| `ageFromNik()` | Completed full years from the NIK at the trait’s reference instant; `null` when ambiguous; alias `ageFromIndonesianIdNumber()` |
| `isSeventeenOrOlderFromNik()` | Conservative 17+ check over all birth candidates; alias `isSeventeenOrOlderFromIndonesianIdNumber()` |
| `isTwentyOneOrOlderFromNik()` | Conservative 21+ check; alias `isTwentyOneOrOlderFromIndonesianIdNumber()` |
| `isAtLeastYearsFromNik(int $years)` | Conservative minimum-age check; alias `isAtLeastYearsFromIndonesianIdNumber()` |

### NIK column and accessors

Override `getIndonesianKtpNikColumn()` and/or use an accessor on that column. The trait does not expose raw NIK normalization beyond stripping non-digits after `getAttribute`.

#### NIK column name

```php
protected function getIndonesianKtpNikColumn(): string
{
    return 'national_id';
}
```

#### NIK value (accessor on the configured column)

Formatted storage → normalize in an accessor; the trait still strips non-digits after `getAttribute`.

```php
// With default getIndonesianKtpNikColumn() => 'nik'
protected function getNikAttribute(?string $value): ?string
{
    return $value !== null ? preg_replace('/\D/', '', $value) : null;
}
```

#### Applicant-style: custom column names, explicit matchers

When birth date, gender, or wilayah live on other attributes or relations, read them yourself and pass them into `nik*Is`:

```php
use Bensondevs\IndonesianKtp\Concerns\HasIndonesianKtp;
use Illuminate\Database\Eloquent\Model;

class Applicant extends Model
{
    use HasIndonesianKtp;

    protected function getIndonesianKtpNikColumn(): string
    {
        return 'identity_number';
    }
}

// Validation-style usage (attributes / casts apply on reads):
$applicant->hasValidNik()
    && $applicant->nikBirthdateIs($applicant->profile?->dob ?? $applicant->legacy_birthdate)
    && $applicant->nikGenderIs($applicant->sex_code)
    && $applicant->nikProvinceIs($applicant->prov_name)
    && $applicant->nikRegencyIs($applicant->kab_name)
    && $applicant->nikSubdistrictIs($applicant->kec_name);
```

#### Reference date (indonesianKtpReferenceDate)

Default `null` → ambiguous two-digit years (like `KTP::nik($nik)` without `asOf()`). Return `Carbon::now()` (or any pivot) so every internal trait query uses `asOf()` for YY resolution.

```php
use Carbon\Carbon;
use Carbon\CarbonInterface;

protected function indonesianKtpReferenceDate(): ?CarbonInterface
{
    return null; // ambiguous
}

protected function indonesianKtpReferenceDate(): ?CarbonInterface
{
    return Carbon::now(); // pivot on “now”
}
```

## Develop and test 🧪

```bash
composer install && composer test
```

## Data source

Hierarchy file: [`data/wilayah.php`](data/wilayah.php) (from [cahyadsn/wilayah](https://github.com/cahyadsn/wilayah), MIT). Attribution: [`NOTICE`](NOTICE). Maintainers can compile from [upstream `db/wilayah.sql`](https://github.com/cahyadsn/wilayah) and ship their own `wilayah.php`; this repo has no compile script.

## Security 🔒

Validation does **not** send NIKs off-device. Treat NIKs as sensitive in logs and traces. Disclosure: [`SECURITY.md`](SECURITY.md).

## Versioning and support

[Semantic Versioning](https://semver.org/). Upgrades: [`CHANGELOG.md`](CHANGELOG.md).

- **Releases:** [Packagist — bensondevs/indonesian-ktp](https://packagist.org/packages/bensondevs/indonesian-ktp)
- **Changelog:** [`CHANGELOG.md`](CHANGELOG.md)
- **Contributing:** [`CONTRIBUTING.md`](CONTRIBUTING.md)
- **Security:** [`SECURITY.md`](SECURITY.md)

Match supported Laravel majors to [`composer.json`](composer.json) `illuminate/*` constraints when upgrading.
