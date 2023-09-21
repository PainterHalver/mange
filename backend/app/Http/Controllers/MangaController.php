<?php

namespace App\Http\Controllers;

use App\Http\Resources\Manga as MangaResource;
use App\Models\Manga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MangaController extends Controller
{
    public function index(Request $request)
    {
        $fields = $this->validate($request, [
            'per_page' => 'integer|min:1',
            'page' => 'integer|min:1',
            'category' => 'array',
            'search' => 'string',
            'sort' => [
                'string',
                'regex:/^([+-]?)(updated_at|follow_count|view_count|comment_count|vote_score|status)$/',
            ],
        ], [
            'sort.regex' => 'The sort field must be one of: updated_at, follow_count, view_count, comment_count, vote_score, status',
        ]);

        $per_page = $fields['per_page'] ?? 10;
        $page = $fields['page'] ?? 1;
        $category_ids = $fields['category'] ?? [];
        $search_query = $fields['search'] ?? '';
        $sort = $fields['sort'] ?? '-updated_at';

        $query = Manga::query()->select(['id', 'name', 'thumbnail', 'view as view_count', 'status'])
            ->with(['chapters', 'categories', 'othernames'])
            ->withCount(['bookmarked_by as follow_count', 'comments as comment_count']);

        // Tìm kiếm theo `tên` hoặc `tên khác` chứa
        if (! empty($search_query)) {
            $query->where(function ($subQuery) use ($search_query) {
                $subQuery->where('name', 'like', "%{$search_query}%")
                    ->orWhereHas('othernames', function ($subQuery) use ($search_query) {
                        $subQuery->where('name', 'like', "%{$search_query}%");
                    });
            });
        }

        // Filter theo category
        if (! empty($category_ids)) {
            $query->whereHas('categories', function ($subQuery) use ($category_ids) {
                $subQuery->select(DB::raw('count(distinct categories.id)'))
                    ->whereIn('category_id', $category_ids);
            }, '=', count($category_ids));
        }

        // Lấy điểm trung bình vote
        $query->withAvg('voted_by as vote_score', 'votes.score');

        // Sắp xếp
        $sort_direction = $sort[0] === '-' ? 'desc' : 'asc';
        $sort_field = $sort[0] === '-' || $sort[0] === '+' ? substr($sort, 1) : $sort;
        $query->orderBy($sort_field, $sort_direction);

        // Phân trang
        $mangas = $query->paginate($per_page, ['*'], 'page', $page);

        return MangaResource::collection($mangas);
    }

    public function show(Request $request)
    {
        $request->merge(['id' => $request->route('manga_id')]);
        $fields = $this->validate($request, [
            'id' => 'required|integer|min:1',
        ]);

        $manga = Manga::query()->select(['mangas.*', 'view as view_count'])
            ->with(['othernames', 'authors'])
            ->with(['chapters' => function ($subQuery) {
                $subQuery->orderBy('id', 'desc');
            }])
            ->with(['categories' => function ($subQuery) {
                $subQuery->select(['categories.id', 'name']);
            }])
            ->withCount(['bookmarked_by as follow_count', 'comments as comment_count', 'voted_by as vote_count'])
            ->withAvg('voted_by as vote_score', 'votes.score')
            ->findOrFail($fields['id']);

        // Thêm thông tin nếu user đã đăng nhập
        $user = Auth::guard('sanctum')->user();
        if ($user) {
            $manga->user_bookmarked = $manga->bookmarked_by()->where('user_id', $user->id)->exists();
            $manga->user_vote = $manga->voted_by()->where('user_id', $user->id)->value('score');
            $manga->user_latest_chapter_id = $user->viewed_chapters()
                ->where('views.manga_id', $manga->id)->max('chapter_id');
        }

        return response()->json($manga);
    }
}
