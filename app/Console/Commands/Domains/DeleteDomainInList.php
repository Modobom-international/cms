<?php

namespace App\Console\Commands\Domains;

use App\Enums\ListServer;
use Illuminate\Console\Command;
use App\Services\SSHService;

class DeleteDomainInList extends Command
{
    protected $client;
    protected $apiKey;
    protected $apiSecret;
    protected $apiUrl;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:delete-domain-in-list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete domain and dir root in list';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $listServer = ListServer::LIST_SERVER;
        $linesAfter = 11;
        $count = 1;
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

        foreach ($listServer as $server) {
            $dbInfoPath = public_path('db-info/DBinfo-' . $server . '.txt');

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

                    $this->apiKey = $listKey['tuan']['apiKey'];
                    $this->apiSecret = $listKey['tuan']['apiSecret'];
                    $this->apiUrl = $listKey['tuan']['apiUrl'];
                    $domain = $row[2];
                    $result = $this->getDetailDomain($domain);

                    dump('---------- Bắt đầu với domain : ' . $domain);

                    if (array_key_exists('code', $result) and $result['code'] == 'NOT_FOUND') {
                        $this->apiKey = $listKey['linh']['apiKey'];
                        $this->apiSecret = $listKey['linh']['apiSecret'];
                        $this->apiUrl = $listKey['linh']['apiUrl'];

                        dump('Domain này không phải của Tuấn.');
                        dump('Tiếp tục kiểm tra với Linh');

                        $result = $this->getDetailDomain($domain);

                        if (array_key_exists('error', $result)) {
                            $count++;

                            dump('Gọi lên api liên tiếp không thành công.');
                            dump('Skip domain');
                            continue;
                        }

                        if (array_key_exists('code', $result) and $result['code'] != 'NOT_FOUND') {
                            $count++;

                            dump('Domain có tồn tại ở tài khoản Linh.');
                            dump('Skip domain');
                            continue;
                        }
                    } else {
                        $count++;

                        dump('Domain có tồn tại ở tài khoản Tuấn.');
                        dump('Skip domain');
                        continue;
                    }

                    if (file_exists($dbInfoPath)) {
                        $lines = file($dbInfoPath, FILE_IGNORE_NEW_LINES);
                        $found = false;
                        $output = [];

                        foreach ($lines as $index => $line) {
                            if (strpos($line, $domain) !== false) {
                                $found = true;
                                $output = array_slice($lines, $index, $linesAfter);
                                break;
                            }
                        }

                        if ($found) {
                            dump("Tìm thấy thông tin domain!");
                        } else {
                            $count++;
                            dump("Không tìm thấy về thông tin domain!");
                            dump('Skip domain');
                            continue;
                        }
                    } else {
                        $count++;
                        dump("File thông tin server không tồn tại!");
                        dump('Skip domain');
                        continue;
                    }

                    $explodeDBUser = explode(':', $output[2]);
                    $explodeDBName = explode(':', $output[1]);
                    $explodeUserFTP = explode(':', $output[4]);
                    $dbUser = trim($explodeDBUser[1]);
                    $dbName = trim($explodeDBName[1]);
                    $userFTP = trim($explodeUserFTP[1]);

                    $data = [
                        'domain' => $domain,
                        'user_ftp' => $userFTP,
                        'db_name' => $dbName,
                        'db_user' => $dbUser,
                        'server' => $server
                    ];

                    $sshService = new SSHService($data['server']);
                    $result = $sshService->runDeleteSiteScript($data);

                    if (is_array($result) and array_key_exists('error', $result)) {
                        $count++;

                        dump('Lỗi xóa domain : ' . json_encode($result));
                        dump('Skip domain');
                        continue;
                    }

                    $result = $cloudFlareService->deleteDomain(
                        $domain
                    );

                    if (is_array($result) and array_key_exists('error', $result)) {
                        $count++;

                        dump('Lỗi xóa domain : ' . json_encode($result));
                        dump('Skip domain');
                        continue;
                    }

                    dump('Domain đã xóa thành công!');
                }
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
