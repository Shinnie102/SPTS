<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Profile extends Controller
{
    // Hiển thị hồ sơ
    public function index()
    {
        $lecturer = Auth::user(); // 👈 GIẢNG VIÊN ĐANG LOGIN

        return view('lecturer.profileLecturer', compact('lecturer'));
    }

    // Cập nhật hồ sơ
    public function update(Request $request)
    {
        $lecturer = Auth::user();

        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'address'   => 'nullable|string|max:255',
            'birth'     => 'nullable|date',
            'major'     => 'nullable|string|max:255',
        ]);

        $lecturer->update($request->only([
            'full_name',
            'phone',
            'address',
            'birth',
            'major',
        ]));

        return back()->with('success', 'Cập nhật hồ sơ thành công');
    }
}
