<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PendaftaranResource\Pages;
use App\Filament\Resources\PendaftaranResource\RelationManagers;
use App\Models\Pendaftaran;
use App\Models\PaketKeberangkatan;
use App\Models\Jamaah;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;

use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class PendaftaranResource extends Resource
{
    protected static ?string $model = Pendaftaran::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Departure Management';

    protected static ?string $navigationLabel = 'Pendaftaran';

    protected static ?string $modelLabel = 'Pendaftaran';

    protected static ?string $pluralModelLabel = 'Pendaftaran';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Pendaftaran')
                    ->schema([
                        Select::make('paket_keberangkatan_id')
                            ->label('Paket Keberangkatan')
                            ->required()
                            ->relationship('paketKeberangkatan', 'nama_paket')
                            ->searchable(['nama_paket', 'kode_paket'])
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Clear jamaah selection when paket changes to avoid conflicts
                                $set('jamaah_id', null);
                            }),
                        
                        Select::make('jamaah_id')
                            ->label('Jamaah')
                            ->required()
                            ->relationship('jamaah', 'nama_lengkap')
                            ->searchable(['nama_lengkap', 'no_ktp'])
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->nama_lengkap} - {$record->kota}")
                            ->reactive()
                            ->rules([
                                'required',
                                function (Get $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $paketId = $get('paket_keberangkatan_id');
                                        
                                        if ($paketId && $value) {
                                            // Get the current record ID for edit mode
                                            $recordId = null;
                                            if (request()->route('record')) {
                                                $recordId = request()->route('record');
                                            }
                                            
                                            $query = \App\Models\Pendaftaran::where('paket_keberangkatan_id', $paketId)
                                ->where('jamaah_id', $value)
                                ->whereNull('deleted_at'); // Only check active records
                            
                            // Exclude current record when editing
                            if ($recordId) {
                                $query->where('id', '!=', $recordId);
                            }
                            
                            if ($query->exists()) {
                                $fail('Jamaah ini sudah terdaftar pada paket keberangkatan yang dipilih.');
                            }
                                        }
                                    };
                                },
                            ]),
                        
                        DatePicker::make('tgl_daftar')
                            ->label('Tanggal Daftar')
                            ->required()
                            ->default(now())
                            ->native(false),
                    ])
                    ->columns(1),
                
                Section::make('Status & Catatan')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending'),
                        
                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(3)
                            ->maxLength(500),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('paketKeberangkatan.nama_paket')
                    ->label('Paket Keberangkatan')
                    ->searchable(['paket_keberangkatan.nama_paket'])
                    ->sortable()
                    ->weight(FontWeight::Bold),
                
                TextColumn::make('jamaah.nama_lengkap')
                    ->label('Nama Jamaah')
                    ->searchable(['jamaah.nama_lengkap'])
                    ->sortable(),
                
                TextColumn::make('jamaah.no_ktp')
                    ->label('No. KTP')
                    ->searchable(['jamaah.no_ktp']),
                
                TextColumn::make('tgl_daftar')
                    ->label('Tanggal Daftar')
                    ->date('d M Y')
                    ->sortable(),
                
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                
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
                
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                    ]),
                
                Filter::make('tgl_daftar')
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
                                fn (EloquentBuilder $query, $date): EloquentBuilder => $query->whereDate('tgl_daftar', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (EloquentBuilder $query, $date): EloquentBuilder => $query->whereDate('tgl_daftar', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->action(function ($record) {
                        $record->forceDelete();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->forceDelete();
                            }
                        }),
                ]),
            ])
            ->defaultSort('tgl_daftar', 'desc');
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
            'index' => Pages\ListPendaftarans::route('/'),
            'create' => Pages\CreatePendaftaran::route('/create'),
            'edit' => Pages\EditPendaftaran::route('/{record}/edit'),
        ];
    }
}