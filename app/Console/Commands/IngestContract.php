<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\IngestContractJob;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\DocumentChunk;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
class IngestContract extends Command
{
    protected $signature = 'docupulse:ingest {--tenant_id=1}';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenant_id = (int) $this->option('tenant_id');
        IngestContractJob::dispatch($tenant_id);
        $this->info('Ingestion queued.');
    }
}
