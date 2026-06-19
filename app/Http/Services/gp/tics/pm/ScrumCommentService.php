<?php

namespace App\Http\Services\gp\tics\pm;

use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\tics\pm\ScrumComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScrumCommentService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return ScrumComment::filter($request)
      ->with('user:id,name')
      ->orderBy('created_at')
      ->get();
  }

  public function find(int $id) { return null; }
  public function show(int $id) { return null; }

  public function store(mixed $data): ScrumComment
  {
    $data['user_id'] = Auth::id();
    return ScrumComment::create($data)->load('user:id,name');
  }

  public function update(mixed $data): ScrumComment
  {
    $comment = ScrumComment::findOrFail($data['id']);
    $comment->update(['content' => $data['content']]);
    return $comment->fresh()->load('user:id,name');
  }

  public function destroy(int $id): void
  {
    ScrumComment::findOrFail($id)->delete();
  }
}
