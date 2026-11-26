<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NoteController extends Controller
{
    public function index(Request $request)
    {
        // Lay id cua nguoi dang dang nhap
        $userId = $request->user()->id;

        // Tim trong bang notes nhung dong co user_id trung voi nguoi dang dang nhap
        $notes = DB::table('notes')->where('user_id', $userId)->get();

        // Tra ket qua ve dang JSON, ma 200 la thanh cong
        return response()->json($notes, 200);
    }

    public function update(Request $request, $id)
    {
        // Lay id user dang dang nhap qua Sanctum
        $userId = $request->user()->id;

        // Tim note theo id cua user (khong co hoac khong phai cua user dang tim thi tra ve 404)
        $note = Note::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        // Kiem tra du lieu gui len
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
        ]);

        // Gan gia tri moi vao note va luu xuong database
        $note->fill($validated);
        $note->save();

        // Tra ve note da cap nhat kem ma 200
        return response()->json($note, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $note = Note::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
        ]);

        return response()->json($note, 201);
    }
}
