<?php

namespace App\Console\Commands;

use App\Services\ContentCatalogService;
use App\Services\ExampleBankService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('content:refresh-from-catalog')]
#[Description('Refresh the managed content subset from the canonical repository catalog')]
class RefreshContentCatalogCommand extends Command
{
    public function __construct(
        protected ContentCatalogService $contentCatalogService,
        protected ExampleBankService $exampleBankService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->contentCatalogService->refreshFromCatalog();
        $exampleBankResult = $this->exampleBankService->refreshFromCatalog();

        $this->info('Content catalog refreshed.');
        $this->line('Exams: '.$result['exams']);
        $this->line('Modules: '.$result['modules']);
        $this->line('Questions: '.$result['questions']);
        $this->line('Themes: '.$result['question_generation_themes']);
        $this->line('Example sources: '.$exampleBankResult['sources']);
        $this->line('Example bank entries: '.$exampleBankResult['examples']);

        return self::SUCCESS;
    }
}
