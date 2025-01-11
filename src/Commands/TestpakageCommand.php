<?php

namespace vendornamespace\Testpakage\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Spatie\StructureDiscoverer\Discover;
use vendornamespace\Testpakage\Attributes\EnumToJson;

class TestpakageCommand extends Command
{
    public $signature = 'enum-to-json:generate';

    public $description = 'It creates a json file from an enum';

    public function handle(): int
    {
        $enums = $this->getEnums();

        dd($enums);


        $this->comment('All done');

        return self::SUCCESS;
    }

    protected function getEnums(): Collection
    {
        $enums = Discover::in(...config('testpakage.enum_locations'))
            ->enums()
            ->withAttribute(EnumToJson::class)
            ->get();

        return collect($enums);
    }
}
