<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\User;

/**
 * Interface UserRepositoryInterface
 * 
 * Định nghĩa các phương thức truy xuất dữ liệu User
 * Tuân thủ Interface Segregation Principle (I in SOLID)
 */
interface UserRepositoryInterface
{
    /**
     * Lấy danh sách users với phân trang và filter
     *
     * @param array $filters ['role' => string, 'status' => string, 'keyword' => string]
     * @param int $perPage Số record trên 1 trang
     * @return LengthAwarePaginator
     */
    public function getPaginatedUsers(array $filters = [], int $perPage = 5): LengthAwarePaginator;

    /**
     * Lấy thông tin chi tiết user theo ID
     *
     * @param int $userId
     * @return User|null
     */
    public function findById(int $userId): ?User;

    /**
     * Đếm số lượng users theo role
     *
     * @return array ['total' => int, 'admin' => int, 'lecturer' => int, 'student' => int]
     */
    public function countByRole(): array;

    /**
     * Tạo user mới
     *
     * @param array $data
     * @return User
     */
    public function create(array $data): User;

    /**
     * Cập nhật thông tin user
     *
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public function update(int $userId, array $data): bool;

    /**
     * Khóa/Mở khóa user (soft lock)
     *
     * @param int $userId
     * @param int $statusId 1=Active, 2=Inactive
     * @return bool
     */
    public function toggleStatus(int $userId, int $statusId): bool;

    /**
     * Xóa user (soft delete)
     *
     * @param int $userId
     * @return bool
     */
    public function delete(int $userId): bool;

    /**
     * Kiểm tra email đã tồn tại chưa
     *
     * @param string $email
     * @param int|null $excludeUserId
     * @return bool
     */
    public function emailExists(string $email, ?int $excludeUserId = null): bool;

    /**
     * Kiểm tra username đã tồn tại chưa
     *
     * @param string $username
     * @param int|null $excludeUserId
     * @return bool
     */
    public function usernameExists(string $username, ?int $excludeUserId = null): bool;
}