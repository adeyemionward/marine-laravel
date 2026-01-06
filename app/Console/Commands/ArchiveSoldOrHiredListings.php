<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EquipmentListing;
use App\Enums\ListingStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ArchiveSoldOrHiredListings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:archive-sold-or-hired-listings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archives equipment listings that have been sold or hired for a certain number of days.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to archive old sold or hired listings...');
        Log::info('ArchiveSoldOrHiredListings command started.');

        $days = config('app.archive_listings_after_days', 7);
        $archiveDate = Carbon::now()->subDays($days);

        $listingsToArchive = EquipmentListing::whereIn('status', [ListingStatus::SOLD, ListingStatus::HIRED])
            ->where('updated_at', '<=', $archiveDate)
            ->get();

        if ($listingsToArchive->isEmpty()) {
            $this->info('No listings to archive.');
            Log::info('No listings to archive.');
            return;
        }

        $count = $listingsToArchive->count();
        $this->info("Found {$count} listings to archive.");

        foreach ($listingsToArchive as $listing) {
            $listing->update(['status' => ListingStatus::ARCHIVED]);
            Log::info("Archived listing with ID: {$listing->id}");
        }

        $this->info("Successfully archived {$count} listings.");
        Log::info("Successfully archived {$count} listings.");
    }
}
