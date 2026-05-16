<?php

declare(strict_types=1);

use Bensondevs\IndonesianKtp\Rules\KtpNik;
use Illuminate\Support\Facades\Validator;

test('KtpNik rule object accepts valid NIK', function () {
    $v = Validator::make(
        ['nik' => '3315131501901235'],
        ['nik' => ['required', 'string', new KtpNik]],
    );

    expect($v->passes())->toBeTrue();
});

test('ktp-nik string rule accepts valid NIK', function () {
    $v = Validator::make(
        ['nik' => '3315131501901235'],
        ['nik' => ['required', 'string', 'ktp-nik']],
    );

    expect($v->passes())->toBeTrue();
});

test('ktp_nik string rule alias accepts valid NIK', function () {
    $v = Validator::make(
        ['nik' => '3315131501901235'],
        ['nik' => ['required', 'string', 'ktp_nik']],
    );

    expect($v->passes())->toBeTrue();
});

test('rule object rejects invalid structure', function () {
    $v = Validator::make(
        ['nik' => '123'],
        ['nik' => ['required', 'string', new KtpNik]],
    );

    expect($v->fails())->toBeTrue();
});

test('ktp-nik rejects unknown district', function () {
    $v = Validator::make(
        ['nik' => '9999991501900001'],
        ['nik' => ['required', 'string', 'ktp-nik']],
    );

    expect($v->fails())->toBeTrue();
});

test('nullable with null passes without evaluating invalid NIK', function () {
    $v = Validator::make(
        ['nik' => null],
        ['nik' => ['nullable', 'string', 'ktp-nik']],
    );

    expect($v->passes())->toBeTrue();
});

test('nullable with empty string passes', function () {
    $v = Validator::make(
        ['nik' => ''],
        ['nik' => ['nullable', 'string', 'ktp-nik']],
    );

    expect($v->passes())->toBeTrue();
});

test('required fails when key missing', function () {
    $v = Validator::make(
        [],
        ['nik' => ['required', 'string', 'ktp-nik']],
    );

    expect($v->fails())->toBeTrue();
});

test('numeric scalar passes same as string NIK', function () {
    $v = Validator::make(
        ['nik' => 3315131501901235],
        ['nik' => ['required', new KtpNik]],
    );

    expect($v->passes())->toBeTrue();
});

test('non-scalar value fails rule object', function () {
    $v = Validator::make(
        ['nik' => ['unexpected']],
        ['nik' => ['required', new KtpNik]],
    );

    expect($v->fails())->toBeTrue();
});

test('ktp-nik failure uses translation message', function () {
    $v = Validator::make(
        ['nik' => '123'],
        ['nik' => ['required', 'string', 'ktp-nik']],
    );

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->first('nik'))->toContain('NIK');
});
