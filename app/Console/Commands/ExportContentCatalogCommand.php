<?php

namespace App\Console\Commands;

use App\Services\ContentCatalogService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('content:export-catalog')]
#[Description('Export the canonical content catalog from the local database')]
class ExportContentCatalogCommand extends Command
{
    public function __construct(protected ContentCatalogService $contentCatalogService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $catalog = $this->contentCatalogService->exportCatalog();

        $this->info('Content catalog exported.');
        $this->line('Questions: '.count($catalog['questions'] ?? []));
        $this->line('Themes: '.count($catalog['question_generation_themes'] ?? []));

        return self::SUCCESS;
    }
}
