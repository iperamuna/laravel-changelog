<?php

namespace Iperamuna\LaravelChangelog\Services;

use Illuminate\Cache\FileStore;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Exception\CommonMarkException;

class ChangeLogService
{
    /**
     * Path to the changelog Markdown file.
     */
    protected string $changelogPath;

    /**
     * Path to the temporary changelog JSON file.
     */
    protected string $temporaryChangelogFilePath;

    /**
     * Array of version links for the changelog.
     */
    protected array $versionLinks;

    /**
     * Initialize the ChangeLogService.
     */
    public function __construct()
    {
        $this->changelogPath = config('changelog.path') . '/' .config('changelog.filename');

        $dir = Cache::getStore() instanceof FileStore
            ? Cache::getStore()->getDirectory()
            : storage_path('framework/cache');

        $changePath = $dir . '/change';

        if (!File::exists($changePath)) {
            File::makeDirectory($changePath);
        }

        $this->temporaryChangelogFilePath = $changePath . '/temp-changelog.json';
        $this->versionLinks = [''];
    }

    public function isChangeLogExist(): bool
    {
        return File::exists($this->changelogPath);

    }

    /**
     * Convert markdown content to HTML.
     *
     * @return string The HTML content
     * @throws CommonMarkException|FileNotFoundException
     */
    public function getChangeLogHtml(): string
    {
        if (File::get($this->changelogPath) === false) {
            throw new FileNotFoundException('Changelog file not found');
        }

        $converter = new CommonMarkConverter();
        return $converter->convert(File::get($this->changelogPath))->getContent();
    }

