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

        if ($request->hasFile('images') && $request->by == 'file') {
            $images = $request->file('images');
            $batches = [];
            $count = count($images);

            for($i = 0; $i < count($images); $i++) {
                $imageName = $i.'.jpg';
                Redis::set("{$mangaFolder}:{$imageName}", file_get_contents($images[$i]));
                array_push($batches, new UploadImage("{$mangaFolder}:{$imageName}", $mangaFolder, $number, $imageName));
            }

            $handler = Bus::batch($batches)
            ->then(function (Batch $batch) use ($count, $manga_id, $number, $name, $mangaFolder) {
                    $chapter = Chapter::create([
                        'manga_id' => $manga_id,
                        'name' => "Chapter {$number}: {$name}",
                        'folder' => "{$mangaFolder}/{$number}/",
                        'amount' => $count,
                    ]);

                    return response()->json([
                        'success' => 1,
                        'data' => $chapter,
                        'message' => 'chapter created',
                    ]);
                }
            )->catch(function (Batch $batch, Exception $error) {
                return response()->json([
                    'success' => 0,
                    'message' => $error->getMessage(),
                ]);
            })->finally(function (Batch $batch) {
                print_r("done uploading");
            })->dispatch();
        } elseif ($request->hasFile('zip') && $request->by == 'zip') {
            $zip = new ZipArchive;
            Storage::disk('ftp')->put("/{$mangaFolder}/{$number}/{$number}.zip", file_get_contents($request->zip));
            if ($zip->open(Storage::disk('ftp')->path("/{$mangaFolder}/{$number}/{$number}.zip")) == true) {
                $zip->extractTo(Storage::disk('ftp')->path("/{$mangaFolder}/{$number}"));
                $zip->close();
            } else {
                dd("error");
            }
        }
        else {
            return response()->json([
                'success' => 0,
                'message' => 'no images found'
            ], 422);
        }
    }
}
