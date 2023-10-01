<?php

namespace App\Jobs;

use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UploadImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public $filePath;
    public $mangaFolder;
    public $number;
    public $imageName;

    public function __construct($filePath, $mangaFolder, $number, $imageName)
    {
        //
        $this->filePath = $filePath;
        $this->mangaFolder = $mangaFolder;
        $this->number = $number;
        $this->imageName = $imageName;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Storage::disk('ftp')->put("{$this->mangaFolder}/{$this->number}/{$this->imageName}", file_get_contents($this->filePath));
    }
}
