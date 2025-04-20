<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RecommendationHistory;
use App\Models\Customer;

class MigrateCustomerDataToRecommendations extends Command
{
    protected $signature = 'recommendations:migrate-customer-data';
    protected $description = 'Migrate customer data to recommendation histories';

    public function handle()
    {
        $histories = RecommendationHistory::all();
        $bar = $this->output->createProgressBar(count($histories));
        
        $bar->start();
        
        foreach ($histories as $history) {
            $customer = Customer::find($history->customer_id);
            
            if ($customer) {
                $history->update([
                    'customer_name' => $customer->name,
                    'customer_phone' => $customer->phone,
                    'customer_address' => $customer->address,
                ]);
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->info("\nCustomer data migration completed!");
    }
}
