<?php

namespace App\Filament\Imports;

use App\Models\Topic;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class TopicImporter extends Importer
{
    protected static ?string $model = Topic::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('topic')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('status')
                ->rules(['in:Approved,Declined,Ready for review,Generated']),
            ImportColumn::make('preferred_framework')
                ->rules(['in:What? What So? What Now?,Issue–Impact–Resolution,Problem–Agitate–Solution,Situation–Impact–Action']),
        ];
    }

    public function resolveRecord(): ?Topic
    {
        // return Topic::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return new Topic();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your topic import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
