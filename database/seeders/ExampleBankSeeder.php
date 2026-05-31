<?php

namespace Database\Seeders;

use App\Services\ExampleBankService;
use Illuminate\Database\Seeder;

class ExampleBankSeeder extends Seeder
{
    public function run(): void
    {
        app(ExampleBankService::class)->refreshFromCatalog();
    }
}
