<?php

namespace Iperamuna\LaravelChangelog\Rules;

use Illuminate\Contracts\Validation\Rule as RuleContract;

class SemverRule implements RuleContract
{
    // SemVer 2.0.0 (major.minor.patch[-prerelease][+build])
    public const REGEX = '/^
        (0|[1-9]\d*)\.
        (0|[1-9]\d*)\.
        (0|[1-9]\d*)
        (?:-
            (?:0|[1-9A-Za-z-][0-9A-Za-z-]*)
            (?:\.(?:0|[1-9A-Za-z-][0-9A-Za-z-]*))*
        )?
        (?:\+
            [0-9A-Za-z-]+
            (?:\.[0-9A-Za-z-]+)*
        )?
    $/x';

    public function passes($attribute, $value): bool
    {
        return is_string($value) && preg_match(self::REGEX, $value) === 1;
    }

    public function message(): string
    {
        //1.2.3-alpha.1, 1.2.3+build.7
        return 'The :attribute must be a valid semantic version (e.g. 1.2.3).';

    }
}
