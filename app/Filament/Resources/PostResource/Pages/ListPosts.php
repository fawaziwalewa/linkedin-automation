<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Models\Post;
use Filament\Actions;
use Filament\Actions\Action;
use App\Filament\Imports\PostImporter;
use Filament\Resources\Components\Tab;
use Illuminate\Support\Facades\Storage;
use App\Filament\Resources\PostResource;
use Filament\Resources\Pages\ListRecords;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    public $defaultAction = 'onboarding';

    public function onboardingAction(): Action
    {
        return Action::make('onboarding')
            ->label('LinkedIn Setup')
            ->modalHeading('LinkedIn Onboarding')
            ->modalWidth('lg')
            ->modalSubheading('To post content to LinkedIn, you must first connect your account.')
            ->modalDescription('Ensure you have the following environment variables set in your .env file before continuing:')
            ->modalContent(view('components.linkedin-setup-instructions'))
            ->action(fn() => redirect()->to(route('linkedin.auth')))
            ->modalSubmitActionLabel('Connect LinkedIn Account')
            ->visible(fn(): bool => !Storage::disk('local')->exists('linkedin.json'));
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\ImportAction::make()
                ->importer(PostImporter::class)
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\PostResource\Widgets\StatsOverview::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->badge(Post::count()),
            'approved' => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', 'Approved'))
                ->badge(Post::where('status', 'Approved')->count()),
            'humanized' => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', 'Humanized'))
                ->badge(Post::where('status', 'Humanized')->count()),
            'declined' => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', 'Declined'))
                ->badge(Post::where('status', 'Declined')->count()),
            'pending' => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', 'Pending'))
                ->badge(Post::where('status', 'Pending')->count()),
            'posted_to_linkedin' => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', 'Posted'))
                ->badge(Post::where('status', 'Posted')->count()),
        ];
    }
}
