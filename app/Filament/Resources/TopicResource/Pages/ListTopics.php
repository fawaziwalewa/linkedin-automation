<?php

namespace App\Filament\Resources\TopicResource\Pages;

use App\Models\Topic;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Components\Tab;
use App\Filament\Imports\TopicImporter;
use Illuminate\Support\Facades\Storage;
use App\Filament\Resources\TopicResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTopics extends ListRecords
{
    protected static string $resource = TopicResource::class;

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
            ->visible(fn (): bool => !Storage::disk('local')->exists('linkedin.json'));
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalHeading('Create New Topic')
                ->modalWidth('lg'),
            Actions\ImportAction::make()
                ->importer(TopicImporter::class)
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\TopicResource\Widgets\StatsOverview::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->badge(Topic::count()),
            'ready_for_review' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'Ready for review'))
                ->badge(Topic::where('status', 'Ready for review')->count()),
            'approved' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'Approved'))
                ->badge(Topic::where('status', 'Approved')->count()),
            'generated' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'Generated'))
                ->badge(Topic::where('status', 'Generated')->count()),
            'declined' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'Declined'))
                ->badge(Topic::where('status', 'Declined')->count()),
            'WWW' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('preferred_framework', 'What? What So? What Now?'))
                ->badge(Topic::where('preferred_framework', 'What? What So? What Now?')->count()),
            'IIR' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('preferred_framework', 'Issue–Impact–Resolution'))
                ->badge(Topic::where('preferred_framework', 'Issue–Impact–Resolution')->count()),
            'PAS' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('preferred_framework', 'Problem–Agitate–Solution'))
                ->badge(Topic::where('preferred_framework', 'Problem–Agitate–Solution')->count()),
            'SIA' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('preferred_framework', 'Situation–Impact–Action'))
                ->badge(Topic::where('preferred_framework', 'Situation–Impact–Action')->count()),
        ];
    }
}
