<?php

namespace App\Console\Commands;

use App\Services\InvoiceWorkflowService;
use Illuminate\Console\Command;

class ProcessSubscriptionRenewals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:process-renewals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate renewal invoices for subscriptions expiring soon and process expired subscriptions';

    protected InvoiceWorkflowService $invoiceWorkflowService;

    public function __construct(InvoiceWorkflowService $invoiceWorkflowService)
    {
        parent::__construct();
        $this->invoiceWorkflowService = $invoiceWorkflowService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing subscription renewals...');

        // Generate renewal invoices for expiring subscriptions
        $this->info('Generating renewal invoices for expiring subscriptions...');
        $renewalCount = $this->invoiceWorkflowService->generateRenewalInvoicesForExpiringSubscriptions();
        $this->info("Generated {$renewalCount} renewal invoices.");

        // Process expired subscriptions
        $this->info('Processing expired subscriptions...');
        $expiredCount = $this->invoiceWorkflowService->processExpiredSubscriptions();
        $this->info("Processed {$expiredCount} expired subscriptions.");

        $this->info('Subscription renewal processing completed!');
        
        return 0;
    }
}
