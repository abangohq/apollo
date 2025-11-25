<?php

namespace App\Jobs\User;

use App\Jobs\General\SendPushNotification;
use App\Models\PushNotification;
use App\Models\User;
use App\Services\Support\BulletinService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UsersBulletin implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $bulletinPayload, public PushNotification $notif)
    {
        $this->queue = 'notifications';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bulletin = new BulletinService($this->bulletinPayload, $this->notif);
        $bulletin->handle($this->bulletinPayload);
    }
}
