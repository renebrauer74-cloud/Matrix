<?php

namespace App\Console\Commands;

use App\Actions\Events\CreateDemoEventsAction;
use App\Actions\Events\RemoveDemoEventsAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('matrix:demo {--remove : Beispiel-Veranstaltungen wieder löschen}')]
#[Description('Legt Beispiel-Veranstaltungen an oder entfernt sie wieder')]
class ManageDemoEvents extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CreateDemoEventsAction $createDemoEvents, RemoveDemoEventsAction $removeDemoEvents): int
    {
        if ($this->option('remove')) {
            $this->info($removeDemoEvents->execute().' Beispiel-Veranstaltungen gelöscht.');

            return self::SUCCESS;
        }

        $this->info($createDemoEvents->execute().' Beispiel-Veranstaltungen angelegt (entfernen mit --remove).');

        return self::SUCCESS;
    }
}
