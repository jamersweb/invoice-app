<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AuditEvent;
use App\Models\AnalyticsEvent;
use App\Modules\Invoices\Models\Invoice;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class DataRetentionCommand extends Command
{
    protected $signature = 'data:retention {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Apply data retention policies and delete old data';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('Starting data retention cleanup...');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be deleted');
        }

        // Audit events retention (default: 7 years)
        $auditRetentionYears = Config::get('app.audit_retention_years', 7);
        $auditCutoff = Carbon::now()->subYears($auditRetentionYears);
        $auditCount = AuditEvent::where('created_at', '<', $auditCutoff)->count();
        
        if ($auditCount > 0) {
            $this->info("Found {$auditCount} audit events older than {$auditRetentionYears} years");
            if (!$dryRun) {
                AuditEvent::where('created_at', '<', $auditCutoff)->delete();
                $this->info("Deleted {$auditCount} audit events");
            }
        }

        // Analytics events retention (default: 1 year)
        $analyticsRetentionYears = Config::get('app.analytics_retention_years', 1);
        $analyticsCutoff = Carbon::now()->subYears($analyticsRetentionYears);
        $analyticsCount = AnalyticsEvent::where('created_at', '<', $analyticsCutoff)->count();
        
        if ($analyticsCount > 0) {
            $this->info("Found {$analyticsCount} analytics events older than {$analyticsRetentionYears} years");
            if (!$dryRun) {
                AnalyticsEvent::where('created_at', '<', $analyticsCutoff)->delete();
                $this->info("Deleted {$analyticsCount} analytics events");
            }
        }

        // Soft-deleted invoices retention (default: 2 years)
        $invoiceRetentionYears = Config::get('app.soft_delete_retention_years', 2);
        $invoiceCutoff = Carbon::now()->subYears($invoiceRetentionYears);
        $invoiceCount = Invoice::onlyTrashed()
            ->where('deleted_at', '<', $invoiceCutoff)
            ->count();
        
        if ($invoiceCount > 0) {
            $this->info("Found {$invoiceCount} soft-deleted invoices older than {$invoiceRetentionYears} years");
            if (!$dryRun) {
                Invoice::onlyTrashed()
                    ->where('deleted_at', '<', $invoiceCutoff)
                    ->forceDelete();
                $this->info("Permanently deleted {$invoiceCount} invoices");
            }
        }

        $this->info('Data retention cleanup completed!');
        
        return 0;
    }
}

