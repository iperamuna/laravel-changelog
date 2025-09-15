<?php

namespace Iperamuna\LaravelChangelog\Console\Commands;

use Iperamuna\LaravelChangelog\Enums\ChangelogChangeTypes;
use Iperamuna\LaravelChangelog\Rules\SemverRule;
use Iperamuna\LaravelChangelog\Enums\SemanticVersionTypes;
use Iperamuna\LaravelChangelog\Services\ChangeLogDataService;
use Iperamuna\LaravelChangelog\Services\ChangeLogService;
use Iperamuna\LaravelChangelog\Services\SemverService;
use Illuminate\Console\Command;
use League\CommonMark\Exception\CommonMarkException;
use function Laravel\Prompts\text;
use function Laravel\Prompts\info;

class ChangelogPromoteUnRelease extends Command
{

    protected ChangeLogDataService $changeLogDataService;
    protected ChangeLogService $changelogService;

    protected SemverService $semverService;

    public function  __construct(ChangeLogService $changelogService,
                                 ChangeLogDataService $changeLogDataService,
                                 SemverService $semverService)
    {
        $this->changelogService = $changelogService;
        $this->changeLogDataService = $changeLogDataService;
        $this->semverService = $semverService;
        parent::__construct();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'changelog:promote-unreleased';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Modify Existing Release to the changelog';

    /**
     * Execute the console command.
     * @throws \Exception
     * @throws CommonMarkException
     */
    public function handle()
    {

        $selectedRelease = 'Unreleased';

        $selectedReleaseContent = $this->changeLogDataService->getReleaseContent($selectedRelease);

        $releaseVersion = text(
            label:  'What is the version of the release?',
            validate: function (string $value) {
                return preg_match(SemverRule::REGEX, $value)
                    ? null
                    : 'Please enter a valid SemVer: 1.2.3';
            });

        $releaseDate = text('What is the date of the release? (YYYY-MM-DD)', default: $selectedReleaseContent['date'] );

        $releaseUrl = text('What is the url of the release? (https://example.com/release/1.2.3)', default: $selectedReleaseContent['url']);

        $releaseSections = ChangelogChangeTypes::cases();

        $releaseSectionContent = [];
        foreach ($releaseSections as $releaseSection) {

            info('Add Content to '.$releaseSection->value . ' section, line by line. Empty line to finish the section.');
            $lineCount = 1;

            if(isset($selectedReleaseContent['content'][$releaseSection->value])) {
                $contents = $selectedReleaseContent['content'][$releaseSection->value];

            }
            $iteration = 0;
            while (true){
                $content = text('Line '. $lineCount++, default: $contents[$iteration++] ?? '');
                if($content === '') {
                    break;
                }else{
                    $releaseSectionContent[$releaseSection->value][] = $content;
                }
            }

        }

        $releaseContentFormatted = $this->changeLogDataService->formatReleaseContent($releaseVersion, $releaseDate, $releaseUrl, $releaseSectionContent);
        $this->changeLogDataService->promoteUnreleased($releaseContentFormatted);
        $this->changelogService->setChangeLog();
        info('Release Updated successfully');
        return self::SUCCESS;

    }



}
