<?php

namespace App\Actions;

use App\Enums\ActivityAction;
use App\Enums\ActivityDomain;
use App\Models\ActivityLog;
use App\Models\CrushingCircuit;
use App\Models\Customer;
use App\Models\EstimatedLoading;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\Role;
use App\Models\Truck;
use App\Models\User;
use App\Models\WeighTicket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class RecordActivity
{
    private static bool $recording = true;

    /**
     * @return list<string>
     */
    private const array Secrets = [
        'password',
        'password_confirmation',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public static function withoutRecording(callable $callback): mixed
    {
        $previous = self::$recording;
        self::$recording = false;

        try {
            return $callback();
        } finally {
            self::$recording = $previous;
        }
    }

    public function record(Model $model, ActivityAction $action): void
    {
        if (! self::$recording) {
            return;
        }

        $domain = $this->domainFor($model);

        if ($domain === null) {
            return;
        }

        $properties = $this->properties($model, $action);

        if ($action === ActivityAction::Updated && $properties === []) {
            return;
        }

        [$articleAndNoun, $name] = $this->subject($model);

        ActivityLog::query()->create([
            'user_id' => auth()->id(),
            'domain' => $domain,
            'action' => $action,
            'subject_type' => $model->getMorphClass(),
            'subject_id' => $model->getKey(),
            'description' => trim($action->label().' '.$articleAndNoun.' '.$name),
            'properties' => $properties,
        ]);
    }

    public function auth(User $user, ActivityAction $action): void
    {
        if (! self::$recording) {
            return;
        }

        ActivityLog::query()->create([
            'user_id' => $user->id,
            'domain' => ActivityDomain::Administrative,
            'action' => $action,
            'subject_type' => $user->getMorphClass(),
            'subject_id' => $user->id,
            'description' => $action === ActivityAction::LoggedIn
                ? 'Entrou no sistema'
                : 'Saiu do sistema',
            'properties' => [],
        ]);
    }

    private function domainFor(Model $model): ?ActivityDomain
    {
        return match ($model::class) {
            Order::class,
            ProductionEntry::class,
            EstimatedLoading::class,
            WeighTicket::class => ActivityDomain::Operational,
            Product::class,
            Customer::class,
            User::class,
            Role::class,
            Truck::class,
            CrushingCircuit::class => ActivityDomain::Administrative,
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function properties(Model $model, ActivityAction $action): array
    {
        $ignored = method_exists($model, 'activityIgnoredAttributes')
            ? $model->activityIgnoredAttributes()
            : ['updated_at', 'remember_token'];

        $hidden = array_merge($model->getHidden(), self::Secrets, $ignored);

        if ($action === ActivityAction::Updated) {
            $changes = Arr::except($model->getChanges(), $hidden);

            foreach (array_keys($changes) as $attribute) {
                if (in_array($attribute, self::Secrets, true)) {
                    $changes[$attribute] = '[oculto]';
                }
            }

            return $changes;
        }

        $attributes = Arr::except($model->getAttributes(), $hidden);

        foreach (array_keys($attributes) as $attribute) {
            if (in_array($attribute, self::Secrets, true)) {
                $attributes[$attribute] = '[oculto]';
            }
        }

        return $attributes;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function subject(Model $model): array
    {
        return match (true) {
            $model instanceof Order => ['o pedido', '#'.$model->getKey()],
            $model instanceof ProductionEntry && $model->parent_id !== null => ['o apontamento do circuito', '#'.$model->getKey()],
            $model instanceof ProductionEntry => ['o apontamento de produção', '#'.$model->getKey()],
            $model instanceof EstimatedLoading => ['o carregamento', $model->referenceLabel()],
            $model instanceof WeighTicket => ['o ticket de balança', $model->number !== '' ? $model->number : '#'.$model->getKey()],
            $model instanceof Product => ['o produto', (string) $model->name],
            $model instanceof Customer => ['o cliente', (string) $model->name],
            $model instanceof User => ['a pessoa', (string) $model->name],
            $model instanceof Role => ['o papel', (string) $model->name],
            $model instanceof Truck => ['o caminhão', (string) $model->name],
            $model instanceof CrushingCircuit => ['o circuito', (string) $model->name],
            default => ['o registro', '#'.$model->getKey()],
        };
    }
}
