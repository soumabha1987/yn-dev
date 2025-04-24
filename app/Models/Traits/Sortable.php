<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Lottery;

trait Sortable
{
    public static function bootSortable(): void
    {
        static::addGlobalScope(fn ($query) => $query->orderBy('position'));

        static::creating(function ($model) {
            $max = static::query()->max('position') ?? -1;

            $model->position = $max + 1;
        });

        static::deleting(function ($model) {
            $model->displace();
        });
    }

    public function move(int $position): void
    {
        Lottery::odds(2, outOf: 100)
            ->winner(fn () => $this->arrange())
            ->choose();

        DB::transaction(function () use ($position): void {
            $current = $this->position;
            $after = $position;

            // If there was no position change, don't shift...
            if ($current === $after) {
                return;
            }

            // Move the target todo out of the position stack...
            $this->update(['position' => -1]);

            // Grab the shifted block and shift it up or down...
            $block = static::query()->whereBetween('position', [
                min($current, $after),
                max($current, $after),
            ]);

            $needToShiftBlockUpBecauseDraggingTargetDown = $current < $after;

            $needToShiftBlockUpBecauseDraggingTargetDown
                ? $block->decrement('position')
                : $block->increment('position');

            // Place target back in position stack...
            $this->update(['position' => $after]);
        });
    }

    public function arrange(): void
    {
        DB::transaction(function (): void {
            $position = 0;

            foreach (static::query()->get() as $model) {
                $model->position = $position++;

                $model->save();
            }
        });
    }

    public function displace(): void
    {
        $this->move(999999);
    }
}
