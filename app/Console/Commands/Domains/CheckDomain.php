<?php

namespace App\Console\Commands\Domains;

use Illuminate\Console\Command;
use App\Jobs\NotificationCheckDomain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Log;

class CheckDomain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:check-domain';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check domain what is up or down';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $listDomain = DB::connection('mongodb')->table('domains')->where('status', 0)->get();

        if (count($listDomain) == 0) {
            return;
        }

        foreach ($listDomain as $record) {
            $response = Http::timeout(10)->get($record->domain);

            if ($response->failed()) {
                continue;
            }

            $html = $response->body();

            if (str_contains($html, '/lander')) {
                continue;
            }

            DB::connection('mongodb')->table('domains')->where('_id', $record->id)->update(['status' => 1]);

            $dataDispatch = [
                'domain' => $record->domain,
                'provider' => $record->provider
            ];

            NotificationCheckDomain::dispatch($dataDispatch)->onQueue('notification_system');

            dump('--- Domain: ' . $record->domain . ' is up ---');
        }
    }
}
