<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BusinessSetting;

class AddBusinessSetting extends Command
{
    protected $signature = 'app:add-business-setting';

    protected $description = 'Add or update a business setting interactively';

    public function handle(): void
    {
        // Q1: Key
        $key = $this->ask('1) What is the setting key?');

        if (empty(trim($key))) {
            $this->error('Key cannot be empty.');
            return;
        }

        $existing = BusinessSetting::query()->where('key', '=', $key)->first();
        if ($existing) {
            if (!$this->confirm("Key \"{$key}\" already exists (current value: {$existing->value}). Overwrite?")) {
                $this->info('Aborted.');
                return;
            }
        }

        // Q2: Value type
        $type = $this->choice(
            '2) What is the value type?',
            ['string', 'number', 'boolean', 'array', 'json'],
            0
        );

        // Q3: Count (only for array / json)
        $count = null;
        if (in_array($type, ['array', 'json'])) {
            $count = (int) $this->ask("3) How many items does the {$type} contain?");

            if ($count < 1) {
                $this->error('Item count must be at least 1.');
                return;
            }
        }

        // Q4: Value(s) — depends on type + count
        $value = $this->collectValue($type, $count);

        BusinessSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        $this->info("Business setting \"{$key}\" saved successfully.");
        $this->line("  Key   : {$key}");
        $this->line("  Type  : {$type}");
        $this->line("  Value : {$value}");
    }

    private function collectValue(string $type, ?int $count): string
    {
        switch ($type) {
            case 'string':
                return (string) $this->ask('4) Enter the string value');

            case 'number':
                $num = $this->ask('4) Enter the numeric value');
                if (!is_numeric($num)) {
                    $this->error('Value is not numeric. Stored as-is.');
                }
                return (string) $num;

            case 'boolean':
                return $this->confirm('4) Boolean value — Yes = true, No = false') ? 'true' : 'false';

            case 'array':
                $items = [];
                for ($i = 1; $i <= $count; $i++) {
                    $items[] = $this->ask("4) Item [{$i}/{$count}]");
                }
                return json_encode($items);

            case 'json':
                $obj = [];
                for ($i = 1; $i <= $count; $i++) {
                    $k = $this->ask("4) Pair [{$i}/{$count}] — key");
                    $v = $this->ask("4) Pair [{$i}/{$count}] — value");
                    $obj[$k] = $v;
                }
                return json_encode($obj);
        }

        return '';
    }
}
