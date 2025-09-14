<?php

namespace Iperamuna\LaravelChangelog\Services;

use InvalidArgumentException;

class SemverService
{
    /**
     * Bump a semantic version.
     *
     * Supported types:
     *  - major, minor, patch
     *  - premajor, preminor, prepatch
     *  - prerelease (increments existing pre tag; if none, bumps patch and adds -{identifier}.0)
     *
     * @param  string  $version     e.g. "1.2.3", "1.2.3-alpha.1+build.7"
     * @param  string  $type        one of: major|minor|patch|premajor|preminor|prepatch|prerelease
     * @param  string  $identifier  prerelease identifier to use when creating one (e.g. "rc", "alpha", "beta")
     * @param  bool    $resetBuild  if true, drops +build metadata after bump
     * @return string
     */
    public function bump(string $version, string $type, string $identifier = 'rc', bool $resetBuild = true): string
    {
        $parts = $this->parse($version);
        if (!$parts) {
            throw new InvalidArgumentException("Invalid semantic version: {$version}");
        }

        [$major, $minor, $patch, $pre, $build] = $parts;

        $type = strtolower($type);
        switch ($type) {
            case 'major':
                $major++;
                $minor = 0; $patch = 0;
                $pre = null;
                break;

            case 'minor':
                $minor++;
                $patch = 0;
                $pre = null;
                break;

            case 'patch':
                $patch++;
                $pre = null;
                break;

            case 'premajor':
                $major++;
                $minor = 0; $patch = 0;
                $pre = "{$identifier}.0";
                break;

            case 'preminor':
                $minor++;
                $patch = 0;
                $pre = "{$identifier}.0";
                break;

            case 'prepatch':
                // If already a prerelease, continue that track; otherwise bump patch first.
                if ($pre === null) {
                    $patch++;
                    $pre = "{$identifier}.0";
                } else {
                    $pre = $this->incrementPrerelease($pre, $identifier);
                }
                break;

            case 'prerelease':
                // If already prerelease, increment numeric tail (and/or switch identifier).
                // If none, create from current by bumping patch.
                if ($pre === null) {
                    $patch++;
                    $pre = "{$identifier}.0";
                } else {
                    $pre = $this->incrementPrerelease($pre, $identifier);
                }
                break;

            default:
                throw new InvalidArgumentException("Unsupported bump type: {$type}");
        }

        if ($resetBuild) {
            $build = null;
        }

        return $this->stringify([$major, $minor, $patch, $pre, $build]);
    }

    /**
     * Parse a semver string into [major, minor, patch, prerelease|null, build|null].
     */
    private function parse(string $version): ?array
    {
        $re = '/^
            (?P<major>0|[1-9]\d*)\.
            (?P<minor>0|[1-9]\d*)\.
            (?P<patch>0|[1-9]\d*)
            (?:-(?P<prerelease>(?:0|[1-9A-Za-z-][0-9A-Za-z-]*)
                (?:\.(?:0|[1-9A-Za-z-][0-9A-Za-z-]*))*))?
            (?:\+(?P<build>[0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?
        $/x';

        if (!preg_match($re, $version, $m)) {
            return null;
        }

        if (!isset($m['prerelease'])) {
            $m['prerelease'] = null;
        }

        if (!isset($m['build'])) {
            $m['build'] = null;
        }

        return [
            (int) $m['major'],
            (int) $m['minor'],
            (int) $m['patch'],
            $m['prerelease'] !== '' ? $m['prerelease'] : null,
            $m['build'] !== '' ? $m['build'] : null,
        ];
    }

    /**
     * Turn parts back into a semver string.
     */
    private function stringify(array $parts): string
    {
        [$major, $minor, $patch, $pre, $build] = $parts;

        $out = "{$major}.{$minor}.{$patch}";
        if ($pre !== null && $pre !== '') {
            $out .= "-{$pre}";
        }
        if ($build !== null && $build !== '') {
            $out .= "+{$build}";
        }
        return $out;
    }

    /**
     * Increment a prerelease segment string (e.g. "alpha.1") intelligently.
     * If $pre starts with a different identifier, switch to "$identifier.0".
     * If same identifier, increment last numeric part; if none, append ".0".
     */
    private function incrementPrerelease(string $pre, string $identifier): string
    {
        $segments = explode('.', $pre);

        // If first token doesn't match the desired identifier, reset to identifier.0
        if (!isset($segments[0]) || strtolower($segments[0]) !== strtolower($identifier)) {
            return "{$identifier}.0";
        }

        // Find the last numeric segment to increment; if none, append .0
        for ($i = count($segments) - 1; $i >= 1; $i--) {
            if (ctype_digit($segments[$i])) {
                $segments[$i] = (string) ((int) $segments[$i] + 1);
                return implode('.', $segments);
            }
        }

        // No numeric tail → add .0
        return implode('.', array_merge($segments, ['0']));
    }
}
