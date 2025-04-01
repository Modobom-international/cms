<?php

namespace App\Http\Controllers;

use App\Repositories\Salary\SalaryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalaryController extends Controller
{
    protected $salaryRepository;

    public function __construct(SalaryRepository $salaryRepository)
    {
        $this->salaryRepository = $salaryRepository;
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $salaryValue = $request->input('salary');
        $securityKey = $user->security_key;

        $encryptionKey = hash('sha256', $securityKey);
        $iv = substr(hash('sha256', $securityKey), 0, 16);

        $encryptedSalary = openssl_encrypt(
            $salaryValue,
            'AES-256-CBC',
            substr($encryptionKey, 0, 32),
            0,
            $iv
        );

        $salary = $this->salaryRepository->getSalaryByUserID($user_id);
        $salary->user_id = $user->id;
        $salary->encrypted_salary = base64_encode($encryptedSalary);
        $salary->save();

        return redirect()->back()->with('success', 'Lương đã được lưu thành công.');
    }

    public function showSalary()
    {
        $user = Auth::user();
        $salary = $this->salaryRepository->getSalaryByUserID($user_id);

        if (!$salary) {
            return redirect()->back()->with('error', 'Không tìm thấy thông tin lương.');
        }

        $credentials = request()->only('email', 'password');
        if (auth()->attempt($credentials)) {
            $passwordPlain = $credentials['password'];
            $securityKey = hash('sha256', $passwordPlain);
            $encryptionKey = hash('sha256', $user->security_key);
            $iv = substr(hash('sha256', $securityKey), 0, 16);

            try {
                $decryptedSalary = openssl_decrypt(
                    base64_decode($salary->encrypted_salary),
                    'AES-256-CBC',
                    substr($encryptionKey, 0, 32),
                    0,
                    $iv
                );
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Không thể truy cập lương do lỗi bảo mật.');
            }

            return view('salary.show', ['salary' => $decryptedSalary]);
        }

        return redirect()->back()->with('error', 'Đăng nhập không thành công.');
    }
}
