<?php

namespace App\Services;

use App\Contracts\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Class UserService
 * 
 * Xử lý business logic liên quan đến User
 * Tuân thủ Single Responsibility Principle (S in SOLID)
 */
class UserService
{
    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * Constructor - Dependency Injection
     *
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Lấy danh sách users với phân trang
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUsers(array $filters = [], int $perPage = 5): LengthAwarePaginator
    {
        return $this->userRepository->getPaginatedUsers($filters, $perPage);
    }

    /**
     * Lấy chi tiết user
     *
     * @param int $userId
     * @return User|null
     */
    public function getUserDetail(int $userId): ?User
    {
        return $this->userRepository->findById($userId);
    }

    /**
     * Lấy số liệu thống kê users
     *
     * @return array
     */
    public function getUserStatistics(): array
    {
        return $this->userRepository->countByRole();
    }

    /**
     * Tạo user mới
     *
     * @param array $data
     * @return array ['success' => bool, 'message' => string, 'user' => User|null]
     */
    public function createUser(array $data): array
    {
        // Validate email
        if ($this->userRepository->emailExists($data['email'])) {
            return [
                'success' => false,
                'message' => 'Email đã tồn tại trong hệ thống',
                'user' => null
            ];
        }

        // Tạo username từ email nếu chưa có
        if (empty($data['username'])) {
            $data['username'] = $this->generateUsernameFromEmail($data['email']);
        }

        // Validate username
        if ($this->userRepository->usernameExists($data['username'])) {
            return [
                'success' => false,
                'message' => 'Username đã tồn tại trong hệ thống',
                'user' => null
            ];
        }

        // Tạo code_user tự động
        $data['code_user'] = $this->generateUserCode($data['role_id']);

        // Hash password
        if (!empty($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
            unset($data['password']);
        }

        // Set default status = Active
        if (empty($data['status_id'])) {
            $data['status_id'] = 1;
        }

        try {
            $user = $this->userRepository->create($data);

            return [
                'success' => true,
                'message' => 'Thêm người dùng thành công',
                'user' => $user
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
                'user' => null
            ];
        }
    }

    /**
     * Cập nhật thông tin user
     *
     * @param int $userId
     * @param array $data
     * @return array
     */
    public function updateUser(int $userId, array $data): array
    {
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy người dùng'
            ];
        }

        // Validate email (nếu thay đổi)
        if (!empty($data['email']) && $data['email'] !== $user->email) {
            if ($this->userRepository->emailExists($data['email'], $userId)) {
                return [
                    'success' => false,
                    'message' => 'Email đã tồn tại trong hệ thống'
                ];
            }
        }

        // Validate username (nếu thay đổi)
        if (!empty($data['username']) && $data['username'] !== $user->username) {
            if ($this->userRepository->usernameExists($data['username'], $userId)) {
                return [
                    'success' => false,
                    'message' => 'Username đã tồn tại trong hệ thống'
                ];
            }
        }

