<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItineraryResource\Pages;
use App\Filament\Resources\ItineraryResource\RelationManagers;
use App\Models\Itinerary;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\SelectFilter;

class ItineraryResource extends Resource
{
    protected static ?string $model = Itinerary::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Departure Management';

    protected static ?string $navigationLabel = 'Itinerary';

    protected static ?string $modelLabel = 'Itinerary';

    protected static ?string $pluralModelLabel = 'Itinerary';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Itinerary')
                    ->schema([
                        Select::make('paket_keberangkatan_id')
                            ->label('Paket Keberangkatan')
                            ->required()
                            ->relationship('paketKeberangkatan', 'nama_paket')
                            ->searchable()
                            ->preload(),
                        
                        TextInput::make('hari_ke')
                            ->label('Hari Ke')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(365),
                        
                        DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->required()
                            ->native(false),
                        
                        TextInput::make('judul')
                            ->label('Judul')
                            ->required()
                            ->maxLength(200),
                        
                        Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->rows(3),
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
                
                TextColumn::make('hari_ke')
                    ->label('Hari Ke')
                    ->sortable(),
                
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                
                TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->limit(40),
                
                TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                
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
            ->defaultSort('hari_ke', 'asc');
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
            'index' => Pages\ListItineraries::route('/'),
            'create' => Pages\CreateItinerary::route('/create'),
            'edit' => Pages\EditItinerary::route('/{record}/edit'),
        ];
    }
}