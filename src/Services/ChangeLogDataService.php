<?php

namespace Iperamuna\LaravelChangelog\Services;

use League\CommonMark\Exception\CommonMarkException;

class ChangeLogDataService
{

    protected ChangeLogService $changelogService;

    public function __construct(ChangeLogService $changelogService)
    {
        $this->changelogService = $changelogService;
    }

    protected array $changeLogData;

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

    /**
     * @throws CommonMarkException
     */
    public function addNewChangeLogData(array $version): void
    {
        $newVersionsArray = [];

        $versions = $this->changeLogData['versions'];
        foreach ($versions as $key => $value) {
            $newVersionsArray[$key] = $value;

            if ($key === "Unreleased") {
                $newVersionsArray = array_merge($newVersionsArray, $version);
            }
        }
        $this->changeLogData['versions'] = $newVersionsArray;

        $this->changelogService->setTemporaryChangeLog($this->changeLogData);
    }


}
