<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = -10;

    protected function getStats(): array
    {
        $stats = [
            // Total number of posts
            'Total Posts' => [
                'query' => \App\Models\Post::query(),
                'description' => 'Total number of posts in the app',
                'icon' => 'heroicon-m-document-text',
                'color' => 'primary',
            ],
            // Total number of topics
            'Total Topics' => [
                'query' => \App\Models\Topic::query(),
                'description' => 'Total number of topics in the app',
                'icon' => 'heroicon-m-clipboard-document-list',
                'color' => 'warning',
            ],
            // Total number of posts posted to LinkedIn
            'Posts Posted to LinkedIn' => [
                'query' => \App\Models\Post::where('status', 'Posted'),
                'description' => 'Posts successfully posted to LinkedIn',
                'icon' => 'heroicon-m-check-circle',
                'color' => 'success',
            ],
        ];

        return collect($stats)->map(fn($data, $label) => Stat::make($label, $data['query']->count())
            ->description($data['description'])
            ->descriptionIcon($data['icon'])
            ->color($data['color'])
            ->chart([7, 2, 10, 3, 15, 4, 17]))->toArray();
    }
}
