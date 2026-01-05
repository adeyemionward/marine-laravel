<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EquipmentListing;
use App\Models\UserProfile;
use App\Models\SellerProfile;
use Illuminate\Support\Facades\DB;

class PopulateSellerProfileId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marine:populate-seller-profile-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate seller_profile_id in equipment_listings table for backward compatibility';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to populate seller_profile_id in equipment_listings...');

        // Get listings with null seller_profile_id
        $listings = EquipmentListing::whereNull('seller_profile_id')->get();
        
        $count = $listings->count();
        $this->info("Found {$count} listings to update.");

        if ($count === 0) {
            $this->info('No listings to update.');
            return;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $updated = 0;
        $failed = 0;

        foreach ($listings as $listing) {
            try {
                // listing->seller_id is actually the user_profile_id
                $userProfile = UserProfile::find($listing->seller_id);
                
                if (!$userProfile) {
                    // Try treating seller_id as user_id directly (just in case of legacy data mixup)
                    $sellerProfile = SellerProfile::where('user_id', $listing->seller_id)->first();
                } else {
                    $sellerProfile = SellerProfile::where('user_id', $userProfile->user_id)->first();
                }

                if ($sellerProfile) {
                    $listing->seller_profile_id = $sellerProfile->id;
                    $listing->saveQuietly();
                    $updated++;
                } else {
                    // Create SellerProfile if missing
                    $this->info("Creating missing seller profile for UserProfile ID: {$listing->seller_id}");
                    
                    try {
                        // Determine User ID
                        if ($userProfile) {
                            $userId = $userProfile->user_id;
                            $businessName = $userProfile->company_name ?: ($userProfile->full_name ?: 'Seller ' . $userId);
                        } else {
                            // Fallback if UserProfile is missing (should not happen for valid listings)
                            // Assuming seller_id might be user_id in some legacy breakage
                            $userId = $listing->seller_id;
                            $businessName = 'Seller ' . $userId;
                        }

                        // Check if user exists before creating profile
                        if (\App\Models\User::find($userId)) {
                            $newProfile = SellerProfile::create([
                                'user_id' => $userId,
                                'business_name' => $businessName,
                                'business_description' => 'Auto-generated seller profile',
                                'verification_status' => 'approved', // Auto-approve legacy sellers?
                                'is_featured' => false,
                                'rating' => 0,
                                'review_count' => 0,
                            ]);
                            
                            $listing->seller_profile_id = $newProfile->id;
                            $listing->saveQuietly();
                            $updated++;
                            $this->info("Created SellerProfile ID: {$newProfile->id}");
                        } else {
                            $this->error("User ID {$userId} not found. Cannot create profile.");
                            $failed++;
                        }
                    } catch (\Exception $e) {
                        $this->error("Failed to create profile: " . $e->getMessage());
                        $failed++;
                    }
                }
            } catch (\Exception $e) {
                $failed++;
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Completed. Updated: {$updated}, Failed/Skipped: {$failed}");
        
        // Run the raw SQL query as a fallback/cleanup for any edge cases where models might have failed
        // but relationships are technically valid in DB
        $this->info('Running final SQL cleanup pass...');
        try {
            DB::statement("
                UPDATE equipment_listings el
                INNER JOIN user_profiles up ON el.seller_id = up.id
                INNER JOIN seller_profiles sp ON up.user_id = sp.user_id
                SET el.seller_profile_id = sp.id
                WHERE el.seller_profile_id IS NULL
            ");
            $this->info('SQL cleanup pass complete.');
        } catch (\Exception $e) {
            $this->error('SQL cleanup pass failed: ' . $e->getMessage());
        }
    }
}
