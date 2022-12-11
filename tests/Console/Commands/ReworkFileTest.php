<?php

namespace Tests\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Tests\TestCase;

class ReworkFileTest extends TestCase
{
    public function test_rework_without_providing_a_file()
    {
        $this->expectException(RuntimeException::class);
        $this->artisan('rework:file');
    }

    public function test_aborting_the_rework_process()
    {
        $filePath = public_path('users.csv');

        $this->artisan("rework:file $filePath")
            ->expectsConfirmation("Are you sure you want to rework the file '{$filePath}' ?", 'no')
            ->expectsOutput('Rework was aborted')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_running_the_rework_process()
    {
        $filePath = public_path('users.csv');
        $reworkedFile = public_path('users.json');

        $this->artisan("rework:file $filePath")
            ->expectsConfirmation("Are you sure you want to rework the file '{$filePath}' ?", 'yes')
            ->expectsOutput('Processing...')
            ->expectsOutput('The file rework was successful!')
            ->assertExitCode(Command::SUCCESS);

        $this->assertFileExists($reworkedFile);
    }
}
