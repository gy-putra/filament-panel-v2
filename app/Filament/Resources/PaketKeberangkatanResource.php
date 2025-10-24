<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaketKeberangkatanResource\Pages;
use App\Filament\Resources\PaketKeberangkatanResource\RelationManagers;
use App\Models\PaketKeberangkatan;
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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Filament\Tables\Actions\ViewAction;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\RepeatableEntry;

class PaketKeberangkatanResource extends Resource
{
    protected static ?string $model = PaketKeberangkatan::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Departure Management';

    protected static ?string $navigationLabel = 'Paket Keberangkatan';

    protected static ?string $modelLabel = 'Paket Keberangkatan';

    protected static ?string $pluralModelLabel = 'Paket Keberangkatan';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Paket')
                    ->schema([
                        TextInput::make('kode_paket')
                            ->label('Kode Paket')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Contoh: UMRH-2025001'),
                        
                        TextInput::make('nama_paket')
                            ->label('Nama Paket')
                            ->required()
                            ->maxLength(255),
                        
                        Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->columns(2),
                
                Section::make('Jadwal & Kuota')
                    ->schema([
                        DatePicker::make('tgl_keberangkatan')
                            ->label('Tanggal Keberangkatan')
                            ->required()
                            ->native(false),
                        
                        DatePicker::make('tgl_kepulangan')
                            ->label('Tanggal Kepulangan')
                            ->required()
                            ->native(false)
                            ->after('tgl_keberangkatan'),
                        
                        TextInput::make('kuota_total')
                            ->label('Kuota Total')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(0),
                    ])
                    ->columns(3),
                
                Section::make('Harga & Status')
                    ->schema([
                        TextInput::make('harga_paket')
                            ->label('Harga Paket')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0),
                        
                        TextInput::make('harga_quad')
                            ->label('Harga Quad')
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0),
                        
                        TextInput::make('harga_triple')
                            ->label('Harga Triple')
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0),
                        
                        TextInput::make('harga_double')
                            ->label('Harga Double')
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0),
                        
                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'draft' => 'Draft',
                                'open' => 'Open',
                                'published' => 'Published',
                                'closed' => 'Closed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('pendaftarans'))
            ->columns([
                TextColumn::make('kode_paket')
                    ->label('Kode Paket')
                    ->searchable()
                    ->sortable()
                    ->alignCenter()
                    ->weight(FontWeight::Bold),
                
                TextColumn::make('nama_paket')
                    ->label('Nama Paket')
                    ->searchable(['nama_paket'])
                    ->sortable()
                    ->alignCenter()
                    ->weight(FontWeight::Bold),
                
                TextColumn::make('kuota_total')
                    ->label('Seat')
                    ->badge()
                    ->color(fn ($record) => 
                        ($record->kuota_total - $record->pendaftarans_count) <= 0
                            ? 'danger'
                            : (($record->kuota_total - $record->pendaftarans_count) <= 3 ? 'warning' : 'success')
                    )
                    ->formatStateUsing(function ($record) {
                        $sisa = $record->kuota_total - $record->pendaftarans_count;
                        if ($sisa <= 0) {
                            return '🚫 FULL - NO SEATS AVAILABLE';
                        } elseif ($sisa <= 3) {
                            return "⚠️ {$sisa} SEATS LEFT - HURRY!";
                        } else {
                            return "✅ {$sisa} SEATS AVAILABLE";
                        }
                    })
                    ->alignCenter()
                    ->sortable(),

                
                TextColumn::make('harga_quad')
                    ->label('Quad')
                    ->money('IDR')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: false),
                
                TextColumn::make('harga_triple')
                    ->label('Triple')
                    ->money('IDR')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: false),
                
