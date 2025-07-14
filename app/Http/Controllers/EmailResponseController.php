<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\EmailResponse;
use Illuminate\Http\Request;

class EmailResponseController extends Controller
{
    public function store(Request $request, Email $email)
    {
        $request->validate([
            'status' => 'required|string|max:255',
            'staff_name' => 'nullable|string|max:255',
            'handled_at' => 'nullable|date',
            'method' => 'nullable|string|max:255',
            'memo' => 'nullable|string',
        ]);

        EmailResponse::create([
            'email_id' => $email->id,
            'status' => $request->status,
            'staff_name' => $request->staff_name,
            'handled_at' => $request->handled_at,
            'method' => $request->method,
            'memo' => $request->memo,
        ]);

        return redirect()->route('emails.show', $email->id)->with('success', '対応状況を登録しました。');
    }

    public function update(Request $request, $id)
    {
        $response = EmailResponse::findOrFail($id);

        $request->validate([
            'status' => 'required|string|max:255',
            'staff_name' => 'nullable|string|max:255',
            'handled_at' => 'nullable|date',
            'method' => 'nullable|string|max:255',
            'memo' => 'nullable|string',
        ]);

        $input = $request->only(['status', 'staff_name', 'handled_at', 'method', 'memo']);

        $hasChanges = false;
        foreach ($input as $key => $value) {
            if ($response->{$key} != $value) {
                $response->{$key} = $value;
                $hasChanges = true;
            }
        }

        if ($hasChanges) {
            $response->save();
            return redirect()->route('emails.show', $response->email_id)->with('success', '対応状況を更新しました。');
        }

        return redirect()->route('emails.show', $response->email_id)->with('info', '変更はありませんでした。');
    }

    public function destroy($id)
    {
        $response = EmailResponse::findOrFail($id);
        $response->delete();

        return back()->with('success', '対応状況を削除しました。');
    }
}