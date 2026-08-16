<?php

namespace App\Listeners;

use App\Actions\RecordActivity;
use App\Enums\ActivityAction;
use App\Models\User;
use Illuminate\Auth\Events\Logout;

class RecordUserLogout
{
    public function __construct(private RecordActivity $activity) {}

    public function handle(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->activity->auth($event->user, ActivityAction::LoggedOut);
        }
    }
}
