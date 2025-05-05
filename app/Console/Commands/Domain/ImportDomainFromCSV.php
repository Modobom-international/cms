<?php

namespace App\Console\Commands\Domain;

use Illuminate\Console\Command;
use App\Repositories\DomainRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Carbon\Carbon;

class ImportDomainFromCSV extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-domain-from-csv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import domain from csv';

    protected $client;
    protected $apiKey;
    protected $apiSecret;
    protected $apiUrl;

    /**
     * Execute the console command.
     */
    public function handle(DomainRepository $domainRepository)
    {
        $count = 1;

        $listKey = [
            'vylinh3' => [
                'apiKey' => config('services.godaddy_vylinh3.api_key'),
                'apiSecret' => config('services.godaddy_vylinh3.api_secret'),
                'apiUrl' => config('services.godaddy_vylinh3.api_url')
            ],
            'vylinh4' => [
                'apiKey' => config('services.godaddy_vylinh4.api_key'),
                'apiSecret' => config('services.godaddy_vylinh4.api_secret'),
                'apiUrl' => config('services.godaddy_vylinh4.api_url')
            ]
        ];

        if (($handle = fopen(public_path('import-domain/domains.csv'), 'r')) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                if ($count <= 1200) {
                    $count++;
                    continue;
                }

                $domain = trim($row[0]);

                if (!preg_match('/^[^.\s]{1,63}(\.[^.\s]{1,63}){1,2}$/', $domain)) {
                    dump("Domain '$domain' không hợp lệ, bỏ qua");
                    continue;
                }

                $checkDomain = $domainRepository->getByDomain($domain);

                if ($checkDomain) {
                    dump("Domain '$domain' đã tồn tại trong hệ thống, bỏ qua");
                    continue;
                }

                $this->apiKey = $listKey['vylinh3']['apiKey'];
                $this->apiSecret = $listKey['vylinh3']['apiSecret'];
                $this->apiUrl = config('services.godaddy.api_url');
                $result = $this->getDetailDomain($domain);

                dump('---------- Bắt đầu với domain : ' . $domain);

                if (array_key_exists('code', $result) and $result['code'] == 'NOT_FOUND') {
                    $this->apiKey = $listKey['vylinh4']['apiKey'];
                    $this->apiSecret = $listKey['vylinh4']['apiSecret'];

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

                        dump($result);
                        dump('Skip domain');
                        continue;
                    } else {
                    }
                } else if (array_key_exists('error', $result)) {
                    $count++;

                    dump($result);
                    dump('Skip domain');
                    continue;
                } else {
                }

                if (array_key_exists('renewDeadline', $result)) {
                    $renewDeadline = Carbon::parse($result['renewDeadline'])->format('Y-m-d H:i:s');
                } else {
                    $renewDeadline = null;
                }

                $data = [
                    'domain' => $result['domain'],
                    'time_expired' => Carbon::parse($result['expires'])->format('Y-m-d H:i:s'),
                    'registrar' => 'Godaddy',
                    'is_locked' => false,
                    'renewable' => $result['renewable'] ?? false,
                    'status' => $result['status'] ?? 'active',
                    'name_servers' => json_encode($result['nameServers']) ?? null,
                    'renew_deadline' => $renewDeadline,
                    'registrar_created_at' => Carbon::parse($result['registrarCreatedAt'])->format('Y-m-d H:i:s') ?? null,
                ];

                $domainRepository->create($data);

                dump('Domain import ' . $domain .  ' thành công');

                $count++;
            }

            fclose($handle);
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
            } catch (RequestException $e) {
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

    public function handleException(RequestException $e)
    {
        if ($e->hasResponse()) {
            $response = json_decode($e->getResponse()->getBody()->getContents(), true);
            if (isset($response['code']) && $response['code'] === 'NOT_FOUND') {
                return ['code' => 'NOT_FOUND', 'message' => "Domain không tồn tại hoặc không thuộc quyền sở hữu."];
            } else {
                dd($e);
            }

            return $response;
        }

        return ['error' => 'Something went wrong'];
    }
}
