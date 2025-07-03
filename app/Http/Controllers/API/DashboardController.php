<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\CardRepository;
use App\Repositories\BoardRepository;
use App\Repositories\BoardUserRepository;
use App\Repositories\UserRepository;
use App\Repositories\WorkspaceRepository;
use App\Repositories\WorkspaceUserRepository;
use App\Enums\Utility;

class DashboardController extends Controller
{
    protected $boardRepository;
    protected $cardRepository;
    protected $boardUserRepository;
    protected $workspaceUserRepository;
    protected $workspaceRepository;
    protected $utility;
    protected $userRepository;

    public function __construct(
        Utility $utility,
        CardRepository $cardRepository,
        BoardRepository $boardRepository,
        BoardUserRepository $boardUserRepository,
        WorkspaceUserRepository $workspaceUserRepository,
        WorkspaceRepository $workspaceRepository,
        UserRepository $userRepository
    ) {
        $this->utility = $utility;
        $this->cardRepository = $cardRepository;
        $this->workspaceUserRepository = $workspaceUserRepository;
        $this->workspaceRepository = $workspaceRepository;
        $this->boardUserRepository = $boardUserRepository;
        $this->boardRepository = $boardRepository;
        $this->userRepository = $userRepository;
    }

    public function metrics()
    {
        try {
            $boardCount = $this->boardRepository->countTotal();
            $taskCount = $this->cardRepository->countTotal();
            $completedThisWeek = $this->cardRepository->countTotalThisWeek();
            $overdueCount = $this->cardRepository->overdueCount();
            $statusStats = $this->cardRepository->statusStats();
            $boards = $this->boardRepository->boardChart();
            $topUsers = $this->userRepository->topUser();
            $overdueTasks = $this->cardRepository->overdueTasks();
            $progressPerBoard = $this->boardRepository->progressPerBoard();

            $data = [
                'board_count' => $boardCount,
                'task_count' => $taskCount,
                'completed_this_week' => $completedThisWeek,
                'overdue_task_count' => $overdueCount,
                'status_pie' => $statusStats,
                'board_chart' => $boards,
                'top_users' => $topUsers,
                'overdue_tasks' => $overdueTasks,
                'board_progress' => $progressPerBoard,
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lấy dữ liệu thống kê board thành công',
                'type' => 'dashboard_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy dữ liệu thống kê board không thành công',
                'type' => 'dashboard_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
