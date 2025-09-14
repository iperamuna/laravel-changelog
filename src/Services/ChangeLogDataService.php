<?php

namespace Iperamuna\LaravelChangelog\Services;

use App\Rules\SemverRule;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Iperamuna\LaravelChangelog\Enums\ChangelogChangeTypes;
use Iperamuna\LaravelChangelog\Enums\SemanticVersionTypes;
use League\CommonMark\Exception\CommonMarkException;

class ChangeLogDataService
{

    protected ChangeLogService $changelogService;

    protected array $defaultChangeLogData;

    protected array $changeLogData;

    /**
     * @throws FileNotFoundException
     * @throws CommonMarkException
     */
    public function __construct(ChangeLogService $changelogService)
    {
        $this->changelogService = $changelogService;

        $this->defaultChangeLogData = [
            'heading' => [
                'heading' => 'Changelog',
                'content' => []
            ],
            'versions' => []
        ];

        $this->changeLogData = $this->changelogService->getTemporaryChangeLog();
    }

    public function setChangeLogData(array $data): void
    {
        $this->changeLogData = $data;
    }

    public function getLastReleaseVersion(): ?array
    {

        $versions = $this->changeLogData['versions'];
        $lastRelease = null;
        foreach ($versions as $release => $version) {
            if($version['heading'] === 'Unreleased') {
                continue;
            }
            $lastRelease = $version;
            break;
        }
        return $lastRelease;
    }

    public function addNewRelease(array $releaseContent): void
    {

        $release = [$releaseContent['release'] => $releaseContent];

        if(isset($this->changeLogData['versions']['Unreleased'])) {

            $newVersionsArray = [];
            $versions = $this->changeLogData['versions'];
            foreach ($versions as $key => $value) {
                $newVersionsArray[$key] = $value;

                if ($key === "Unreleased") {
                    $newVersionsArray = array_merge($newVersionsArray, $release);
                }
            }
            $this->changeLogData['versions'] = $newVersionsArray;
        }else{
            $versions = array_merge($release, $this->changeLogData['versions']);
            $this->changeLogData['versions'] = $versions;
        }

        $this->changelogService->setTemporaryChangeLog($this->changeLogData);
    }

    public function initChangeLogData(array $headerContent, array $unReleasedVersion): void
    {
        $changeLogData = $this->defaultChangeLogData;

        $changeLogData['heading']['content'] = $headerContent;

        if(!empty($unReleasedVersion)) {
            $changeLogData['versions']['Unreleased'] = $unReleasedVersion;
        }

        $this->changelogService->setTemporaryChangeLog($changeLogData);
    }

    public function getUnreleasedVersion(): array|bool
    {
        if(!isset($this->changeLogData['versions']['Unreleased'])) {
            return false;
        }
        return $this->changeLogData['versions']['Unreleased'];
    }

    public function setUnreleasedVersion(array $sections): void
    {
        $version = [
            'heading' => 'Unreleased',
            'url' => '',
            'date' => '',
            'content' => $sections
        ];

        $unreleased = ['Unreleased' => $version];
        $this->changeLogData['versions'] = array_merge($unreleased, $this->changeLogData['versions']);
        $this->changelogService->setTemporaryChangeLog($this->changeLogData);
    }

    public function formatReleaseContent(string $newReleaseVersion, string $releaseDate, string $releaseUrl, array $releaseSectionContent): array
    {
        return [
            'release' => "$newReleaseVersion - $releaseDate",
            'heading' => $newReleaseVersion,
            'date' => $releaseDate,
            'url' => $releaseUrl,
            'content' => $releaseSectionContent,
        ];
    }

    public function getChangeLogReleases()
    {
        $releases = [];
        $versions = $this->changeLogData['versions'];
        foreach ($versions as $release => $version) {
            $releases[$release] = $release;
        }

        return $releases;
    }

    public function getReleaseContent(string $releaseVersion):array|bool
    {
        if(!isset($this->changeLogData['versions'][$releaseVersion])) {
            return false;
        }
        return $this->changeLogData['versions'][$releaseVersion];
    }

    public function updateRelease($selectedRelease, $releaseContentFormatted): void
    {
        $this->changeLogData['versions'][$selectedRelease] = $releaseContentFormatted;
        $this->changelogService->setTemporaryChangeLog($this->changeLogData);
    }

    public function promoteUnreleased($releaseContentFormatted):void
    {

        if (isset($this->changeLogData['versions']['Unreleased'])) {
            unset($this->changeLogData['versions']['Unreleased']);
        }

        $release = [$releaseContentFormatted['release'] => $releaseContentFormatted];
        $this->changeLogData['versions'] = array_merge($release, $this->changeLogData['versions']);
        $this->changelogService->setTemporaryChangeLog($this->changeLogData);
    }



}
