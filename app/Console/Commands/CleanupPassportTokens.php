<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupPassportTokens extends Command
{
    protected $signature = 'passport:cleanup-tokens';
    protected $description = 'Delete revoked and expired Passport tokens';

    public function handle()
    {
        // Delete revoked OR expired access tokens
        $deletedAccess = DB::table('oauth_access_tokens')
            ->where(function ($query) {
                $query->where('revoked', 1)
                      ->orWhere('expires_at', '<', now());
            })
            ->delete();

        // Delete revoked OR expired refresh tokens
        $deletedRefresh = DB::table('oauth_refresh_tokens')
            ->where(function ($query) {
                $query->where('revoked', 1)
                      ->orWhere('expires_at', '<', now());
            })
            ->delete();

        $this->info("Deleted {$deletedAccess} access tokens.");
        $this->info("Deleted {$deletedRefresh} refresh tokens.");

        return 0;
    }
}
