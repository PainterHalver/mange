<?php

namespace App\Http\Controllers;

use App\Http\Resources\Chapter as ChapterResources;
use App\Models\Chapter;
use App\Models\Manga;
use App\Models\View;
use App\Traits\AuthTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Bus;
use App\Jobs\UploadImage;
use Illuminate\Bus\Batch;
use Throwable;
use ZipArchive;

class ChapterController extends Controller
{
    //
    use AuthTrait;

    public function show(Request $request)
    {
        $user = $this->getUser($request);

        $request->merge(['id' => $request->route('chapter_id')]);
        $fields = $this->validate($request, [
            'id' => 'required|integer|min:1',
        ]);

        $chapter = Chapter::query()
            ->with('images')
            ->with('manga')
            ->findOrFail($fields['id']);
        $chapter = new ChapterResources($chapter);

        if (! Redis::exists("{$request->ip()}_{$chapter['manga']['id']}")) {
            if ($user) {
                View::create([
                    'user_id' => $user->id,
                    'manga_id' => $chapter['manga']['id'],
                    'chapter_id' => $chapter['id'],
                ]);
            }
            Redis::setex($request->ip()."_{$chapter['manga']['id']}", 120, 1);
            Manga::where('id', $chapter['manga']['id'])->update(['view' => $chapter['manga']['view'] + 1]);
        }

        return response()->json([
            'success' => 1,
            'data' => $chapter,
            'message' => 'get chapter data success',
        ], 200);
    }

    public function create(Request $request) {
        $manga = Manga::findOrFail($request->manga_id);
        $mangaFolder = explode('/', $manga->thumbnail)[0];
        $manga_id = $request->manga_id;
        $name = $request->name;
        $number = $request->number;

        $images = $request->file('images');
        $batches = [];
        $count = count($images);

        for($i = 0; $i < count($images); $i++) {
            $imageName = $i . $images[$i]->getClientOriginalName();
            $batches[] = new UploadImage($images[$i]->getRealPath(), $mangaFolder, $number, $imageName);
        }

        $batch = Bus::batch($batches)->dispatch();

        try {
            return response()->stream(function () use ($batch) {
                while ($batch->finished() === false) {
                    $batch = $batch->fresh();
                    echo "data: {$batch->processedJobs()}/{$batch->totalJobs} {$batch->progress()}%\n\n";
                    ob_flush();
                    flush();
                    sleep(1);
                    if (connection_aborted()) {
                        break;
                    }
                }
            }, 200, [
                'Cache-Control' => 'no-cache',
                'Content-Type' => 'text/event-stream',
                'X-Accel-Buffering' => 'no',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => 0,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
