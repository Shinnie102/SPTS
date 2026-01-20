<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Profile extends Controller
{
    public function index()
    {
        return view('student.profileStudent');
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'birth'     => 'nullable|date',
            'address'   => 'nullable|string|max:255',
            'major'     => 'nullable|string|max:255',
        ]);

        $user->update([
            'full_name' => $request->full_name,
            'phone'     => $request->phone,
            'birth'     => $request->birth,
            'address'   => $request->address,
            'major'     => $request->major,
        ]);

        return redirect()
            ->route('student.profile')
            ->with('success', 'Cập nhật hồ sơ thành công');
    }
}
