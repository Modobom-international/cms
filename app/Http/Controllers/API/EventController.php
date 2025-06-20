<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Traits\LogsActivity;
use App\Enums\ActivityAction;
use App\Http\Controllers\Controller;
use App\Jobs\StoreEvents;
use App\Repositories\EventRepository;

class EventController extends Controller
{
    use LogsActivity;

    protected $eventRepository;

    public function __construct(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    public function list(Request $request)
    {
        try {
            $input = $request->all();
            $data = $this->eventRepository->get();

            $this->logActivity(ActivityAction::ACCESS_VIEW, ['filters' => $input], 'Xem danh sách sự kiện');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lấy danh sách sự kiện thành công',
                'type' => 'list_event_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách sự kiện không thành công',
                'type' => 'list_event_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            StoreEvents::dispatch($data)->onQueue('store_events');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lưu sự kiện thành công!',
                'type' => 'store_event_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lưu sự kiện không thành công!',
                'type' => 'store_event_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
