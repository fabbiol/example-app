<?php

namespace App\Models\Concerns;

use App\Actions\RecordActivity;
use App\Enums\ActivityAction;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function (Model $model): void {
            app(RecordActivity::class)->record($model, ActivityAction::Created);
        });

        static::updated(function (Model $model): void {
            app(RecordActivity::class)->record($model, ActivityAction::Updated);
        });

        static::deleted(function (Model $model): void {
            app(RecordActivity::class)->record($model, ActivityAction::Deleted);
        });
    }

    /**
     * @return list<string>
     */
    public function activityIgnoredAttributes(): array
    {
        return ['updated_at', 'remember_token'];
    }
}
