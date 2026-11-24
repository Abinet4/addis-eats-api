<?php

declare(strict_types=1);

use App\Rules\Santim;
use App\Rules\TeleBirrNumber;
use Illuminate\Support\Facades\Validator;

function passes(mixed $value, object $rule): bool
{
    return Validator::make(['v' => $value], ['v' => [$rule]])->passes();
}

it('accepts only 09 followed by eight digits', function (mixed $phone, bool $ok) {
    expect(passes($phone, new TeleBirrNumber))->toBe($ok);
})->with([
    ['0911234567', true],
    ['0999999999', true],
    ['911234567', false],      // missing the leading 0: never guessed
    ['+251911234567', false],  // the rule alone does not normalise; the Form Request does
    ['09112345678', false],    // eleven digits
    ['0811234567', false],
    ['091123456a', false],
    [911234567, false],        // a JSON number loses the leading zero
    // '' is not listed: custom rules do not run on empty values; 'required' reports those.
]);

it('accepts money only as whole santim', function (mixed $value, bool $ok) {
    expect(passes($value, new Santim))->toBe($ok);
})->with([
    [68500, true], ['68500', true], [1, true], [1_000_000, true],
    [0, false], [-500, false], [1_000_001, false],
    [685.0, false],    // a float, even a whole one
    [685.5, false], ['685.00', false], [true, false], ['1e3', false], [null, false], [[68500], false],
]);

it('shows why Laravel\'s integer rule alone is not enough for money', function () {
    expect(Validator::make(['p' => true], ['p' => ['integer']])->passes())->toBeTrue()
        ->and(Validator::make(['p' => 685.0], ['p' => ['integer']])->passes())->toBeTrue();
});