        // Hash password nếu có thay đổi
        if (!empty($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
            unset($data['password']);
        }

        try {
            $this->userRepository->update($userId, $data);

            return [
                'success' => true,
                'message' => 'Cập nhật thông tin thành công'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Khóa/Mở khóa user
     *
     * @param int $userId
     * @return array
     */
    public function toggleUserStatus(int $userId): array
    {
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy người dùng'
            ];
        }

        // Không cho phép khóa admin
        if ($user->isAdmin()) {
            return [
                'success' => false,
                'message' => 'Không thể khóa tài khoản Admin'
            ];
        }

        // Toggle status: 1 (Active) <-> 2 (Inactive)
        $newStatusId = $user->status_id == 1 ? 2 : 1;

        try {
            $this->userRepository->toggleStatus($userId, $newStatusId);

            $statusText = $newStatusId == 1 ? 'mở khóa' : 'khóa';

            return [
                'success' => true,
                'message' => "Đã {$statusText} người dùng thành công",
                'new_status' => $newStatusId
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Xóa user (soft delete)
     *
     * @param int $userId
     * @return array
     */
    public function deleteUser(int $userId): array
    {
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy người dùng'
            ];
        }

        // Không cho phép xóa admin
        if ($user->isAdmin()) {
            return [
                'success' => false,
                'message' => 'Không thể xóa tài khoản Admin'
            ];
        }

        try {
            // Append timestamp to unique fields to avoid conflict when soft deleted
            $timestamp = time();
            $user->update([
                'email' => $user->email . '_deleted_' . $timestamp,
                'username' => $user->username ? $user->username . '_deleted_' . $timestamp : null,
                'code_user' => $user->code_user . '_deleted_' . $timestamp,
            ]);

            // Now soft delete
            $this->userRepository->delete($userId);

            return [
                'success' => true,
                'message' => 'Đã xóa người dùng thành công'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate username từ email
     *
     * @param string $email
     * @return string
     */
    protected function generateUsernameFromEmail(string $email): string
    {
        $username = Str::before($email, '@');

        // Nếu username đã tồn tại, thêm số random
        if ($this->userRepository->usernameExists($username)) {
            $username .= rand(100, 999);
        }

        return $username;
    }

    /**
     * Generate mã user tự động
     *
     * @param int $roleId
     * @return string
     */
    protected function generateUserCode(int $roleId): string
    {
        $prefix = match ($roleId) {
            1 => 'ADMIN',
            2 => 'LEC',
            3 => 'STU',
            default => 'USER'
        };

        // Find the highest existing code number for this role (exclude soft deleted)
        $maxCode = User::where('code_user', 'like', "{$prefix}%")
            ->whereNull('deleted_at')
            ->orderBy('code_user', 'desc')
            ->value('code_user');

        if ($maxCode) {
            // Extract number from code (e.g., "LEC007" -> 7)
            $maxNumber = (int) substr($maxCode, strlen($prefix));
            $number = str_pad(($maxNumber + 1), 3, '0', STR_PAD_LEFT);
        } else {
            // No existing codes, start from 001
            $number = '001';
        }

        return "{$prefix}{$number}";
    }

    /**
     * Import users hàng loạt từ array data
     *
     * @param array $usersData Mảng chứa thông tin users
     * @return array ['success' => bool, 'message' => string, 'results' => array]
     */
    public function bulkCreateUsers(array $usersData): array
    {
        $results = [
            'total' => count($usersData),
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($usersData as $index => $userData) {
            try {
                // Validate dữ liệu cơ bản
                if (empty($userData['email']) || empty($userData['full_name']) || empty($userData['role_id'])) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'row' => $index + 1,
                        'data' => $userData,
                        'error' => 'Thiếu thông tin bắt buộc (email, họ tên, vai trò)'
                    ];
                    continue;
                }

                // Kiểm tra email đã tồn tại
                if ($this->userRepository->emailExists($userData['email'])) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'row' => $index + 1,
                        'data' => $userData,
                        'error' => 'Email đã tồn tại: ' . $userData['email']
                    ];
                    continue;
                }

                // Tạo username từ email nếu chưa có
                if (empty($userData['username'])) {
                    $userData['username'] = $this->generateUsernameFromEmail($userData['email']);
                }

                // Kiểm tra username đã tồn tại
                if ($this->userRepository->usernameExists($userData['username'])) {
                    $userData['username'] .= rand(100, 999);
                }

                // Tạo code_user tự động
                $userData['code_user'] = $this->generateUserCode($userData['role_id']);

                // Hash password (mặc định nếu không có)
                if (empty($userData['password'])) {
                    $userData['password'] = 'password123'; // Mật khẩu mặc định
                }
                $userData['password_hash'] = Hash::make($userData['password']);
                unset($userData['password']);

                // Set default status
                if (empty($userData['status_id'])) {
                    $userData['status_id'] = 1;
                }

                // Tạo user
                $this->userRepository->create($userData);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'row' => $index + 1,
                    'data' => $userData,
                    'error' => $e->getMessage()
                ];
            }
        }

        $message = "Thêm thành công {$results['success']}/{$results['total']} người dùng";
        if ($results['failed'] > 0) {
            $message .= ". Thất bại: {$results['failed']}";
        }

        return [
            'success' => true,
            'message' => $message,
            'results' => $results
        ];
    }

    /**
     * Parse CSV/Excel data thành array
     *
     * @param string $content Nội dung file
     * @param string $type 'csv' hoặc 'paste'
     * @return array
     */
    public function parseImportData(string $content, string $type = 'csv'): array
    {
        $users = [];
        $lines = explode("\n", trim($content));

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Bỏ qua dòng header (dòng đầu tiên chứa tiêu đề cột)
            if ($index === 0 && (stripos($line, 'họ và tên') !== false || stripos($line, 'email') !== false || stripos($line, 'full_name') !== false)) {
                continue;
            }

            // Tự động phát hiện delimiter (chấm phẩy hoặc dấu phẩy)
            $delimiter = strpos($line, ';') !== false ? ';' : ',';
            
            // Parse CSV với delimiter phù hợp
            $fields = str_getcsv($line, $delimiter);

            if (count($fields) < 3) {
                continue; // Bỏ qua dòng không đủ dữ liệu
            }

            // Validate email format
            $email = trim($fields[1]);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue; // Bỏ qua nếu email không hợp lệ
            }

            // Format: Họ tên, Email, Role (ADMIN/LECTURER/STUDENT), [SĐT], [Địa chỉ]
            $roleMap = [
                'ADMIN' => 1,
                'LECTURER' => 2,
                'STUDENT' => 3,
                'admin' => 1,
                'lecturer' => 2,
                'student' => 3,
                'Admin' => 1,
                'Lecturer' => 2,
                'Student' => 3,
            ];

            $roleInput = trim($fields[2]);
            $roleId = $roleMap[$roleInput] ?? $roleMap[strtoupper($roleInput)] ?? 3; // Case-insensitive fallback

            $users[] = [
                'full_name' => trim($fields[0]),
                'email' => $email,
                'role_id' => $roleId,
                'phone' => isset($fields[3]) && !empty(trim($fields[3])) ? trim($fields[3]) : null,
                'address' => isset($fields[4]) && !empty(trim($fields[4])) ? trim($fields[4]) : null,
                'password' => isset($fields[5]) && !empty(trim($fields[5])) ? trim($fields[5]) : null, // Cột thứ 6: mật khẩu tùy chỉnh
            ];
        }

        return $users;
    }
}
