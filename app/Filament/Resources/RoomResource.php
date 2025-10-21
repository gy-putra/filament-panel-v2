<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomResource\Pages;
use App\Filament\Resources\RoomResource\RelationManagers;
use App\Models\Room;
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

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationGroup = 'Departure Management';

    protected static ?string $navigationLabel = 'Kamar';

    protected static ?string $modelLabel = 'Kamar';

    protected static ?string $pluralModelLabel = 'Kamar';

    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Kamar')
                    ->schema([
                        Select::make('hotel_booking_id')
                            ->label('Hotel Booking')
                            ->required()
                            ->relationship('hotelBooking', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "#{$record->id} - {$record->paketKeberangkatan->nama_paket} - {$record->hotel->nama}")
                            ->searchable()
                            ->preload(),
                        
                        TextInput::make('nomor_kamar')
                            ->label('Nomor Kamar')
                            ->required()
                            ->maxLength(20),
                        
                        Select::make('tipe_kamar')
                            ->label('Tipe Kamar')
                            ->required()
                            ->options([
                                'single' => 'Single',
                                'double' => 'Double',
                                'triple' => 'Triple',
                                'quad' => 'Quad',
                            ]),
                        
                        TextInput::make('kapasitas')
                            ->label('Kapasitas')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10),
                        
                        Select::make('gender_preference')
                            ->label('Preferensi Gender')
                            ->options([
                                'laki-laki' => 'Laki-laki',
                                'perempuan' => 'Perempuan',
                                'mixed' => 'Campuran',
                            ]),
                        
                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'available' => 'Tersedia',
                                'occupied' => 'Terisi',
                                'maintenance' => 'Maintenance',
                            ])
                            ->default('available'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('hotelBooking.paketKeberangkatan.nama_paket')
                    ->label('Paket')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                
                TextColumn::make('nomor_kamar')
                    ->label('Nomor Kamar')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                
                TextColumn::make('tipe_kamar')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'single' => 'info',
                        'double' => 'success',
                        'triple' => 'warning',
                        'quad' => 'primary',
                        default => 'gray',
                    }),
                
                TextColumn::make('kapasitas')
                    ->label('Kapasitas')
                    ->numeric()
                    ->sortable(),
                
                TextColumn::make('current_occupancy')
                    ->label('Terisi')
                    ->getStateUsing(function (Room $record): int {
                        return $record->roomAssignments()->count();
                    })
                    ->numeric()
                    ->badge()
                    ->color(function (Room $record): string {
                        $occupancy = $record->roomAssignments()->count();
                        $capacity = $record->kapasitas;
                        
                        if ($occupancy >= $capacity) {
                            return 'danger';
                        } elseif ($occupancy > 0) {
                            return 'warning';
                        }
                        
                        return 'success';
                    }),
                
                TextColumn::make('gender_preference')
                    ->label('Gender')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'laki-laki' => 'blue',
                        'perempuan' => 'pink',
                        'mixed' => 'gray',
                        default => 'gray',
                    }),
                
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'occupied' => 'warning',
                        'maintenance' => 'danger',
                        default => 'gray',
                    }),
                
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('hotel_booking_id')
                    ->label('Hotel Booking')
                    ->relationship('hotelBooking.paketKeberangkatan', 'nama_paket')
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('tipe_kamar')
                    ->label('Tipe Kamar')
                    ->options([
                        'single' => 'Single',
                        'double' => 'Double',
                        'triple' => 'Triple',
                        'quad' => 'Quad',
                    ]),
                
                SelectFilter::make('gender_preference')
                    ->label('Preferensi Gender')
                    ->options([
                        'laki-laki' => 'Laki-laki',
                        'perempuan' => 'Perempuan',
                        'mixed' => 'Campuran',
                    ]),
                
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'available' => 'Tersedia',
                        'occupied' => 'Terisi',
                        'maintenance' => 'Maintenance',
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
            ->defaultSort('nomor_kamar', 'asc');
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
            'index' => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/create'),
            'edit' => Pages\EditRoom::route('/{record}/edit'),
        ];
    }
}