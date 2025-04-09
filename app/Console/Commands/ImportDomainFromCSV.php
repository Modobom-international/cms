<?php

namespace App\Console\Commands;

use App\Enums\Utility;
use App\Repositories\DomainRepository;
use Illuminate\Console\Command;
use GuzzleHttp\Client;

class ImportDomainFromCSV extends Command
{
    protected $client;
    protected $apiKey;
    protected $apiSecret;
    protected $apiUrl;
    protected $utility;

    protected $signature = 'domain:import-from-csv';
    protected $description = 'Import domain from CSV file';

    public function __construct(Utility $utility, DomainRepository $domainRepository)
    {
        $this->utility = $utility;
        $this->domainRepository = $domainRepository;
        parent::__construct();
    }

    public function handle()
    {
        $count = 1;
        $listIP = explode(',', env('LIST_IP', '127.0.0.1,localhost'));

        $listKey = [
            'tuan' => [
                'apiKey' => config('services.godaddy_tuan.api_key'),
                'apiSecret' => config('services.godaddy_tuan.api_secret'),
                'apiUrl' => config('services.godaddy_tuan.api_url')
            ],
            'linh' => [
                'apiKey' => config('services.godaddy_tuan.api_key'),
                'apiSecret' => config('services.godaddy_tuan.api_secret'),
                'apiUrl' => config('services.godaddy_tuan.api_url')
            ]
        ];

        foreach ($listIP as $server) {
            if (($handle = fopen(public_path('import-domain/' . $server . '.csv'), 'r')) !== false) {
                while (($row = fgetcsv($handle)) !== false) {
                    if ($count <= 2) {
                        $count++;
                        continue;
                    }

                    if (empty($row[2]) or empty($row[3]) or empty($row[5]) or empty($row[6]) or empty($row[7])) {
                        $count++;
                        continue;
                    }

                    $domain = $row[2];

                    $this->apiKey = $listKey['tuan']['apiKey'];
                    $this->apiSecret = $listKey['tuan']['apiSecret'];
                    $this->apiUrl = $listKey['tuan']['apiUrl'];
                    $result = $this->getDetailDomain($domain);

                    dump('---------- Bắt đầu với domain : ' . $domain);

                    if (array_key_exists('code', $result) and $result['code'] == 'NOT_FOUND') {
                        $this->apiKey = $listKey['linh']['apiKey'];
                        $this->apiSecret = $listKey['linh']['apiSecret'];
                        $this->apiUrl = $listKey['linh']['apiUrl'];

                        dump('Domain này không phải của Tuấn');
                        dump('Tiếp tục kiểm tra với Linh');

                        $result = $this->getDetailDomain($domain);

                        if (array_key_exists('code', $result) and $result['code'] == 'NOT_FOUND') {
                            $count++;

                            dump('Domain này không phải của Linh');
                            dump('Skip domain');
                            continue;
                        } else if (array_key_exists('error', $result)) {
                            $count++;

                            dump('Gọi lên api liên tiếp không thành công');
                            dump('Skip domain');
                            continue;
                        } else {
                        }
                    } else if (array_key_exists('error', $result)) {
                        $count++;

                        dump('Gọi lên api liên tiếp không thành công');
                        dump('Skip domain');
                        continue;
                    } else {
                    }

                    dd($result);

                    $data = [
                        'domain' => $domain,
                        'time_expired' => 'admin',
                        'registrar' => 'Godaddy',
                        'is_locked' => false,
                    ];

                    $this->domainRepository->create($data);

                    dump('Domain import thành công');

                    $count++;
                }

                fclose($handle);
            }
        }
    }

    public function getDetailDomain($domain)
    {
        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Authorization' => 'sso-key ' . $this->apiKey . ':' . $this->apiSecret,
                'Accept' => 'application/json',
            ],
        ]);

        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                if ($attempt > 0) {
                    dump('Thử lại lần thứ ' . $attempt);
                }

                $response = $this->client->get("/v1/domains/{$domain}");

                return json_decode($response->getBody(), true);
            } catch (Exception $e) {
                $error = $this->handleException($e);

                if (isset($error['code']) && $error['code'] === 'TOO_MANY_REQUESTS') {
                    $retryAfter = $error['retryAfterSec'] ?? 30;
                    dump("Quá nhiều request, thử lại sau {$retryAfter} giây...");
                    sleep($retryAfter);
                    $attempt++;
                } else {
                    return $error;
                }
            }
        }

        return ['error' => 'SKIP_DOMAIN'];
    }

    public function handleException(Exception $e)
    {
        if ($e->hasResponse()) {
            $response = json_decode($e->getResponse()->getBody()->getContents(), true);
            if (isset($response['code']) && $response['code'] === 'NOT_FOUND') {
                return ['code' => 'NOT_FOUND', 'message' => "Domain không tồn tại hoặc không thuộc quyền sở hữu."];
            }
            return $response;
        }

        return ['error' => 'Something went wrong'];
    }
}
