<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivity;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tên và mô tả 6 nhóm Holland (hiện trên trang kết quả, email, trang giới thiệu).
 */
class GroupController extends Controller
{
    public const GROUPS = ['R', 'I', 'A', 'S', 'E', 'C'];

    public function edit(): View
    {
        return view('admin.groups.edit', [
            'groups' => collect(self::GROUPS)->map(fn (string $letter) => [
                'letter' => $letter,
                'name' => setting("group_name_{$letter}", $letter),
                'description' => setting("group_desc_{$letter}"),
            ]),
            'activities' => AdminActivity::forSubject(AdminActivity::SUBJECT_GROUPS, null),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];
        $messages = [];

        foreach (self::GROUPS as $letter) {
            $rules["name.{$letter}"] = ['required', 'string', 'max:50'];
            $rules["description.{$letter}"] = ['nullable', 'string', 'max:500'];
            $messages["name.{$letter}.required"] = 'Vui lòng nhập tên nhóm.';
            $messages["description.{$letter}.max"] = 'Mô tả tối đa 500 ký tự.';
        }

        $validated = $request->validate($rules, $messages);

        $before = [];
        $after = [];

        foreach (self::GROUPS as $letter) {
            foreach (["group_name_{$letter}" => $validated['name'][$letter], "group_desc_{$letter}" => $validated['description'][$letter] ?? ''] as $key => $value) {
                $old = Setting::query()->where('key', $key)->value('value');

                if ((string) $old !== (string) $value) {
                    $before[$key] = $old;
                    $after[$key] = $value;
                    Setting::put($key, $value, 'result');
                }
            }
        }

        if ($after === []) {
            return back()->with('status', 'Không có thay đổi nào.');
        }

        AdminActivity::record(AdminActivity::ACTION_UPDATE, AdminActivity::SUBJECT_GROUPS, null, 'Sửa mô tả 6 nhóm ('.count($after).' ô)', $before, $after);

        return back()->with('status', 'Đã lưu. Trang kết quả hiện ngay chữ mới.');
    }
}
