<?php

namespace Iperamuna\LaravelChangelog\Enums;

enum SemanticVersionTypes: string
{
    case MAJOR = 'Major';

    case MINOR = 'Minor';

    case PATCH = 'Patch';

    public function description(): string
    {
        return match ($this) {
            self::MAJOR => 'Increment the major version (X.0.0)',
            self::MINOR => 'Increment the minor version (1.X.0)',
            self::PATCH => 'Increment the patch version (1.0.X)',
        };
    }

    public function lowercase(): string
    {
        return strtolower($this->value);
    }
}
