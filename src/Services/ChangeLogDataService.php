<?php

namespace Iperamuna\LaravelChangelog\Services;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use League\CommonMark\Exception\CommonMarkException;

/**
 * Service for managing structured Changelog data held in a temporary store.
 *
 * Data shape example:
 * [
 *   'heading' => [
 *       'heading' => 'Changelog',
 *       'content' => string[],             // arbitrary heading content lines
 *   ],
 *   'versions' => [
 *       'Unreleased' => [
 *           'heading' => 'Unreleased',
 *           'url'     => '',
 *           'date'    => '',
 *           'content' => array{ChangeType => string[]},
 *       ],
 *       '1.2.3 - 2025-09-15' => [
 *           'release' => '1.2.3 - 2025-09-15',
 *           'heading' => '1.2.3',
 *           'date'    => '2025-09-15',
 *           'url'     => 'https://...',
 *           'content' => array{ChangeType => string[]},
 *       ],
 *       ...
 *   ],
 * ]
 */
class ChangeLogDataService
{
    private const UNRELEASED_KEY = 'Unreleased';

    private ChangeLogService $changelogService;

    /** @var array<string, mixed> */
    private array $defaultChangeLogData = [
        'heading' => [
            'heading' => 'Changelog',
            'content' => [],
        ],
        'versions' => [],
    ];

    /** @var array<string, mixed> */
    private array $changeLogData;

    /**
     * @throws FileNotFoundException
     * @throws CommonMarkException
     */
    public function __construct(ChangeLogService $changelogService)
    {
        $this->changelogService = $changelogService;
        $this->changeLogData = $this->changelogService->getTemporaryChangeLog() ?: $this->defaultChangeLogData;
    }

    /**
     * Replace the in-memory changelog data.
     *
     * @param array<string, mixed> $data
     */
    public function setChangeLogData(array $data): void
    {
        $this->changeLogData = $data;
    }

    /**
     * Get the most recent released (i.e., not "Unreleased") version entry.
     *
     * @return array<string, mixed>|null
     */
    public function getLastReleaseVersion(): ?array
    {
        foreach ($this->versions() as $key => $version) {
            if (($version['heading'] ?? null) === self::UNRELEASED_KEY) {
                continue;
            }
            return $version;
        }
        return null;
    }

    /**
     * Add a new released version. If "Unreleased" exists, insert directly after it.
     *
     * @param array<string, mixed> $releaseContent Requires 'release' key
     */
    public function addNewRelease(array $releaseContent): void
    {
        $releaseKeyed = [$releaseContent['release'] => $releaseContent];
        $versions = $this->versions();

        if (\array_key_exists(self::UNRELEASED_KEY, $versions)) {
            $this->changeLogData['versions'] = $this->insertAfterKey(
                $versions,
                self::UNRELEASED_KEY,
                $releaseKeyed
            );
        } else {
            $this->changeLogData['versions'] = $this->prependAssoc($versions, $releaseKeyed);
        }

        $this->persist();
    }

    /**
     * Initialize the changelog with a heading and (optionally) an Unreleased block.
     *
     * @param string[]              $headerContent
     * @param array<string, mixed>  $unReleasedVersion
     */
    public function initChangeLogData(array $headerContent, array $unReleasedVersion = []): void
    {
        $data = $this->defaultChangeLogData;
        $data['heading']['content'] = $headerContent;

        if (!empty($unReleasedVersion)) {
            $data['versions'][self::UNRELEASED_KEY] = $unReleasedVersion;
        }

        $this->changeLogData = $data;
        $this->persist();
    }

    /**
     * Get the Unreleased block if present.
     *
     * @return array<string, mixed>|null
     */
    public function getUnreleasedVersion(): ?array
    {
        return $this->changeLogData['versions'][self::UNRELEASED_KEY] ?? null;
    }

    /**
     * Create/replace the Unreleased block and move it to the top.
     *
     * @param array<string, mixed> $sections e.g. ['Added' => [...], 'Changed' => [...]]
     */
    public function setUnreleasedVersion(array $sections): void
    {
        $version = [
            'heading' => self::UNRELEASED_KEY,
            'url'     => '',
            'date'    => '',
            'content' => $sections,
        ];

        $versions = $this->versions();
        // Remove existing unreleased to avoid duplicates before re-prepending
        unset($versions[self::UNRELEASED_KEY]);

        $this->changeLogData['versions'] = $this->prependAssoc(
            $versions,
            [self::UNRELEASED_KEY => $version]
        );

        $this->persist();
    }

