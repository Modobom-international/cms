<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\GoDaddyService;
use App\Events\RefreshDomain;
use App\Repositories\DomainRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RefreshDomainInPlatform implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(GoDaddyService $goDaddyService, DomainRepository $domainRepository)
    {
        try {
            $result = $goDaddyService->getListDomain();
            $listDomain = array();

            foreach ($result['data'] as $domain) {
                $listDomain[] = $domain['domain'];
            }

            if (array_key_exists('error', $result)) {
                return $this->error($result['error']);
            }

            $listExist = $domainRepository->getDomainByList($listDomain);
            $listNonExist = array_diff($listDomain, $listExist);

            if (count($listNonExist) > 0) {
                $this->messageEventHandler('Đã tìm thấy ' . count($listNonExist) . ' domain mới!');
                $this->messageEventHandler('Bắt đầu đồng bộ dữ liệu ...');
                foreach ($listNonExist as $index => $domain) {
                    $infoDomain = $result['data'][$index];

                    $data = [
                        'domain' => $domain,
                        'time_expired' => Carbon::parse($infoDomain['expires'])->format('Y-m-d H:i:s'),
                        'registrar' => 'Godaddy',
                        'is_locked' => false,
                        'renewable' => $infoDomain['renewable'] ?? false,
                        'status' => $infoDomain['status'] ?? 'active',
                        'name_servers' => json_encode($infoDomain['nameServers']) ?? null,
                        'renew_deadline' => Carbon::parse($infoDomain['renewDeadline'])->format('Y-m-d H:i:s') ?? null,
                        'registrar_created_at' => Carbon::parse($infoDomain['registrarCreatedAt'])->format('Y-m-d H:i:s') ?? null,
                    ];

                    $domainRepository->create($data);

                    $this->messageEventHandler('- Thêm domain ' . $domain . ' vào hệ thống thành công ...');
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
