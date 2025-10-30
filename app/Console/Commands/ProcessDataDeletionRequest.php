<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProcessDataDeletionRequest extends Command
{
    protected $signature = 'data:delete-request {email : Supplier email address}';
    protected $description = 'Process a GDPR/PDPL data deletion request';

    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("Processing data deletion request for: {$email}");
        
        if (!$this->confirm('Are you sure you want to delete all data for this supplier?')) {
            $this->warn('Deletion cancelled');
            return 0;
        }

        DB::beginTransaction();
        
        try {
            $supplier = Supplier::where('contact_email', $email)->first();
            
            if (!$supplier) {
                $this->error("Supplier not found with email: {$email}");
                return 1;
            }

            $user = User::where('email', $email)->first();

            // Anonymize supplier data
            $supplier->update([
                'company_name' => '[DELETED]',
                'legal_name' => '[DELETED]',
                'tax_registration_number' => null,
                'contact_email' => 'deleted_' . $supplier->id . '@deleted.local',
                'contact_phone' => null,
                'address' => null,
                'website' => null,
                'kyc_data' => null,
            ]);

            // Anonymize user if exists
            if ($user) {
                $user->update([
                    'name' => '[DELETED]',
                    'email' => 'deleted_' . $user->id . '@deleted.local',
                ]);
            }

            // Note: Keep invoices and transactions for compliance
            // But remove any PII from them if stored

            $this->info("Data anonymized successfully for supplier ID: {$supplier->id}");
            
            DB::commit();
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}