    /**
     * Helper to standardize a release entry.
     *
     * @param string                $newReleaseVersion e.g., "1.2.3"
     * @param string                $releaseDate       e.g., "2025-09-15"
     * @param string                $releaseUrl
     * @param array<string, mixed>  $releaseSectionContent
     * @return array<string, mixed>
     */
    public function formatReleaseContent(
        string $newReleaseVersion,
        string $releaseDate,
        string $releaseUrl,
        array $releaseSectionContent
    ): array {
        return [
            'release' => "{$newReleaseVersion} - {$releaseDate}",
            'heading' => $newReleaseVersion,
            'date'    => $releaseDate,
            'url'     => $releaseUrl,
            'content' => $releaseSectionContent,
        ];
    }

    /**
     * Get a map of release keys.
     *
     * @return array<string, string> key => key
     */
    public function getChangeLogReleases(): array
    {
        $out = [];
        foreach ($this->versions() as $releaseKey => $_) {
            $out[$releaseKey] = $releaseKey;
        }
        return $out;
    }

    /**
     * Get the stored content for a specific release.
     *
     * @return array<string, mixed>|null
     */
    public function getReleaseContent(string $releaseVersion): ?array
    {
        return $this->versions()[$releaseVersion] ?? null;
    }

    /**
     * Update a release's content and, if its 'release' key changed, rename the array key
     * while preserving the original position.
     *
     * @param array<string, mixed> $releaseContentFormatted Must include 'release'
     */
    public function updateRelease(string $selectedRelease, array $releaseContentFormatted): void
    {
        $newKey   = $releaseContentFormatted['release'];
        $versions = $this->versions();

        if (!\array_key_exists($selectedRelease, $versions)) {
            // Nothing to update; silently ignore or throw if you prefer:
            // throw new \InvalidArgumentException("Release '{$selectedRelease}' not found.");
            return;
        }

        // Rename (if needed) and replace value in the same position.
        $this->changeLogData['versions'] = $this->renameKeyPreserveOrder(
            $versions,
            $selectedRelease,
            $newKey,
            $releaseContentFormatted
        );

        $this->persist();
    }

    /**
     * Promote the Unreleased changes into a new release entry and place it at the top;
     * removes the Unreleased block.
     *
     * @param array<string, mixed> $releaseContentFormatted Must include 'release'
     */
    public function promoteUnreleased(array $releaseContentFormatted): void
    {
        $versions = $this->versions();

        // Remove Unreleased if present
        unset($versions[self::UNRELEASED_KEY]);

        // Prepend new release
        $releaseKeyed = [$releaseContentFormatted['release'] => $releaseContentFormatted];
        $this->changeLogData['versions'] = $this->prependAssoc($versions, $releaseKeyed);

        $this->persist();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @return array<string, array<string, mixed>>
     */
    private function versions(): array
    {
        /** @var array<string, array<string, mixed>> $versions */
        $versions = $this->changeLogData['versions'] ?? [];
        return $versions;
    }

    /**
     * Persist current changeLogData to the backing service.
     */
    private function persist(): void
    {
        $this->changelogService->setTemporaryChangeLog($this->changeLogData);
    }

    /**
     * Insert an associative chunk immediately after a specific key.
     *
     * @param array<string, mixed> $array
     * @param string               $afterKey
     * @param array<string, mixed> $toInsertKeyed single or multiple entries to insert
     * @return array<string, mixed>
     */
    private function insertAfterKey(array $array, string $afterKey, array $toInsertKeyed): array
    {
        $out = [];
        foreach ($array as $k => $v) {
            $out[$k] = $v;
            if ($k === $afterKey) {
                // Keep insertion order of provided chunk
                foreach ($toInsertKeyed as $ik => $iv) {
                    $out[$ik] = $iv;
                }
            }
        }
        return $out;
    }

    /**
     * Prepend one or more associative entries to an associative array (preserves relative order).
     *
     * @param array<string, mixed> $array
     * @param array<string, mixed> $toPrependKeyed
     * @return array<string, mixed>
     */
    private function prependAssoc(array $array, array $toPrependKeyed): array
    {
        // array_merge on assoc arrays is left-to-right: earlier keys stay earlier.
        return \array_merge($toPrependKeyed, $array);
    }

    /**
     * Rename an associative array key while preserving order; replace value with $newValue.
     *
     * @param array<string, mixed> $array
     * @return array<string, mixed>
     */
    private function renameKeyPreserveOrder(
        array $array,
        string $oldKey,
        string $newKey,
        array $newValue
    ): array {
        if ($oldKey === $newKey) {
            // Simple replace in place
            $array[$oldKey] = $newValue;
            return $array;
        }

        $out = [];
        foreach ($array as $k => $v) {
            if ($k === $oldKey) {
                $out[$newKey] = $newValue;
            } else {
                $out[$k] = $v;
            }
        }
        return $out;
    }
}
