<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Card;
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
    
    public function dashboard()
    {
        // 1, Tổng số board
        $boardCount = $this->boardRepository->countTotal();
    
        // 2, Tổng số task
        $taskCount = $this->cardRepository->countTotal();
    
        // 3, Task hoàn thành trong tuần
        $completedThisWeek = $this->cardRepository->countTotalThisWeek();
    
        // 4, Task quá hạn
        $overdueCount = $this->cardRepository->overdueCount();
    
        // 5, Pie Chart - Trạng thái Task
        $statusStats = $this->cardRepository->statusStats();
    
        // 6, Bar Chart - Tiến độ các board
        $boards = $this->boardRepository->boardChart();
        // 7, Top thành viên hoàn thành task nhiều nhất
        $topUsers = $this->userRepository->topUser();
    
        // 8, Danh sách task quá hạn chi tiết
        $overdueTasks = $this->cardRepository->overdueTasks();
    
        $progressPerBoard = $this->boardRepository->progressPerBoard();
    
        // Kết quả trả về
        return response()->json([
            'board_count' => $boardCount,
            'task_count' => $taskCount,
            'completed_this_week' => $completedThisWeek,
            'overdue_task_count' => $overdueCount,
            'status_pie' => $statusStats,
            'board_chart' => $boards,
            'top_users' => $topUsers,
            'overdue_tasks' => $overdueTasks,
            'board_progress' => $progressPerBoard,
        ]);
    }

}
