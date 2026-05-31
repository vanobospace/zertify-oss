<?php

namespace App\Console\Commands;

use Database\Seeders\ZertifySeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('questions:sync-listening-seeds')]
#[Description('Sync the seeded listening questions and their audio metadata')]
class SyncListeningSeedsCommand extends Command
{
    public function handle(): int
    {
        app(ZertifySeeder::class)->syncListeningSeeds();

        $this->info('Listening seed questions synced.');

        return self::SUCCESS;
    }
}