                TextColumn::make('harga_double')
                    ->label('Double')
                    ->money('IDR')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: false),
                
                TextColumn::make('harga_paket')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable()
                    ->alignCenter(),
                
                TextColumn::make('tgl_keberangkatan')
                    ->label('Tanggal Keberangkatan')
                    ->date('d M Y')
                    ->sortable()
                    ->alignCenter(),
                
                TextColumn::make('tgl_kepulangan')
                    ->label('Tanggal Kepulangan')
                    ->date('d M Y')
                    ->sortable()
                    ->alignCenter(),    
                
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'open' => 'info',
                        'published' => 'success',
                        'closed' => 'danger',
                        'cancelled' => 'warning',
                        default => 'gray',
                    })
                    ->alignCenter()
                    ->sortable(),
                
                TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->alignCenter()
                    ->limit(50),
                
                TextColumn::make('pendaftarans_count')
                    ->label('Jumlah Pendaftar')
                    ->default(0)
                    ->sortable()
                    ->alignCenter(),
                
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'open' => 'Open',
                        'published' => 'Published',
                        'closed' => 'Closed',
                        'cancelled' => 'Cancelled',
                    ]),
                
                Filter::make('tgl_keberangkatan')
                    ->form([
                        DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (EloquentBuilder $query, array $data): EloquentBuilder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (EloquentBuilder $query, $date): EloquentBuilder => $query->whereDate('tgl_keberangkatan', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (EloquentBuilder $query, $date): EloquentBuilder => $query->whereDate('tgl_keberangkatan', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tgl_keberangkatan', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Informasi Paket')
                    ->schema([
                        TextEntry::make('kode_paket')
                            ->label('Kode Paket')
                            ->weight('bold'),
                        
                        TextEntry::make('nama_paket')
                            ->label('Nama Paket')
                            ->weight('bold'),
                        
                        TextEntry::make('deskripsi')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                InfolistSection::make('Jadwal & Kuota')
                    ->schema([
                        TextEntry::make('tgl_keberangkatan')
                            ->label('Tanggal Keberangkatan')
                            ->date('d M Y'),
                        
                        TextEntry::make('tgl_kepulangan')
                            ->label('Tanggal Kepulangan')
                            ->date('d M Y'),
                        
                        TextEntry::make('kuota_total')
                            ->label('Seat Total')
                            ->default(0),
                        
                        TextEntry::make('availability_status')
                            ->label('Status Ketersediaan')
                            ->state(function ($record) {
                                $sisa = $record->kuota_total - $record->pendaftarans_count;
                                if ($sisa <= 0) {
                                    return '🚫 FULL - NO SEATS AVAILABLE';
                                } elseif ($sisa <= 3) {
                                    return "⚠️ {$sisa} SEATS LEFT - HURRY!";
                                } else {
                                    return "✅ {$sisa} SEATS AVAILABLE";
                                }
                            })
                            ->badge()
                            ->color(function ($record) {
                                $sisa = $record->kuota_total - ($record->pendaftarans_count ?? 0);
                                if ($sisa <= 0) return 'danger';
                                if ($sisa <= 3) return 'warning';
                                return 'success';
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                
                InfolistSection::make('Harga & Status')
                    ->schema([
                        TextEntry::make('harga_paket')
                            ->label('Harga Paket')
                            ->money('IDR'),
                        
                        TextEntry::make('harga_quad')
                            ->label('Harga Quad')
                            ->money('IDR')
                            ->placeholder('Tidak tersedia'),
                        
                        TextEntry::make('harga_triple')
                            ->label('Harga Triple')
                            ->money('IDR')
                            ->placeholder('Tidak tersedia'),
                        
                        TextEntry::make('harga_double')
                            ->label('Harga Double')
                            ->money('IDR')
                            ->placeholder('Tidak tersedia'),
                        
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'open' => 'info',
                                'published' => 'success',
                                'closed' => 'danger',
                                'cancelled' => 'warning',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),
                
                InfolistSection::make('Itinerary')
                    ->schema([
                        RepeatableEntry::make('itinerary')
                            ->label('')
                            ->schema([
                                TextEntry::make('hari_ke')
                                    ->label('Hari')
                                    ->prefix('Hari ke-'),
                                
                                TextEntry::make('tanggal')
                                    ->label('Tanggal')
                                    ->date('d M Y'),
                                
                                TextEntry::make('judul')
                                    ->label('Judul')
                                    ->weight('bold'),
                                
                                TextEntry::make('deskripsi')
                                    ->label('Deskripsi')
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->contained(false),
                    ])
                    ->collapsible(),
                
                InfolistSection::make('Hotel')
                    ->schema([
                        RepeatableEntry::make('hotelBookings')
                            ->label('')
                            ->schema([
                                TextEntry::make('hotel.nama')
                                    ->label('Nama Hotel')
                                    ->weight('bold'),
                                
                                TextEntry::make('hotel.kota')
                                    ->label('Kota'),
                                
                                TextEntry::make('check_in')
                                    ->label('Check In')
                                    ->date('d M Y'),
                                
                                TextEntry::make('check_out')
                                    ->label('Check Out')
                                    ->date('d M Y'),
                            ])
                            ->columns(2)
                            ->contained(false),
                    ])
                    ->collapsible(),
                
                InfolistSection::make('Penerbangan')
                    ->schema([
                        RepeatableEntry::make('flightSegments')
                            ->label('')
                            ->schema([
                                TextEntry::make('maskapai.nama')
                                    ->label('Maskapai')
                                    ->weight('bold'),
                                
                                TextEntry::make('flight_number')
                                    ->label('Nomor Penerbangan'),
                                
                                TextEntry::make('departure_airport')
                                    ->label('Bandara Keberangkatan'),
                                
                                TextEntry::make('arrival_airport')
                                    ->label('Bandara Tujuan'),
                                
                                TextEntry::make('departure_time')
                                    ->label('Waktu Keberangkatan')
                                    ->dateTime('d M Y H:i'),
                                
                                TextEntry::make('arrival_time')
                                    ->label('Waktu Tiba')
                                    ->dateTime('d M Y H:i'),
                            ])
                            ->columns(2)
                            ->contained(false),
                    ])
                    ->collapsible(),
            ]);
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
            'index' => Pages\ListPaketKeberangkatans::route('/'),
            'create' => Pages\CreatePaketKeberangkatan::route('/create'),
            'view' => Pages\ViewPaketKeberangkatan::route('/{record}'),
            'edit' => Pages\EditPaketKeberangkatan::route('/{record}/edit'),
        ];
    }
}