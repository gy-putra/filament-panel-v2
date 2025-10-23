<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FlightSegmentResource\Pages;
use App\Filament\Resources\FlightSegmentResource\RelationManagers;
use App\Models\FlightSegment;
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
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\SelectFilter;

class FlightSegmentResource extends Resource
{
    protected static ?string $model = FlightSegment::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationGroup = 'Departure Management';

    protected static ?string $navigationLabel = 'Segmen Penerbangan';

    protected static ?string $modelLabel = 'Segmen Penerbangan';

    protected static ?string $pluralModelLabel = 'Segmen Penerbangan';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Penerbangan')
                    ->schema([
                        Select::make('paket_keberangkatan_id')
                            ->label('Paket Keberangkatan')
                            ->required()
                            ->relationship('paketKeberangkatan', 'nama_paket')
                            ->searchable()
                            ->preload(),
                        
                        Select::make('maskapai_id')
                            ->label('Maskapai')
                            ->required()
                            ->relationship('maskapai', 'nama')
                            ->searchable()
                            ->preload(),
                        
                        TextInput::make('nomor_penerbangan')
                            ->label('Nomor Penerbangan')
                            ->required()
                            ->maxLength(20),
                        
                        TextInput::make('asal')
                            ->label('Bandara Asal')
                            ->required()
                            ->maxLength(100),
                        
                        TextInput::make('tujuan')
                            ->label('Bandara Tujuan')
                            ->required()
                            ->maxLength(100),
                        
                        DateTimePicker::make('waktu_berangkat')
                            ->label('Waktu Keberangkatan')
                            ->required()
                            ->native(false),
                        
                        DateTimePicker::make('waktu_tiba')
                            ->label('Waktu Kedatangan')
                            ->required()
                            ->native(false),
                        
                        Select::make('tipe')
                            ->label('Tipe Segmen')
                            ->required()
                            ->options([
                                'keberangkatan' => 'Keberangkatan',
                                'kepulangan' => 'Kepulangan',
                            ]),
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
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                
                TextColumn::make('maskapai.nama')
                    ->label('Maskapai')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('nomor_penerbangan')
                    ->label('Nomor Penerbangan')
                    ->searchable()
                    ->weight(FontWeight::Bold),
                
                TextColumn::make('asal')
                    ->label('Asal')
                    ->searchable()
                    ->limit(20),
                
                TextColumn::make('tujuan')
                    ->label('Tujuan')
                    ->searchable()
                    ->limit(20),
                
                TextColumn::make('waktu_berangkat')
                    ->label('Keberangkatan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                
                TextColumn::make('waktu_tiba')
                    ->label('Kedatangan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                
                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'keberangkatan' => 'success',
                        'kepulangan' => 'warning',
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
                
                SelectFilter::make('maskapai_id')
                    ->label('Maskapai')
                    ->relationship('maskapai', 'nama')
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('tipe')
                    ->label('Tipe Segmen')
                    ->options([
                        'keberangkatan' => 'Keberangkatan',
                        'kepulangan' => 'Kepulangan',
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
            ->defaultSort('waktu_berangkat', 'asc');
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
            'index' => Pages\ListFlightSegments::route('/'),
            'create' => Pages\CreateFlightSegment::route('/create'),
            'edit' => Pages\EditFlightSegment::route('/{record}/edit'),
        ];
    }
}