<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HotelBookingResource\Pages;
use App\Filament\Resources\HotelBookingResource\RelationManagers;
use App\Models\HotelBooking;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker as FilterDatePicker;

class HotelBookingResource extends Resource
{
    protected static ?string $model = HotelBooking::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Departure Management';

    protected static ?string $navigationLabel = 'Booking Hotel';

    protected static ?string $modelLabel = 'Booking Hotel';

    protected static ?string $pluralModelLabel = 'Booking Hotel';

    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Booking Hotel')
                    ->schema([
                        Select::make('paket_keberangkatan_id')
                            ->label('Paket Keberangkatan')
                            ->required()
                            ->relationship('paketKeberangkatan', 'nama_paket')
                            ->searchable(['nama_paket', 'kode_paket'])
                            ->preload(),
                        
                        Select::make('hotel_id')
                            ->label('Hotel')
                            ->required()
                            ->relationship('hotel', 'nama')
                            ->searchable(['nama', 'kota'])
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->nama} - {$record->kota}"),
                        
                        DatePicker::make('check_in')
                            ->label('Tanggal Check-in')
                            ->required()
                            ->native(false),
                        
                        DatePicker::make('check_out')
                            ->label('Tanggal Check-out')
                            ->required()
                            ->native(false)
                            ->after('check_in'),
                        
                        TextInput::make('jumlah_malam')
                            ->label('Jumlah Malam')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        
                        TextInput::make('jumlah_kamar')
                            ->label('Jumlah Kamar')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        
                        TextInput::make('nomor_booking')
                            ->label('Nomor Booking')
                            ->maxLength(100),
                        
                        Select::make('status_booking')
                            ->label('Status Booking')
                            ->required()
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('paketKeberangkatan.nama_paket')
                    ->label('Paket')
                    ->searchable(['paket_keberangkatan.nama_paket'])
                    ->sortable()
                    ->limit(30),
                
                TextColumn::make('hotel.nama')
                    ->label('Hotel')
                    ->searchable(['hotel.nama'])
                    ->sortable()
                    ->weight(FontWeight::Bold),
                
                TextColumn::make('hotel.kota')
                    ->label('Kota')
                    ->searchable(['hotel.kota'])
                    ->sortable(),
                
                TextColumn::make('check_in')
                    ->label('Check-in')
                    ->date('d M Y')
                    ->sortable(),
                
                TextColumn::make('check_out')
                    ->label('Check-out')
                    ->date('d M Y')
                    ->sortable(),
                
                TextColumn::make('jumlah_malam')
                    ->label('Jumlah Malam')
                    ->numeric()
                    ->sortable(),
                
                TextColumn::make('jumlah_kamar')
                    ->label('Jumlah Kamar')
                    ->numeric()
                    ->sortable(),
                
                TextColumn::make('nomor_booking')
                    ->label('Nomor Booking')
                    ->searchable(['nomor_booking'])
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('status_booking')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('paket_keberangkatan_id')
                    ->label('Paket Keberangkatan')
                    ->relationship('paketKeberangkatan', 'nama_paket')
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('hotel_id')
                    ->label('Hotel')
                    ->relationship('hotel', 'nama')
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('status_booking')
                    ->label('Status Booking')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                    ]),
                
                Filter::make('check_in')
                    ->form([
                        FilterDatePicker::make('checkin_from')
                            ->label('Check-in Dari'),
                        FilterDatePicker::make('checkin_until')
                            ->label('Check-in Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['checkin_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('check_in', '>=', $date),
                            )
                            ->when(
                                $data['checkin_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('check_in', '<=', $date),
                            );
                    }),
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
            ->defaultSort('check_in', 'asc');
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
            'index' => Pages\ListHotelBookings::route('/'),
            'create' => Pages\CreateHotelBooking::route('/create'),
            'edit' => Pages\EditHotelBooking::route('/{record}/edit'),
        ];
    }
}