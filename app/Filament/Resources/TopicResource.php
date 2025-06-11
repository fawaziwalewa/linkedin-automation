<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Topic;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use App\Console\Commands\GenerateContent;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\TopicResource\Pages;

class TopicResource extends Resource
{
    protected static ?string $model = Topic::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('topic')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options([
                        'Approved' => 'Approved',
                        'Declined' => 'Declined',
                        'Ready for review' => 'Ready for review',
                        'Generated' => 'Generated',
                    ])
                    ->default('Approved')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('preferred_framework')
                    ->options([
                        'What? What So? What Now?' => 'What? What So? What Now?',
                        'Issue–Impact–Resolution' => 'Issue–Impact–Resolution',
                        'Problem–Agitate–Solution' => 'Problem–Agitate–Solution',
                        'Situation–Impact–Action' => 'Situation–Impact–Action',
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('topic')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Topic copied to clipboard')
                    ->copyMessageDuration(1500)
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        // Only render the tooltip if the column content exceeds the length limit.
                        return $state;
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(Topic $record) => match ($record->status) {
                        'Approved' => 'success',
                        'Declined' => 'danger',
                        default => 'warning',
                    })
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('preferred_framework')
                    ->badge()
                    ->color(fn(Topic $record) => match ($record->preferred_framework) {
                        'What? What So? What Now?' => 'primary',
                        'Issue–Impact–Resolution' => 'warning',
                        'Problem–Agitate–Solution' => 'info',
                        'Situation–Impact–Action' => 'success',
                        default => 'default',
                    })
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('createPost')
                    ->label('')
                    ->icon('heroicon-o-plus')
                    ->tooltip('Create Post')
                    ->extraAttributes([
                        'class' => 'border-2 dark:border-blue-400 rounded px-2 py-1 !gap-0',
                    ])
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Create Post')
                    ->modalDescription('Are you sure you want to create a post for this topic?')
                    ->action(function (Topic $record) {
                        $response = (new GenerateContent)->generate($record->topic, $record->preferred_framework);
                        if($response){
                            $content = $response['content'] ?? '';
                            $framework = $response['framework'] ?? $record->preferred_framework;
                            $post = new \App\Models\Post();
                            $post->topic = $record->topic;
                            $post->content = $content;
                            $post->framework = $framework;
                            $post->save();
                            $record->status = 'Generated';
                            $record->save();

                            Notification::make()
                                ->title('Post Created')
                                ->body("Post for topic '{$record->topic}' has been created successfully.")
                                ->success()
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Edit Topic')
                    ->extraAttributes([
                        'class' => 'border-2 dark:border-blue-400 rounded px-2 py-1 !gap-0',
                    ])
                    ->modalWidth('lg'),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Delete Topic')
                    ->extraAttributes([
                        'class' => 'border-2 dark:border-red-400 rounded px-2 py-1 !gap-0',
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->modifyQueryUsing(function (Builder $query) {
                return $query->latest();
            });
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTopics::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\TopicResource\Widgets\StatsOverview::class,
        ];
    }
}
