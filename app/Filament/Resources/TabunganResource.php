<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TabunganResource\Pages;
use App\Filament\Resources\TabunganResource\RelationManagers;
use App\Models\Tabungan;
use App\Models\Jamaah;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;

class TabunganResource extends Resource
{
    protected static ?string $model = Tabungan::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Tabungan Umroh';

    protected static ?string $modelLabel = 'Tabungan Umroh';

    protected static ?string $pluralModelLabel = 'Tabungan Umroh';

    protected static ?string $navigationGroup = 'Tabungan Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Jamaah')
                    ->schema([
                        Forms\Components\Select::make('jamaah_id')
                            ->label('Jamaah')
                            ->relationship('jamaah', 'nama_lengkap')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nama_lengkap')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('no_telepon')
                                    ->tel()
                                    ->maxLength(20),
                            ]),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Informasi Rekening')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_rekening')
                            ->label('Nomor Rekening')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('Contoh: TAB-001-2024'),

                        Forms\Components\Select::make('nama_bank')
                            ->label('Nama Bank')
                            ->required()
                            ->options([
                                'BJB' => 'Bank BJB',
                                'BSI' => 'Bank BSI',
                            ]),

                        Forms\Components\TextInput::make('nama_ibu_kandung')
                            ->label('Nama Ibu Kandung')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Untuk verifikasi keamanan'),

                        Forms\Components\DatePicker::make('tanggal_buka_rekening')
                            ->label('Tanggal Buka Rekening')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('dibuka_pada')
                            ->label('Dibuka Pada')
                            ->required()
                            ->default(now()),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'aktif' => 'Aktif',
                                'nonaktif' => 'Non-Aktif',
                                'ditutup' => 'Ditutup',
                            ])
                            ->required()
                            ->default('aktif'),

                        Forms\Components\TextInput::make('saldo_tersedia')
                            ->label('Saldo Tersedia')
                            ->numeric()
                            ->default(0)
                            ->prefix('Rp')
                            ->disabled()
                            ->hidden(true)
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('saldo_terkunci')
                            ->label('Saldo Terkunci')
                            ->numeric()
                            ->default(0)
                            ->prefix('Rp')
                            ->disabled()
                            ->hidden(true)
                            ->dehydrated(false),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('jamaah.nama_lengkap')
                    ->label('Jamaah')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('nama_ibu_kandung')
                    ->label('Ibu Kandung')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama_bank')
                    ->label('Bank')
                    ->color(fn (string $state): string => match ($state) {
                        'BJB' => 'primary',
                        'BSI' => 'success',
                        default => 'primary',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nomor_rekening')
                    ->label('No. Rekening')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('saldo_tersedia')
                    ->label('Saldo Tersedia')
                    ->hidden(true)
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('saldo_terkunci')
                    ->label('Saldo Terkunci')
                    ->hidden(true)
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('total_saldo')
                    ->label('Total Saldo')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->weight(FontWeight::Bold),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'aktif',
                        'warning' => 'nonaktif',
                        'danger' => 'ditutup',
                    ]),

                Tables\Columns\TextColumn::make('tanggal_buka_rekening')
                    ->label('Tgl. Buka')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Non-Aktif',
                        'ditutup' => 'Ditutup',
                    ]),

                Tables\Filters\Filter::make('has_balance')
                    ->label('Memiliki Saldo')
                    ->query(fn (Builder $query): Builder => $query->where(function ($q) {
                        $q->where('saldo_tersedia', '>', 0)
                          ->orWhere('saldo_terkunci', '>', 0);
                    })),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dibuat Dari'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Dibuat Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Rekening')
                    ->schema([
                        Infolists\Components\TextEntry::make('nomor_rekening')
                            ->label('Nomor Rekening'),
                        Infolists\Components\TextEntry::make('jamaah.nama_lengkap')
                            ->label('Jamaah'),
                        Infolists\Components\TextEntry::make('nama_bank')
                            ->label('Bank'),
                        Infolists\Components\TextEntry::make('nama_ibu_kandung')
                            ->label('Nama Ibu Kandung'),
                        Infolists\Components\TextEntry::make('tanggal_buka_rekening')
                            ->label('Tanggal Buka Rekening')
                            ->date(),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'aktif' => 'success',
                                'nonaktif' => 'warning',
                                'ditutup' => 'danger',
                            }),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Informasi Saldo')
                    ->schema([
                        Infolists\Components\TextEntry::make('saldo_tersedia')
                            ->label('Saldo Tersedia')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('saldo_terkunci')
                            ->label('Saldo Terkunci')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('total_saldo')
                            ->label('Total Saldo')
                            ->money('IDR')
                            ->weight(FontWeight::Bold),
                    ])
                    ->columns(3),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SetoranRelationManager::class,
            RelationManagers\AlokasiRelationManager::class,
            RelationManagers\TargetRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTabungans::route('/'),
            'create' => Pages\CreateTabungan::route('/create'),
            'view' => Pages\ViewTabungan::route('/{record}'),
            'edit' => Pages\EditTabungan::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
