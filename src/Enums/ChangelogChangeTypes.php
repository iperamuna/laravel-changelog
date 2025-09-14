<?php

namespace Iperamuna\LaravelChangelog\Enums;

enum ChangelogChangeTypes: string
{

    case Added = 'Added';
    case Changed = 'Changed';
    case Deprecated = 'Deprecated';
    case Removed = 'Removed';
    case Fixed = 'Fixed';
    case Security = 'Security';

    public function description(): string
    {
        return match ($this) {
            self::Added => 'Added for new feature',
            self::Changed => 'Changed for changes in existing functionality',
            self::Deprecated => 'Deprecated for soon-to-be removed features',
            self::Removed => 'Removed for now removed features',
            self::Fixed => 'Fixed for any bug fixes',
            self::Security => 'Security in case of vulnerabilities',
        };
    }
}
