<?php

namespace App\Console\Commands;

use App\Models\Property;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class StoreProperty extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:store-property';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $file = file_get_contents('public/data/property.json');
            $property = json_decode($file, true);
            if (is_array($property) && count($property) > 0) {
                foreach ($property as $key => $value) {
                    Property::create([
                        'builder_id' => 1,
                        'name' => $value['property_name'],
                        'starting_price' => 0,
                        'ending_price' => 0,
                        'address' => $value['address'],
                        'additional_note' => $value['description'],
                        'image_url' => $value['property_image'],
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::info('propert store Error', $e->getMessage());
        }
    }
}
