<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\SellerApplication;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Payment;
use App\Notifications\SubscriptionActivatedNotification;
use App\Notifications\SubscriptionExpiredNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class InvoiceWorkflowService
{
    /**
     * Generate invoice after seller application approval
     */
    public function generateSellerApprovalInvoice(SellerApplication $application, User $approvedBy): Invoice
    {
        try {
            DB::beginTransaction();

            // Get default subscription plan for new sellers
            $defaultPlan = SubscriptionPlan::where('tier', 'basic')
                ->where('is_active', true)
                ->first();

            if (!$defaultPlan) {
                throw new \Exception('No default subscription plan found for new sellers');
            }

            // Calculate amounts
            $baseAmount = $defaultPlan->price;
            $taxRate = 7.5; // VAT in Nigeria
            $taxAmount = ($baseAmount * $taxRate) / 100;
            $totalAmount = $baseAmount + $taxAmount;

            // Generate invoice
            $invoice = Invoice::create([
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'user_id' => $application->user_id,
                'seller_application_id' => $application->id,
                'plan_id' => $defaultPlan->id,
                'amount' => $baseAmount,
                'tax_amount' => $taxAmount,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'invoice_type' => 'seller_subscription',
                'tax_rate' => $taxRate,
                'due_date' => now()->addDays(14), // 14 days to pay
                'items' => [
                    [
                        'name' => $defaultPlan->name . ' Subscription',
                        'description' => 'Monthly seller subscription - ' . $defaultPlan->description,
                        'quantity' => 1,
                        'unit_price' => $baseAmount,
                        'total' => $baseAmount
                    ]
                ],
                'notes' => 'Welcome to Marine.ng! Please complete your payment to activate your seller account.',
                'terms_and_conditions' => $this->getSellerTermsAndConditions(),
                'company_name' => $application->business_name,
                'generated_by' => $approvedBy->id,
            ]);

            // Log the invoice generation
            Log::info('Seller approval invoice generated', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'user_id' => $application->user_id,
                'application_id' => $application->id,
                'amount' => $totalAmount,
            ]);

            DB::commit();
            
            return $invoice;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to generate seller approval invoice', [
                'application_id' => $application->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process invoice payment and activate subscription
     */
    public function processInvoicePayment(Invoice $invoice, Payment $payment): Subscription
    {
        try {
            DB::beginTransaction();

            // Mark invoice as paid
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_reference' => $payment->payment_reference,
                'payment_method' => $payment->gateway,
            ]);

            // Create or update subscription
            $subscription = $this->createOrUpdateSubscription($invoice);

            // If this was for seller application, activate seller profile
            if ($invoice->seller_application_id) {
                $this->activateSellerProfile($invoice->sellerApplication);
            }

            // Send welcome email/notifications
            $this->sendSubscriptionActivatedNotification($subscription);

            Log::info('Invoice payment processed and subscription activated', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'subscription_id' => $subscription->id,
                'user_id' => $invoice->user_id,
            ]);

            DB::commit();

            return $subscription;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process invoice payment', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate renewal invoice before subscription expires
     */
    public function generateRenewalInvoice(Subscription $subscription): Invoice
    {
        try {
            DB::beginTransaction();

            $plan = $subscription->plan;
            $user = $subscription->user;

            // Check if there's already a pending renewal invoice
            $existingInvoice = Invoice::where('user_id', $user->id)
                ->where('invoice_type', 'subscription_renewal')
                ->where('status', 'pending')
                ->where('plan_id', $plan->id)
                ->first();

            if ($existingInvoice) {
                return $existingInvoice;
            }

            // Calculate amounts
            $baseAmount = $plan->price;
            $taxRate = 7.5; // VAT in Nigeria
            $taxAmount = ($baseAmount * $taxRate) / 100;
            $totalAmount = $baseAmount + $taxAmount;

            // Generate renewal invoice
            $invoice = Invoice::create([
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'amount' => $baseAmount,
                'tax_amount' => $taxAmount,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'invoice_type' => 'subscription_renewal',
                'tax_rate' => $taxRate,
                'due_date' => $subscription->expires_at, // Due on expiration date
                'items' => [
                    [
                        'name' => $plan->name . ' Renewal',
                        'description' => 'Monthly subscription renewal - ' . $plan->description,
                        'quantity' => 1,
                        'unit_price' => $baseAmount,
                        'total' => $baseAmount
                    ]
                ],
                'notes' => 'Your subscription is due for renewal. Please complete payment to continue your seller privileges.',
                'terms_and_conditions' => $this->getSubscriptionTermsAndConditions(),
                'company_name' => $user->sellerProfile->business_name ?? 'Marine Equipment Business',
                'generated_by' => null, // Auto-generated
            ]);

            Log::info('Renewal invoice generated', [
                'invoice_id' => $invoice->id,
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'due_date' => $subscription->expires_at,
            ]);

            DB::commit();

            return $invoice;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to generate renewal invoice', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle subscription expiration
     */
    public function handleSubscriptionExpiration(Subscription $subscription): void
    {
        try {
            DB::beginTransaction();

            // Mark subscription as expired
            $subscription->markAsExpired();

            // Deactivate seller privileges
            if ($subscription->user->role === 'seller') {
                $this->deactivateSellerPrivileges($subscription->user);
            }

            // Hide all active listings
            $this->hideSellerListings($subscription->user);

            // Send expiration notification
            $this->sendSubscriptionExpiredNotification($subscription);

            Log::info('Subscription expired and seller privileges deactivated', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to handle subscription expiration', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create or update subscription after payment
     */
    private function createOrUpdateSubscription(Invoice $invoice): Subscription
    {
        $user = $invoice->user;
        $plan = $invoice->subscriptionPlan;

        // Check for existing active subscription
        $existingSubscription = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if ($existingSubscription) {
            // Extend existing subscription
            $newExpiryDate = $existingSubscription->expires_at->addDays(30); // Add 30 days
            $existingSubscription->update([
                'expires_at' => $newExpiryDate,
                'auto_renew' => true,
            ]);
            return $existingSubscription;
        }

        // Create new subscription
        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(30), // 30 days from now
            'auto_renew' => true,
        ]);
    }

    /**
     * Activate seller profile after first payment
     */
    private function activateSellerProfile(SellerApplication $application): void
    {
        $user = $application->user;
        
        // Ensure seller profile exists and is active
        if ($user->sellerProfile) {
            $user->sellerProfile->update([
                'verification_status' => 'active',
                'verified_at' => now(),
            ]);
        }

        // Update user status
        $user->update(['status' => 'active']);
    }

    /**
     * Deactivate seller privileges
     */
    private function deactivateSellerPrivileges(User $user): void
    {
        if ($user->sellerProfile) {
            $user->sellerProfile->update([
                'verification_status' => 'expired',
            ]);
        }

        // Don't change user role, just deactivate privileges
        $user->update(['status' => 'inactive']);
    }

    /**
     * Hide all seller listings when subscription expires
     */
    private function hideSellerListings(User $user): void
    {
        $user->equipmentListings()
            ->where('status', 'active')
            ->update([
                'status' => 'expired',
                'expired_at' => now(),
            ]);
    }

    /**
     * Get seller terms and conditions
     */
    private function getSellerTermsAndConditions(): string
    {
        return "By paying this invoice, you agree to Marine.ng's seller terms and conditions:\n\n" .
               "1. Monthly subscription fee is required to maintain active seller status\n" .
               "2. Late payments may result in temporary suspension of seller privileges\n" .
               "3. All listings must comply with Marine.ng quality standards\n" .
               "4. Commission fees apply to completed transactions\n" .
               "5. Subscription auto-renews unless cancelled\n\n" .
               "For full terms, visit: https://marine.ng/seller-terms";
    }

    /**
     * Get subscription terms and conditions
     */
    private function getSubscriptionTermsAndConditions(): string
    {
        return "Subscription Renewal Terms:\n\n" .
               "1. This invoice is for your monthly subscription renewal\n" .
               "2. Payment must be completed before the due date to avoid service interruption\n" .
               "3. Subscription will auto-renew monthly unless cancelled\n" .
               "4. All active listings will be hidden if payment is not received\n" .
               "5. Late fees may apply for payments received after the due date\n\n" .
               "Manage your subscription at: https://marine.ng/user-dashboard";
    }

    /**
     * Send subscription activated notification
     */
    private function sendSubscriptionActivatedNotification(Subscription $subscription): void
    {
        try {
            $user = $subscription->user;
            $plan = $subscription->plan;

            // Send email notification
            $user->notify(new SubscriptionActivatedNotification($subscription, $plan));

            Log::info('Subscription activated notification sent', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'email' => $user->email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send subscription activated notification', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send subscription expired notification
     */
    private function sendSubscriptionExpiredNotification(Subscription $subscription): void
    {
        try {
            $user = $subscription->user;
            $plan = $subscription->plan;

            // Send email notification
            $user->notify(new SubscriptionExpiredNotification($subscription, $plan));

            Log::info('Subscription expired notification sent', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'email' => $user->email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send subscription expired notification', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check for subscriptions that need renewal invoices
     */
    public function generateRenewalInvoicesForExpiringSubscriptions(): int
    {
        $count = 0;
        
        // Get subscriptions expiring in 7 days
        $expiringSubscriptions = Subscription::active()
            ->where('expires_at', '<=', now()->addDays(7))
            ->where('expires_at', '>', now())
            ->with(['user', 'plan'])
            ->get();

        foreach ($expiringSubscriptions as $subscription) {
            try {
                $this->generateRenewalInvoice($subscription);
                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to generate renewal invoice for subscription', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $count;
    }

    /**
     * Process expired subscriptions
     */
    public function processExpiredSubscriptions(): int
    {
        $count = 0;
        
        $expiredSubscriptions = Subscription::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->with(['user'])
            ->get();

        foreach ($expiredSubscriptions as $subscription) {
            try {
                $this->handleSubscriptionExpiration($subscription);
                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to process expired subscription', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $count;
    }
}