    /**
     * Parse the changelog HTML into a structured array.
     *
     * @return array The parsed HTML elements
     * @throws CommonMarkException|FileNotFoundException
     */
    private function htmlizeChangeLog(): array
    {
        $htmlChangeLog = $this->getChangeLogHtml();

        $dom = new \DOMDocument();
        @$dom->loadHTML($htmlChangeLog);

        $parsedHtmlElements = [];
        $url = '';

        $bodyNode = $dom->getElementsByTagName('body')->item(0);

        if (!$bodyNode) {
            return [];
        }

        foreach ($bodyNode->childNodes as $node) {
            if ($node->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $children = [];
            $nodeName = $node->nodeName;

            // Extract URL from h2 elements
            if ($nodeName === 'h2') {
                foreach ($node->childNodes as $childNode) {
                    if ($childNode instanceof \DOMElement) {
                        $url = $childNode->getAttribute('href');
                    }
                }
            } else {
                $url = '';
            }

            // Extract children from ul elements
            if ($nodeName === 'ul') {
                $children = collect($node->childNodes)
                    ->filter(fn($text) => $text instanceof \DOMElement)
                    ->map(fn($text) => $text->textContent)
                    ->values()
                    ->all();
            }

            $parsedHtmlElements[] = [
                'tag' => $nodeName,
                'url' => $url,
                'content' => trim($node->textContent),
                'children' => $children,
            ];
        }

        return $parsedHtmlElements;
    }

    /**
     * Convert parsed HTML elements into a structured changelog array.
     *
     * @return array The structured changelog content
     * @throws CommonMarkException|FileNotFoundException
     */
    public function getChangeLogArrayFromParsedHtmlElements(): array
    {
        $parsedHtmlElements = $this->htmlizeChangeLog();

        $changeLogContent = [
            'heading' => [],
            'versions' => [],
        ];

        $parent = '';
        $currentVersion = '';
        $currentType = '';

        foreach ($parsedHtmlElements as $index => $item) {
            if ($item['tag'] == 'h1') {
                $parent = $item['tag'];
                $heading = $item['content'];
                $changeLogContent['heading']['heading'] = $heading;
                continue;
            }

            if ($parent == 'h1' && $item['tag'] == 'p') {
                $changeLogContent['heading']['content'][] = $item['content'];
                continue;
            }

            if ($item['tag'] == 'h2') {
                $parent = $item['tag'];
                $spitedContent = explode(' - ', $item['content']);
                $versionName = $spitedContent[0];
                $date = $spitedContent[1] ?? '';
                $currentVersion = $item['content'];
                Log::info('x'. $index, [$currentVersion]);;
                $changeLogContent['versions'][$item['content']] = [
                    'heading' => $versionName,
                    'date' => $date,
                    'url' => $item['url'],
                ];
                continue;
            }

            if ($item['tag'] == 'h3' || $item['tag'] == 'ul') {

                if ($item['tag'] == 'h3') {
                    $currentType = $item['content'];
                    $parent = $item['tag'];
                    Log::info('x'. $index, [$currentVersion, $currentType]);;
                    $changeLogContent['versions'][$currentVersion]['content'][$currentType] = [];
                    continue;
                }

                if ($item['tag'] == 'ul') {
                    $parent = $item['tag'];
                    Log::info('x'. $index, [$currentVersion, $currentType, $item['children']]);;
                    $changeLogContent['versions'][$currentVersion]['content'][$currentType] = $item['children'];
                }
            }
        }

        return $changeLogContent;
    }

    /**
     * Save the changelog content to a temporary JSON file.
     *
     * @param array $changeLogContent They changelog content to save
     * @return void
     */
    public function setTemporaryChangeLog(array $changeLogContent): void
    {
        File::put(
            $this->temporaryChangelogFilePath,
            json_encode($changeLogContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Get the changelog content from the temporary JSON file.
     *
     * @return array|null The changelog content or null if a file doesn't exist
     * @throws FileNotFoundException|CommonMarkException
     */
    public function getTemporaryChangeLog(): ?array
    {
        if (!File::exists($this->temporaryChangelogFilePath)) {
            /*try {
                $changeLogContent = $this->getChangeLogArrayFromParsedHtmlElements();
                $this->setTemporaryChangeLog($changeLogContent);
            } catch (FileNotFoundException $e) {

            } catch (CommonMarkException $e) {

            }*/

            $changeLogContent = $this->getChangeLogArrayFromParsedHtmlElements();
            $this->setTemporaryChangeLog($changeLogContent);

        }

        return json_decode(File::get($this->temporaryChangelogFilePath), true);
    }


    /**
     * Render the Markdown from a normalized array.
     *
     * @param array $data The structured changelog data
     * @return string The rendered Markdown content
     */
    private function renderMarkdown(array $data): string
    {
        // Header section
        $title = data_get($data, 'heading.heading', 'Changelog');
        $intro = collect(data_get($data, 'heading.content', []));

        $lines = collect(['# ' . $title, '']);

        // Add introduction paragraphs
        $intro->each(function ($paragraph) use ($lines) {
            $lines->push(Str::of($paragraph)->trim());
            $lines->push('');
        });

        // Get all versions
        $versions = collect(data_get($data, 'versions', []));

        // Find the "Unreleased" version if it exists
        $unreleasedKey = $versions->keys()
            ->first(fn($key) => Str::lower($key) === 'unreleased');

        // Build a sortable list of releases (excluding Unreleased)
        $releaseRows = $versions
            ->except($unreleasedKey)
            ->map(function ($version, $key) {
                $dateStr = Str::of(data_get($version, 'date', ''))->trim();
                return [
                    'key'   => $key,
                    'date'  => $dateStr,
                    'ts'    => $dateStr->isNotEmpty() ? strtotime($dateStr) : null,
                    'entry' => $version,
                ];
            })
            ->values();

        // Sort releases by date (newest first)
        $sortedReleases = $releaseRows->sort(function ($a, $b) {
            // Put releases with dates first, newest on top
            if ($a['ts'] === $b['ts']) return 0;
            if ($a['ts'] === null) return 1;
            if ($b['ts'] === null) return -1;
            return $b['ts'] <=> $a['ts'];
        });

        // Render the "Unreleased" section first (if it exists)
        if ($unreleasedKey) {
            $lines = $lines->merge(
                $this->renderVersion($unreleasedKey, $versions[$unreleasedKey], true)
            );
        }

        // Render all other releases
        $sortedReleases->each(function ($row) use (&$lines) {
            $lines = $lines->merge(
                $this->renderVersion($row['key'], $row['entry'], false)
            );
        });

        // Add version links
        $lines = $lines->merge($this->versionLinks);

        // Ensure final newline
        if ($lines->last() !== '') {
            $lines->push('');
        }

        return $lines->implode("\n");
    }

    /**
     * Render a single version block.
     *
     * @param string $key The object key ("1.1.1 - 2023-03-05" or "Unreleased")
     * @param array $entry The version entry
     * @param bool $isUnreleased Whether this is the unreleased version
     * @return array Lines of Markdown content
     */
    private function renderVersion(string $key, array $entry, bool $isUnreleased): array
    {
        $lines = collect();

        $heading = Str::of(data_get($entry, 'heading', $key))->trim();
        $date = Str::of(data_get($entry, 'date', ''))->trim();
        $url = Str::of(data_get($entry, 'url', ''))->trim();

        // Title line: "## [1.1.1] - 2023-03-05" or "## [Unreleased]"
        if ($isUnreleased) {
            $lines->push('## [Unreleased]');
            $this->versionLinks[] = "[Unreleased]: {$url}";
        } else {
            $label = $heading->isNotEmpty() ? $heading : $key;
            $titleLine = $date->isNotEmpty()
                ? "## [{$label}] - {$date}"
                : "## [{$label}]";

            $lines->push($titleLine);
            $this->versionLinks[] = "[{$label}]: {$url}";
        }

        $lines->push('');

        // Preferred section order (others will follow)
        $preferred = collect(['Added', 'Changed', 'Fixed', 'Removed', 'Deprecated', 'Security', 'Breaking']);
        $content = collect(data_get($entry, 'content', []));

        // First: render preferred sections in order
        $preferred->each(function ($section) use ($content, &$lines) {
            if (!$content->has($section)) {
                return;
            }

            $sectionLines = $this->renderSection($section, $content[$section]);
            $lines = $lines->merge($sectionLines);
        });

        // Then: render any other sections not in a preferred list
        $content
            ->keys()
            ->reject(fn ($section) => $preferred->contains($section))
            ->each(function ($section) use ($content, &$lines) {
                $sectionLines = $this->renderSection($section, $content[$section]);
                $lines = $lines->merge($sectionLines);
            });

        // Add a blank line after each version
        if ($lines->last() !== '') {
            $lines->push('');
        }

        return $lines->all();
    }

    /**
     * Render a section like "### Added" with bullet items.
     * Supports strings or nested arrays (will flatten to bullets).
     *
     * @param string $title The section title
     * @param array $items The items to include in the section
     * @return array Lines of Markdown content
     */
    private function renderSection(string $title, array $items): array
    {

        $lines = collect(['### ' . $title]);

        $this->flattenItems($items)->each(function ($item) use (&$lines) {

            $item = Str::of($item)->rtrim();

            if ($item->isEmpty()) {
                return;
            }

            // Preserve multi-line items (indent following lines)
            $parts = $item->replace("\r\n", "\n")->explode("\n");
            $first = $parts->shift();
            $lines->push("- {$first}");

            $parts->each(function ($part) use ($lines) {
                $lines->push("  {$part}");
            });
        });

        $lines->push('');
        return $lines->all();
    }

    /**
     * Flatten arbitrarily nested arrays of strings into a simple collection.
     *
     * @param array $items The nested array to flatten
     * @return Collection The flattened collection
     */
    private function flattenItems(array $items): Collection
    {
        return collect($items)->flatten();
    }

    /**
     * Generate and save a temporary changelog from the Markdown file.
     *
     * @throws \Exception If the changelog file doesn't exist
     * @throws CommonMarkException
     * @return void
     */
    private function generateTemporaryChangeLog(): void
    {
        $changeLogContent = $this->getChangeLogArrayFromParsedHtmlElements();
        $this->setTemporaryChangeLog($changeLogContent);
    }

    /**
     * Render and save the changelog from the temporary file.
     *
     * @throws \Exception If there's an error during rendering
     * @throws CommonMarkException
     * @return void
     */
    public function setChangeLog(): void
    {
        $temporaryChangeLog = $this->getTemporaryChangeLog();
        try {
            $markdown = $this->renderMarkdown($temporaryChangeLog);

        } catch (\Throwable $e) {
            throw new \Exception('Render error: ' . $e->getMessage());

        }
        File::put($this->changelogPath, $markdown);
    }

}
