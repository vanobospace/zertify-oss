<?php

namespace App\Console\Commands;

use App\Services\ExampleBankService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('examples:refresh-index')]
#[Description('Refresh the example bank runtime index from the repository corpus')]
class RefreshExampleBankCommand extends Command
{
    public function __construct(protected ExampleBankService $exampleBankService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->exampleBankService->refreshFromCatalog();

        $this->info('Example bank index refreshed.');
        $this->line('Sources: '.$result['sources']);
        $this->line('Examples: '.$result['examples']);

        return self::SUCCESS;
    }
}
