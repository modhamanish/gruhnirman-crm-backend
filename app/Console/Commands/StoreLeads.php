<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Category;
use App\Models\PropertyType;
use Illuminate\Support\Facades\Storage;

class StoreLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:store-leads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store leads from public/2000_leads.csv';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = public_path('2k_to_3k_leads.csv');

        if (!file_exists($filePath)) {
            $this->error('CSV file not found!');
            return;
        }

        $file = fopen($filePath, 'r');
        $header = fgetcsv($file); // Skip header

        $successDetails = [];
        $failedDetails = [];

        while (($row = fgetcsv($file)) !== false) {
            try {
                // Skip completely empty rows
                if (!array_filter($row)) {
                    continue;
                }

                // Column 2: Name and Contact Number
                $nameStr = strip_tags($row[1]);
                $parts = explode('/', $nameStr);
                $name = trim($parts[0]);
                $contact_number = isset($parts[1]) ? trim(str_replace(['+91-', '+91', '-', ' '], '', $parts[1])) : null;

                // Column 3: Property Name
                $property_name = trim($row[2]);

                // Column 4: Leads Source
                $sourceStr = strtolower(trim($row[3]));
                if (in_array($sourceStr, ['n/a', '', 'call'])) {
                    $sourceName = 'Phone Call';
                } else {
                    $sourceName = trim($row[3]);
                }
                $leadSource = LeadSource::firstOrCreate(['name' => $sourceName]);
                $lead_source_id = $leadSource->id;

                // Column 5: Category
                $catStr = trim(strip_tags($row[4]));
                if (empty($catStr)) {
                    $catStr = 'Unknown';
                }

                $category = Category::firstOrCreate(['name' => $catStr]);
                $category_id = $category->id;

                // Column 7: Remark
                $remark = trim($row[6]);

                // Column 8: Property Type & Inquiry For
                $propTypeStr = trim($row[7]);
                $propTypeParts = explode('<br>', $propTypeStr);
                $propTypeName = trim($propTypeParts[0]);

                if (empty($propTypeName)) {
                    $propTypeName = trim(strip_tags(str_replace('<br>', ' ', $propTypeStr)));
                }
                if (empty($propTypeName)) {
                    $propTypeName = 'Unknown';
                }

                $propertyType = PropertyType::firstOrCreate([
                    'name' => $propTypeName,
                    'category_id' => $category_id
                ]);
                $property_type_id = $propertyType->id;

                $inquiry_for = trim(strip_tags(str_replace('<br>', ' ', $propTypeStr)));

                // Column 9: Budget
                $budgetStr = trim($row[8]);
                $budgets = explode('-', $budgetStr);
                $min = isset($budgets[0]) && trim($budgets[0]) !== '' ? (float)trim($budgets[0]) : null;
                $max = isset($budgets[1]) && trim($budgets[1]) !== '' ? (float)trim($budgets[1]) : null;

                $min_budget = null;
                if ($min !== null) {
                    $min_budget = $min <= 10 ? $min * 10000000 : $min * 100000;
                }

                $max_budget = null;
                if ($max !== null) {
                    $max_budget = $max <= 10 ? $max * 10000000 : $max * 100000;
                }

                // Column 10: Interested Area
                $interested_area = trim(strip_tags(str_replace('<br>', ' ', $row[9])));

                // Column 11 & 12: Timestamps
                $createdStr = explode('<br>', trim($row[10]))[0];
                $created_at = $createdStr ? date('Y-m-d H:i:s', strtotime($createdStr)) : now();

                $updatedStr = explode('<br>', trim($row[11]))[0];
                $updated_at = $updatedStr ? date('Y-m-d H:i:s', strtotime($updatedStr)) : now();

                // Column 13: Append to remark
                if (isset($row[12])) {
                    $col13 = trim($row[12]);
                    if (!empty($col13)) {
                        $remark .= "\n" . $col13;
                    }
                }

                $lead = new Lead();
                $lead->name = $name;
                $lead->contact_number = $contact_number;
                $lead->property_name = $property_name;
                $lead->lead_source_id = $lead_source_id;
                $lead->category_id = $category_id;
                $lead->remark = $remark;
                $lead->property_type_id = $property_type_id;
                $lead->inquiry_for = $inquiry_for;
                $lead->min_budget = $min_budget;
                $lead->max_budget = $max_budget;
                $lead->interested_area = $interested_area;

                $status = \Illuminate\Support\Facades\DB::table('lead_statuses')->where('is_initial', 1)->first() ?? \Illuminate\Support\Facades\DB::table('lead_statuses')->first();
                $lead->lead_status_id = $status->id ?? 1;
                $lead->type = 'lead';
                $lead->created_by = 1; // Default fallback
                $lead->assigned_to = 1; // Default fallback

                // We want to override timestamps:
                $lead->timestamps = false;
                $lead->created_at = $created_at;
                $lead->updated_at = $updated_at;

                // Temporary workaround for trait error on save without auth
                $lead->withoutEvents(function () use ($lead) {
                    $lead->save();
                });

                $successDetails[] = $lead->toArray();
            } catch (\Exception $e) {
                $failedDetails[] = [
                    'row_data' => $row,
                    'error' => $e->getMessage(),
                    'line' => $e->getLine()
                ];
            }
        }

        fclose($file);

        file_put_contents(storage_path('app/success_leads1.json'), json_encode($successDetails, JSON_PRETTY_PRINT));
        file_put_contents(storage_path('app/failed_leads1.json'), json_encode($failedDetails, JSON_PRETTY_PRINT));

        $this->info('Leads storing process completed.');
        $this->info('Successfully stored: ' . count($successDetails));
        $this->info('Failed rows: ' . count($failedDetails));
    }
}
