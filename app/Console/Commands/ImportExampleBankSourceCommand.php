<?php

namespace App\Console\Commands;

use App\Services\ExampleBankService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('examples:import-source {manifest : Path to a JSON manifest with sources/examples to merge into the repo corpus}')]
#[Description('Merge an example bank source manifest into the repository corpus')]
class ImportExampleBankSourceCommand extends Command
{
    public function __construct(protected ExampleBankService $exampleBankService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->exampleBankService->mergeManifestIntoCatalog((string) $this->argument('manifest'));

        $this->info('Example bank source manifest merged.');
        $this->line('Merged sources: '.$result['sources']);
        $this->line('Merged examples: '.$result['examples']);

        return self::SUCCESS;
    }
}
