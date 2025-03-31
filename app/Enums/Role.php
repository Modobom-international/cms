<?php

namespace App\Enums;

final class Role
{
    const LIST_VN = [
        'general_manager' => 'Quản lý chính',
        'manager' => 'Quản lý',
        'senior_expert' => 'Chuyên viên cấp cao',
        'expert' => 'Chuyên viên',
        'officer' => 'Nhân viên',
        'probationer' => 'Thử việc'
    ];

    const LIST_EN = [
        'general_manager' => 'General Manager',
        'manager' => 'Manager',
        'senior_expert' => 'Senior Expert',
        'expert' => 'Expert',
        'officer' => 'Officer',
        'probationer' => 'Probationer'
    ];
}
