<?php

namespace Zoker\FilamentMultisite\Filament\Resources\SiteGroups;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Zoker\FilamentMultisite\Filament\Resources\SiteGroups\Pages\CreateSiteGroup;
use Zoker\FilamentMultisite\Filament\Resources\SiteGroups\Pages\EditSiteGroup;
use Zoker\FilamentMultisite\Filament\Resources\SiteGroups\Pages\ListSiteGroups;
use Zoker\FilamentMultisite\Models\SiteGroup;

class SiteGroupResource extends Resource
{
    protected static ?string $model = SiteGroup::class;

    protected static ?string $slug = 'site-groups';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),

                TextInput::make('code'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code')
                    ->searchable(),

                TextColumn::make('sites_count')
                    ->counts('sites')
                    ->label('Sites'),
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
            'index' => ListSiteGroups::route('/'),
            'create' => CreateSiteGroup::route('/create'),
            'edit' => EditSiteGroup::route('/{record}/edit'),
        ];
    }
}
