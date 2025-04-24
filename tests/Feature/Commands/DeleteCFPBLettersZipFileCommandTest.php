<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteCFPBLettersZipFileCommandTest extends TestCase
{
    #[Test]
    public function it_can_removes_zip_files_older_than_three_days(): void
    {
        Storage::fake();

        $zipDirectory = 'public/cfpb-consumer/zips/';

        $pastFewDaysAgo = today()->subDays(fake()->numberBetween(5, 100))->format('Y_m_d');

        $pastFile = $zipDirectory . $pastFewDaysAgo . '_' . fake()->randomNumber() . '_' . str()->random(10) . '.zip';
        $fourDaysOldFile = $zipDirectory . today()->subDays(4)->format('Y_m_d') . '_' . fake()->randomNumber() . '_' . str()->random(10) . '.zip';
        $borderFile = $zipDirectory . today()->subDays(3)->format('Y_m_d') . '_' . fake()->randomNumber() . '_' . str()->random(10) . 'zip';
        $newFile = $zipDirectory . today()->format('Y_m_d') . '_' . fake()->randomNumber() . '_' . str()->random(10) . 'zip';

        Storage::put($pastFile, 'Past Few Days Ago File Content');
        Storage::put($fourDaysOldFile, '4 Days Ago File Content');
        Storage::put($borderFile, '3 Days Ago File Content');
        Storage::put($newFile, 'New File Content');

        $this->artisan('delete:cfpb-letters-zip')->assertOk();

        Storage::assertMissing($pastFile);
        Storage::assertMissing($fourDaysOldFile);
        Storage::assertExists($borderFile);
        Storage::assertExists($newFile);
    }

    #[Test]
    public function it_can_no_any_removes_zip_files_older_than_three_days(): void
    {
        Storage::fake();

        $zipDirectory = 'public/cfpb-consumer/zips/';

        $borderFile = $zipDirectory . today()->subDays(3)->format('Y_m_d') . '_' . fake()->randomNumber() . '_' . str()->random(10) . 'zip';
        $twoDaysOldFile = $zipDirectory . today()->subDays(2)->format('Y_m_d') . '_' . fake()->randomNumber() . '_' . str()->random(10) . 'zip';
        $newFile = $zipDirectory . today()->format('Y_m_d') . '_' . fake()->randomNumber() . '_' . str()->random(10) . 'zip';

        Storage::put($borderFile, '3 Days Ago File Content');
        Storage::put($twoDaysOldFile, '2 Days Ago File Content');
        Storage::put($newFile, 'New File Content');

        $this->artisan('delete:cfpb-letters-zip')->assertOk();

        Storage::assertExists($borderFile);
        Storage::assertExists($twoDaysOldFile);
        Storage::assertExists($newFile);
    }
}
