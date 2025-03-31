<?php

namespace App\Console\Commands\Domains;

use App\Enums\ListServer;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helper\Common;

class ImportDomainFromCSV extends Command
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
    protected $signature = 'domains:import-domain-from-csv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import domain from CSV file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $listIP = ListServer::LIST_SERVER;
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

        foreach ($listIP as $server) {
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

                    if ($server == '139.162.44.151') {
                        $domain = $row[2];
                        $admin_password = $row[3];
                        $db_name = $row[6];
                        $ftp_user = $row[5];
                    } else if ($server == '139.177.186.184') {
                        $domain = $row[2];
                        $admin_password = $row[4];
                        $db_name = $row[6];
                        $ftp_user = $row[5];
                    } else {
                        $domain = $row[2];
                        $admin_password = $row[3];
                        $db_name = $row[6];
                        $ftp_user = $row[5];
                    }

                    $this->apiKey = $listKey['tuan']['apiKey'];
                    $this->apiSecret = $listKey['tuan']['apiSecret'];
                    $this->apiUrl = $listKey['tuan']['apiUrl'];
                    $linesAfter = 10;
                    $public_html = `/home/` . $domain . "/public_html";
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
                            $getUser = DB::table('users')->where('email', 'tranlinh.modobom@gmail.com')->first();
                            $provider = $getUser->id;
                        }
                    } else if (array_key_exists('error', $result)) {
                        $count++;

                        dump('Gọi lên api liên tiếp không thành công');
                        dump('Skip domain');
                        continue;
                    } else {
                        $getUser = DB::table('users')->where('email', 'vutuan.modobom@gmail.com')->first();
                        $provider = $getUser->id;
                    }

                    if (file_exists($dbInfoPath)) {
                        $lines = file($dbInfoPath, FILE_IGNORE_NEW_LINES);
                        $found = false;
                        $output = [];

                        foreach ($lines as $index => $line) {
                            if (strpos($line, $domain) !== false) {
                                $found = true;
                                $output = array_slice($lines, $index, $linesAfter + 1);
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
                    $explodeDBPassword = explode(':', $output[3]);
                    $dbUser = trim($explodeDBUser[1]);
                    $dbPassword = trim($explodeDBPassword[1]);

                    $data = [
                        'domain' => $domain,
                        'admin_username' => 'admin',
                        'admin_password' => $admin_password,
                        'db_name' => $db_name,
                        'db_user' => $dbUser,
                        'db_password' => $dbPassword,
                        'public_html' => $public_html,
                        'ftp_user' => $ftp_user,
                        'server' => $server,
                        'status' => 1,
                        'provider' => $provider,
                        'created_at' => Common::covertDateTimeToMongoBSONDateGMT7(Common::getCurrentVNTime())
                    ];

                    DB::connection('mongodb')
                        ->table('domains')
                        ->insert($data);

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
