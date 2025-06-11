<?php

namespace App\Filament\Resources\PostResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $statuses = [
            'Total' => [
                'query' => \App\Models\Post::query(),
                'description' => 'All posts in the app',
                'icon' => 'heroicon-m-document-text',
                'color' => 'primary',
            ],
            'Approved' => [
                'query' => \App\Models\Post::where('status', 'Approved'),
                'description' => 'Approved posts',
                'icon' => 'heroicon-m-check-circle',
                'color' => 'success',
            ],
            'Humanized' => [
                'query' => \App\Models\Post::where('status', 'Humanized'),
                'description' => 'Humanized content',
                'icon' => 'heroicon-m-cog-6-tooth',
                'color' => 'warning',
            ],
            'Declined' => [
                'query' => \App\Models\Post::where('status', 'Declined'),
                'description' => 'Rejected posts',
                'icon' => 'heroicon-m-x-circle',
                'color' => 'danger',
            ],
            'Pending' => [
                'query' => \App\Models\Post::where('status', 'Pending'),
                'description' => 'Awaiting approval',
                'icon' => 'heroicon-m-clock',
                'color' => 'info',
            ],
            'Posted to LinkedIn' => [
                'query' => \App\Models\Post::where('status', 'Posted'),
                'description' => 'Posts shared on LinkedIn',
                'icon' => 'heroicon-m-link',
                'color' => 'success',
            ],
        ];

        return collect($statuses)->map(fn($data, $label) => Stat::make($label, $data['query']->count())
            ->description($data['description'])
            ->descriptionIcon($data['icon'])
            ->color($data['color'])
            ->chart([7, 2, 10, 3, 15, 4, 17]))->toArray();
    }
}
