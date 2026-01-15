<?php

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Class UserRepository
 * 
 * Xử lý tất cả logic truy xuất dữ liệu User
 * Tuân thủ Single Responsibility Principle (S in SOLID)
 */
class UserRepository implements UserRepositoryInterface
{
    /**
     * @var User
     */
    protected $model;

    /**
     * Constructor - Dependency Injection
     * Tuân thủ Dependency Inversion Principle (D in SOLID)
     *
     * @param User $model
     */
    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaginatedUsers(array $filters = [], int $perPage = 5): LengthAwarePaginator
    {
        $query = $this->model->with(['role', 'status', 'gender']);

        // Filter theo role
        if (!empty($filters['role']) && $filters['role'] !== 'all') {
            $query->whereHas('role', function ($q) use ($filters) {
                $q->where('role_code', strtoupper($filters['role']));
            });
        }

        // Filter theo status
        if (!empty($filters['status'])) {
            $statusId = $filters['status'] === 'active' ? 1 : 2;
            $query->where('status_id', $statusId);
        }

        // Search theo keyword (tên, email, code_user)
        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('full_name', 'LIKE', "%{$keyword}%")
                  ->orWhere('email', 'LIKE', "%{$keyword}%")
                  ->orWhere('code_user', 'LIKE', "%{$keyword}%");
            });
        }

        // Sắp xếp theo ngày tạo mới nhất
        $query->orderBy('created_at', 'DESC');

        return $query->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $userId): ?User
    {
        return $this->model->with(['role', 'status', 'gender'])
                           ->find($userId);
    }

    /**
     * {@inheritDoc}
     */
    public function countByRole(): array
    {
        $total = $this->model->count();
        
        $counts = $this->model->select('role_id', DB::raw('count(*) as count'))
                              ->groupBy('role_id')
                              ->pluck('count', 'role_id')
                              ->toArray();

        return [
            'total' => $total,
            'admin' => $counts[1] ?? 0,      // role_id = 1
            'lecturer' => $counts[2] ?? 0,   // role_id = 2
            'student' => $counts[3] ?? 0,    // role_id = 3
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): User
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $userId, array $data): bool
    {
        $user = $this->findById($userId);
        
        if (!$user) {
            return false;
        }

        return $user->update($data);
    }

    /**
     * {@inheritDoc}
     */
    public function toggleStatus(int $userId, int $statusId): bool
    {
        $user = $this->findById($userId);
        
        if (!$user) {
            return false;
        }

        // Không cho phép khóa admin
        if ($user->isAdmin()) {
            return false;
        }

        return $user->update(['status_id' => $statusId]);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $userId): bool
    {
        $user = $this->findById($userId);
        
        if (!$user) {
            return false;
        }

        // Không cho phép xóa admin
        if ($user->isAdmin()) {
            return false;
        }

        return $user->delete(); // Soft delete
    }

    /**
     * {@inheritDoc}
     */
    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        $query = $this->model->where('email', $email);

        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function usernameExists(string $username, ?int $excludeUserId = null): bool
    {
        $query = $this->model->where('username', $username);

        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        return $query->exists();
    }
}