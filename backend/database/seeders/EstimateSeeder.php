<?php

namespace Database\Seeders;

use App\Models\Estimate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EstimateSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        Estimate::factory(10)->create();
        
        Estimate::factory(3)->draft()->create();
        
        Estimate::factory(2)->sent()->create();
        
        Estimate::factory(1)->accepted()->create();
        
        Estimate::factory(2)->inactive()->create();
    }
}
