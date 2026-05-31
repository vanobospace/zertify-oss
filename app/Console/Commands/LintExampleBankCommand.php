<?php

namespace App\Console\Commands;

use App\Services\ExampleBankService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('examples:lint')]
#[Description('Validate the repository example bank corpus')]
class LintExampleBankCommand extends Command
{
    public function __construct(protected ExampleBankService $exampleBankService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->exampleBankService->lintCatalog();

        if (! $result['passed']) {
            foreach ($result['errors'] as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->info('Example bank catalog passed validation.');
        $this->line('Sources: '.$result['sources']);
        $this->line('Examples: '.$result['examples']);

        return self::SUCCESS;
    }
}
