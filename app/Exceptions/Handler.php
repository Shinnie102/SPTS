<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Xử lý API errors cho AJAX requests
        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('admin/users/api/*') || $request->wantsJson()) {
                
                // ModelNotFoundException
                if ($e instanceof ModelNotFoundException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không tìm thấy dữ liệu',
                        'error' => 'Resource not found'
                    ], 404);
                }

                // NotFoundHttpException
                if ($e instanceof NotFoundHttpException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Endpoint không tồn tại',
                        'error' => 'Route not found'
                    ], 404);
                }

                // Validation Exception đã được xử lý tự động bởi Laravel
                
                // Generic error
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra trên server',
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
                ], 500);
            }
        });
    }
}