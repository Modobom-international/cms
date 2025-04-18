<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\GoDaddyService;
use App\Services\CloudFlareService;
use App\Events\RefreshDomain;
use App\Repositories\DomainRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RefreshDomainInPlatform implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(GoDaddyService $goDaddyService, CloudFlareService $cloudFlareService, DomainRepository $domainRepository)
    {
        try {
            $result = $goDaddyService->getListDomain();
            $listDomain = array();

            foreach ($result['data'] as $domain) {
                $listDomain[] = $domain['domain'];
            }

            if (array_key_exists('error', $result)) {
                $this->messageEventHandler('Lỗi không lấy được danh sách domain từ Godaddy ...');

                return $this->error($result['error']);
            }

            $listExist = $domainRepository->getDomainByList($listDomain);
            $listNonExist = array_diff($listDomain, $listExist);

            if (count($listNonExist) > 0) {
                $this->messageEventHandler('Đã tìm thấy ' . count($listNonExist) . ' domain mới!');
                $this->messageEventHandler('Bắt đầu đồng bộ dữ liệu ...');
                foreach ($listNonExist as $index => $domain) {
                    $infoDomain = $result['data'][$index];
                    $nameServer = [
                        'ben.ns.cloudflare.com',
                        'jean.ns.cloudflare.com',
                    ];

                    $data = [
                        'domain' => $domain,
                        'time_expired' => Carbon::parse($infoDomain['expires'])->format('Y-m-d H:i:s'),
                        'registrar' => 'Godaddy',
                        'is_locked' => false,
                        'renewable' => $infoDomain['renewable'] ?? false,
                        'status' => $infoDomain['status'] ?? 'active',
                        'name_servers' => json_encode($nameServer) ?? null,
                        'renew_deadline' => Carbon::parse($infoDomain['renewDeadline'])->format('Y-m-d H:i:s') ?? null,
                        'registrar_created_at' => Carbon::parse($infoDomain['registrarCreatedAt'])->format('Y-m-d H:i:s') ?? null,
                    ];

                    $domainRepository->create($data);

                    $this->messageEventHandler('Thêm domain ' . $domain . ' vào hệ thống thành công ...');

                    $result = $cloudFlareService->addDomain(
                        $domain
                    );

                    if (array_key_exists('error', $result)) {
                        $this->messageEventHandler('Thêm domain ' . $domain . ' vào Cloudflare không thành công  ...');

                        return;
                    } else {
                        $this->messageEventHandler('Thêm domain ' . $domain . ' vào Cloudflare thành công ...');
                    }

                    $result = $goDaddyService->updateNameservers(
                        $domain
                    );

                    if (is_array($result) and array_key_exists('error', $result)) {
                        $this->messageEventHandler('Sửa DNS ' . $domain . ' trên Godaddy không thành công  ...');

                        return;
                    } else {
                        $this->messageEventHandler('Sửa DNS ' . $domain . ' trên Godaddy thành công  ...');
                    }
                }
            } else {
                $this->messageEventHandler('Không có domain mới.');
            }

            $this->messageEventHandler('Hoàn thành kiểm tra domain! Bro có thể tắt hộp thoại này đi được rồi nhé');
        } catch (\Exception $e) {
            $this->messageEventHandler('Xử lý đồng bộ domain lỗi. Vui lòng liên hệ bộ phận code!' . $e->getMessage());
            Log::error('Error in RefreshDomainInPlatform: ' . $e->getMessage());
        }
    }

    private function messageEventHandler($message)
    {
        event(new RefreshDomain($message));
    }
}
