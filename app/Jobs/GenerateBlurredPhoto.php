<?php

namespace App\Jobs;

use App\Models\ProfilePhoto;
use App\Services\PhotoBlurService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateBlurredPhoto implements ShouldQueue
{
    use Queueable;

    public function __construct(public ProfilePhoto $photo) {}

    public function handle(PhotoBlurService $blur): void
    {
        $this->photo->update(['blurred_path' => $blur->generate($this->photo)]);
    }
}
