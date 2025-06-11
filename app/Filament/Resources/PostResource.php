<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Post;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Console\Commands\PostToLinkedIn;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Console\Commands\HumanizePostContent;
use App\Filament\Resources\PostResource\Pages;
use Webbingbrasil\FilamentCopyActions\Forms\Actions\CopyAction;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('topic')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->suffixAction(CopyAction::make()),
                Forms\Components\Textarea::make('content')
                    ->columnSpanFull()
                    ->hintAction(CopyAction::make())
                    ->autosize(),
                Forms\Components\Textarea::make('humanized_content')
                    ->columnSpanFull()
                    ->hintActions([
                        CopyAction::make(),
                        Forms\Components\Actions\Action::make('humanize')
                            ->label('Humanize')
                            ->action(function (Get $get, Set $set, Post $record) {
                                if (!$get('content')) {
                                    Notification::make()
                                        ->title('Content is required')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                $content = (new HumanizePostContent)->humanizeWithFineTune($get('content'));
                                $record->update(['humanized_content' => $content]);
                                $set('humanized_content', $content);

                                Notification::make()
                                    ->title('Content humanized successfully')
                                    ->success()
                                    ->send();
                            })
                            ->requiresConfirmation()
                            ->modalDescription('Are you sure you want to humanize the content? It will automatically rewrite and save the humanized content field.')
                            ->color('success'),
                    ])
                    ->autosize(),
                Forms\Components\FileUpload::make('image')
                    ->image()
                    ->columnSpanFull(),
                Forms\Components\Select::make('framework')
                    ->options([
                        'What? What So? What Now?' => 'What? What So? What Now?',
                        'Issue–Impact–Resolution' => 'Issue–Impact–Resolution',
                        'Problem–Agitate–Solution' => 'Problem–Agitate–Solution',
                        'Situation–Impact–Action' => 'Situation–Impact–Action',
                    ]),
                Forms\Components\Select::make('status')
                    ->options([
                        'Approved' => 'Approved',
                        'Posted' => 'Posted',
                        'Declined' => 'Declined',
                        'Humanized' => 'Humanized',
                        'Pending' => 'Pending',
                    ])
                    ->default('Pending')
                    ->required(),
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
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\TextColumn::make('framework')
                    ->badge()
                    ->color(fn(Post $record) => match ($record->framework) {
                        'What? What So? What Now?' => 'primary',
                        'Issue–Impact–Resolution' => 'warning',
                        'Problem–Agitate–Solution' => 'info',
                        'Situation–Impact–Action' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(Post $record) => match ($record->status) {
                        'Approved' => 'success',
                        'Posted' => 'success',
                        'Declined' => 'danger',
                        'Humanized' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
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
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->tooltip('Preview Post')
                    ->modalHeading('Post Preview')
                    ->extraAttributes([
                        'class' => 'border-2 dark:border-gray-500 rounded px-2 py-1 !gap-0',
                    ]),
                Tables\Actions\Action::make('postToLinkedIn')
                    ->label('')
                    ->tooltip('Post to LinkedIn')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Post to LinkedIn Confirmation')
                    ->modalDescription('Are you sure you want to post this content to LinkedIn?')
                    ->action(function (Post $record) {
                        // Logic to post to LinkedIn
                        $asset = (new PostToLinkedIn)->uploadImageToLinkedIn($record->image);
                        $post = (new PostToLinkedIn)->publishToLinkedIn($record->humanized_content, $asset);

                        if ($post) {
                            $record->update(['status' => 'Posted']);
                            Notification::make()
                                ->title('Post published successfully')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Failed to publish post')
                                ->danger()
                                ->send();
                        }
                    })
                    ->extraAttributes([
                        'class' => 'border-2 dark:border-blue-400 rounded px-2 py-1 !gap-0',
                    ]),
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Edit Post')
                    ->extraAttributes([
                        'class' => 'border-2 dark:border-blue-400 rounded px-2 py-1 !gap-0',
                    ])
                    ->modalWidth('lg'),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Delete Post')
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
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\PostResource\Widgets\StatsOverview::class,
        ];
    }
}
