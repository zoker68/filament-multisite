<?php

namespace Zoker\FilamentMultisite\Filament\Resources\Sites;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;
use Zoker\FilamentMultisite\Filament\Resources\Sites\Pages\CreateSite;
use Zoker\FilamentMultisite\Filament\Resources\Sites\Pages\EditSite;
use Zoker\FilamentMultisite\Filament\Resources\Sites\Pages\ListSites;
use Zoker\FilamentMultisite\Models\Site;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static ?string $slug = 'sites';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Main data')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                if (empty($get('label') ?? '')) {
                                    $set('label', $get('name'));
                                }
                            }),

                        TextInput::make('label')
                            ->live(),

                        TextInput::make('code')
                            ->required(),

                        Select::make('site_group_id')
                            ->label('Site group')
                            ->relationship('group', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Sites in the same group are alternates of each other (hreflang, switcher), independent of domain.')
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                                TextInput::make('code'),
                            ]),

                        TextInput::make('locale')
                            ->label('Locale')
                            ->required()
                            ->datalist(File::isDirectory(base_path('resources/lang')) ? collect(File::directories(base_path('resources/lang')))->map(fn ($path) => pathinfo($path, PATHINFO_BASENAME)) : []),

                        Toggle::make('is_active')
                            ->label('Active'),

                        Toggle::make('is_default')
                            ->label('Default site')
                            ->helperText('The original site of its group — content is authored there and translated out of it. One default per group.'),
                    ]),

                Section::make('URL')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('domain')
                            ->label('Specific domain'),

                        TextInput::make('prefix')
                            ->label('Prefix'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('label')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('group.name')
                    ->label('Group')
                    ->sortable(),

                TextColumn::make('domain')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('prefix'),

                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->searchable()
                    ->sortable(),

                ToggleColumn::make('is_default')
                    ->label('Default')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->headerActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSites::route('/'),
            'create' => CreateSite::route('/create'),
            'edit' => EditSite::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'label'];
    }
}
