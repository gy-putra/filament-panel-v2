<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaskapaiResource\Pages;
use App\Filament\Resources\MaskapaiResource\RelationManagers;
use App\Models\Maskapai;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\SelectFilter;

class MaskapaiResource extends Resource
{
    protected static ?string $model = Maskapai::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationGroup = 'Departure Management';

    protected static ?string $navigationLabel = 'Maskapai';

    protected static ?string $modelLabel = 'Maskapai';

    protected static ?string $pluralModelLabel = 'Maskapai';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Maskapai')
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Maskapai')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(150),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('flightSegments'))
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama Maskapai')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                
                TextColumn::make('flight_segments_count')
                    ->label('Jumlah Penerbangan')
                    ->default(0)
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // No filters needed for simplified maskapai table
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('nama', 'asc');
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
            'index' => Pages\ListMaskapais::route('/'),
            'create' => Pages\CreateMaskapai::route('/create'),
            'edit' => Pages\EditMaskapai::route('/{record}/edit'),
        ];
    }
}