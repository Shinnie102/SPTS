<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Profile extends Controller
{
    public function index()
    {
        /** @var User $lecturer */
        $lecturer = Auth::user();

        return view('lecturer.profileLecturer', compact('lecturer'));
    }

    public function edit()
    {
        /** @var User $lecturer */
        $lecturer = Auth::user();

        return view('lecturer.editProfileLecturer', compact('lecturer'));
    }

    public function update(Request $request)
    {
        /** @var User $lecturer */
        $lecturer = Auth::user();

        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'address'   => 'nullable|string|max:255',
            'birth'     => 'nullable|date',
            // ❌ XÓA VALIDATION CHO 'major' - KHÔNG CHO SỬA
        ]);

        // ✅ CHỈ CẬP NHẬT CÁC TRƯỜNG ĐƯỢC PHÉP SỬA (KHÔNG CÓ 'major')
        $lecturer->update($request->only([
            'full_name',
            'phone',
            'address',
            'birth',
            // ❌ KHÔNG CÓ 'major'
        ]));

        return redirect()
            ->route('lecturer.profile')
            ->with('success', 'Cập nhật hồ sơ thành công');
    }
}
