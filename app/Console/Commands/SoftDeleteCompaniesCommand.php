<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ConsumerStatus;
use App\Models\Company;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SoftDeleteCompaniesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:plan-expired-companies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Soft delete users of plan expired companies';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Company::query()
            ->whereHas('companyMemberships', function (Builder $query): void {
                $query->where('current_plan_end', '<', today())
                    ->where('auto_renew', false);
            })
            ->where('remove_profile', true)
            ->each(function (Company $company): void {
                DB::beginTransaction();

                try {
                    $company->scheduleExports()->delete();
                    $company->merchants()->delete();

                    $company->users()->update([
                        'email' => DB::raw("CONCAT('deleted-', FLOOR(1000 + (RAND() * 9000)), '-', email)"),
                        'deleted_at' => now(),
                    ]);

                    $company->consumers()->update([
                        'status' => ConsumerStatus::DEACTIVATED,
                        'disputed_at' => now(),
                    ]);

                    $company->delete();

                    DB::commit();

                    Log::channel('daily')->info('This company deleted successfully, company id: ' . $company->id);
                } catch (Exception $exception) {
                    DB::rollBack();

                    Log::channel('daily')->error('This company can not delete', [
                        'company id' => $company->id,
                        'message' => $exception->getMessage(),
                    ]);
                }
            });
    }
}
