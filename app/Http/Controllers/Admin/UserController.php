<?php
// tạo user mới
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;

/**
 * Class UserController
 * 
 * Controller xử lý quản lý người dùng
 * Tuân thủ Single Responsibility Principle (S in SOLID)
 */
class UserController extends Controller
{
    /**
     * @var UserService
     */
    protected $userService;

    /**
     * Constructor - Dependency Injection
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Hiển thị trang quản lý users
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.adminUsers');
    }

    /**
     * API: Lấy danh sách users (JSON)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUsers(Request $request): JsonResponse
    {
        $filters = [
            'role' => $request->get('role', 'all'),
            'status' => $request->get('status'),
            'keyword' => $request->get('keyword')
        ];

        $perPage = $request->get('per_page', 5);

        $users = $this->userService->getUsers($filters, $perPage);
        $statistics = $this->userService->getUserStatistics();

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem()
                ],
                'statistics' => $statistics
            ]
        ]);
    }

    /**
     * API: Lấy chi tiết user
     *
     * @param string|int $userId
     * @return JsonResponse
     */
    public function show(string|int $userId): JsonResponse
    {
        $user = $this->userService->getUserDetail((int)$userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy người dùng'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * API: Tạo user mới (với Request Validation)
     *
     * @param StoreUserRequest $request
     * @return JsonResponse
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $result = $this->userService->createUser($request->validated());

        $statusCode = $result['success'] ? 201 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * API: Cập nhật user (với Request Validation)
     *
     * @param UpdateUserRequest $request
     * @param string|int $userId
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $request, string|int $userId): JsonResponse
    {
        $result = $this->userService->updateUser((int)$userId, $request->validated());

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * API: Khóa/Mở khóa user
     *
     * @param string|int $userId
     * @return JsonResponse
     */
    public function toggleStatus(string|int $userId): JsonResponse
    {
        $result = $this->userService->toggleUserStatus((int)$userId);

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * API: Xóa user
     *
     * @param string|int $userId
     * @return JsonResponse
     */
    public function destroy(string|int $userId): JsonResponse
    {
        $result = $this->userService->deleteUser((int)$userId);

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * API: Lấy thống kê users
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        $statistics = $this->userService->getUserStatistics();

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }
    /**
     * API: Import users hàng loạt
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $request->validate([
            'import_type' => 'required|in:paste,file',
            'data' => 'required_if:import_type,paste|string',
            'file' => 'required_if:import_type,file|file|mimes:csv,txt,xlsx,xls|max:5120', // Max 5MB
        ], [
            'data.required_if' => 'Vui lòng nhập dữ liệu',
            'file.required_if' => 'Vui lòng chọn file',
            'file.mimes' => 'File phải là định dạng CSV, TXT hoặc Excel',
            'file.max' => 'File không được vượt quá 5MB',
        ]);

        try {
            $content = '';

            if ($request->import_type === 'paste') {
                // Dán trực tiếp
                $content = $request->data;
            } else {
                // Upload file
                $file = $request->file('file');
                $extension = $file->getClientOriginalExtension();

                if (in_array($extension, ['xlsx', 'xls'])) {
                    // Xử lý Excel (cần cài thư viện)
                    return response()->json([
                        'success' => false,
                        'message' => 'Chức năng import Excel đang được phát triển. Vui lòng sử dụng CSV.'
                    ], 422);
                } else {
                    // Đọc CSV
                    $content = file_get_contents($file->getRealPath());
                }
            }

            // Parse dữ liệu
            $usersData = $this->userService->parseImportData($content, 'csv');

            if (empty($usersData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy dữ liệu hợp lệ'
                ], 422);
            }

            // Import hàng loạt
            $result = $this->userService->bulkCreateUsers($usersData);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Download file mẫu CSV
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadTemplate()
    {
        // Thêm UTF-8 BOM để Excel nhận diện đúng encoding tiếng Việt
        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM
        // Sử dụng dấu chấm phẩy (;) làm delimiter cho Excel Việt Nam
        $csv .= "Họ và tên;Email;Vai trò;Số điện thoại;Địa chỉ;Mật khẩu\n";
        $csv .= "Nguyễn Văn A;nguyenvana@university.edu;STUDENT;0912345678;Hà Nội;password123\n";
        $csv .= "Trần Thị B;tranthib@university.edu;LECTURER;0987654321;TP.HCM;lecturer@2026\n";
        $csv .= "Phạm Văn C;phamvanc@university.edu;ADMIN;0909123456;Đà Nẵng;admin@secure\n";

        return response($csv, 200)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="user_template.csv"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
