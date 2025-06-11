<?php

namespace App\Filament\Resources\TopicResource\Widgets;

use App\Models\Topic;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $statuses = [
            'Total' => [
                'query' => Topic::query(),
                'description' => 'All topics in the app',
                'icon' => 'heroicon-m-document-text',
                'color' => 'primary',
            ],
            'Approved' => [
                'query' => Topic::where('status', 'Approved'),
                'description' => 'Approved topics',
                'icon' => 'heroicon-m-check-circle',
                'color' => 'success',
            ],
            'Generated' => [
                'query' => Topic::where('status', 'Generated'),
                'description' => 'AI-generated content',
                'icon' => 'heroicon-m-cog-6-tooth',
                'color' => 'warning',
            ],
            'Declined' => [
                'query' => Topic::where('status', 'Declined'),
                'description' => 'Rejected topics',
                'icon' => 'heroicon-m-x-circle',
                'color' => 'danger',
            ],
            'Ready for review' => [
                'query' => Topic::where('status', 'Ready for review'),
                'description' => 'Awaiting approval',
                'icon' => 'heroicon-m-check-badge',
                'color' => 'info',
            ],
        ];

        return collect($statuses)->map(fn($data, $label) => Stat::make($label, $data['query']->count())
            ->description($data['description'])
            ->descriptionIcon($data['icon'])
            ->color($data['color'])
            ->chart([7, 2, 10, 3, 15, 4, 17]))->toArray();
    }
}
