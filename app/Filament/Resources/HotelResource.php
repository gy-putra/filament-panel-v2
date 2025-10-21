<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HotelResource\Pages;
use App\Filament\Resources\HotelResource\RelationManagers;
use App\Models\Hotel;
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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\SelectFilter;

class HotelResource extends Resource
{
    protected static ?string $model = Hotel::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Departure Management';

    protected static ?string $navigationLabel = 'Hotel';

    protected static ?string $modelLabel = 'Hotel';

    protected static ?string $pluralModelLabel = 'Hotel';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Hotel')
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Hotel')
                            ->required()
                            ->maxLength(150),
                        
                        Select::make('kota')
                            ->label('Kota')
                            ->required()
                            ->options([
                                'makkah' => 'Makkah',
                                'madinah' => 'Madinah',
                            ]),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('hotelBookings'))
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama Hotel')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                
                TextColumn::make('kota')
                    ->label('Kota')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'makkah' => 'success',
                        'madinah' => 'info',
                    }),
                
                TextColumn::make('hotel_bookings_count')
                    ->label('Jumlah Booking')
                    ->default(0)
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kota')
                    ->label('Kota')
                    ->options([
                        'makkah' => 'Makkah',
                        'madinah' => 'Madinah',
                    ]),
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
            'index' => Pages\ListHotels::route('/'),
            'create' => Pages\CreateHotel::route('/create'),
            'edit' => Pages\EditHotel::route('/{record}/edit'),
        ];
    }
}