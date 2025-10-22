<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TabunganSetoranResource\Pages;
use App\Filament\Resources\TabunganSetoranResource\RelationManagers;
use App\Models\TabunganSetoran;
use App\Services\SavingsLedgerService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TabunganSetoranResource extends Resource
{
    protected static ?string $model = TabunganSetoran::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Setoran Tabungan';

    protected static ?string $modelLabel = 'Setoran';

    protected static ?string $pluralModelLabel = 'Setoran Tabungan';

    protected static ?string $navigationGroup = 'Tabungan Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Setoran')
                    ->schema([
                        Forms\Components\Select::make('tabungan_id')
                            ->label('Rekening Tabungan')
                            ->relationship('tabungan', 'nomor_rekening', function (Builder $query) {
                                return $query->with('jamaah')->where('status', 'aktif');
                            })
                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                $record->nomor_rekening . ' - ' . $record->jamaah->nama_lengkap
                            )
                            ->searchable(['nomor_rekening'])
                            ->preload()
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('tanggal')
                            ->label('Tanggal Setoran')
                            ->required()
                            ->default(now())
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('nominal')
                            ->label('Nominal')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(1)
                            ->columnSpan(1),

                        Forms\Components\Select::make('metode')
                            ->label('Metode Pembayaran')
                            ->options([
                                'tunai' => 'Tunai',
                                'transfer' => 'Transfer Bank',
                                'kartu_debit' => 'Kartu Debit',
                                'kartu_kredit' => 'Kartu Kredit',
                                'e_wallet' => 'E-Wallet',
                            ])
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('status_verifikasi')
                            ->label('Status Verifikasi')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->default('pending')
                            ->required()
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Bukti & Catatan')
                    ->schema([
                        Forms\Components\FileUpload::make('bukti_path')
                            ->label('Bukti Pembayaran')
                            ->image()
                            ->directory('setoran-bukti')
                            ->visibility('private')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg'])
                            ->maxSize(2048)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tabungan.nomor_rekening')
                    ->label('No. Rekening')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tabungan.jamaah.nama_lengkap')
                    ->label('Nama Jamaah')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('metode')
                    ->label('Metode')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'tunai' => 'success',
                        'transfer' => 'info',
                        'kartu_debit' => 'warning',
                        'kartu_kredit' => 'danger',
                        'e_wallet' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\BadgeColumn::make('status_verifikasi')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),

                Tables\Columns\TextColumn::make('verifiedBy.name')
                    ->label('Diverifikasi Oleh')
                    ->placeholder('Belum diverifikasi')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('verified_at')
                    ->label('Tgl. Verifikasi')
                    ->dateTime()
                    ->placeholder('Belum diverifikasi')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\ImageColumn::make('bukti_path')
                    ->label('Bukti')
                    ->circular()
                    ->size(40),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_verifikasi')
                    ->label('Status Verifikasi')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

                Tables\Filters\SelectFilter::make('metode')
                    ->label('Metode Pembayaran')
                    ->options([
                        'tunai' => 'Tunai',
                        'transfer' => 'Transfer Bank',
                        'kartu_debit' => 'Kartu Debit',
                        'kartu_kredit' => 'Kartu Kredit',
                        'e_wallet' => 'E-Wallet',
                    ]),

                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('nominal')
                    ->form([
                        Forms\Components\TextInput::make('min_nominal')
                            ->label('Nominal Minimum')
                            ->numeric()
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('max_nominal')
                            ->label('Nominal Maksimum')
                            ->numeric()
                            ->prefix('Rp'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_nominal'],
                                fn (Builder $query, $amount): Builder => $query->where('nominal', '>=', $amount),
                            )
                            ->when(
                                $data['max_nominal'],
                                fn (Builder $query, $amount): Builder => $query->where('nominal', '<=', $amount),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->tooltip('Edit data setoran'),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->tooltip('Hapus data setoran')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Setoran')
                    ->modalDescription('Apakah Anda yakin ingin menghapus setoran ini? Tindakan ini tidak dapat dibatalkan.')
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->modalCancelActionLabel('Batal'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Setoran Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus semua setoran yang dipilih? Tindakan ini tidak dapat dibatalkan.')
                        ->modalSubmitActionLabel('Ya, Hapus Semua')
                        ->modalCancelActionLabel('Batal')
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('Setujui Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\Textarea::make('catatan')
                                ->label('Catatan Persetujuan')
                                ->rows(3),
                        ])
                        ->action(function (array $data, $records): void {
                            $service = app(SavingsLedgerService::class);
                            $successCount = 0;
                            $errorCount = 0;

                            foreach ($records as $record) {
                                if ($record->status_verifikasi === 'pending') {
                                    try {
                                        $service->approveDeposit($record, $data['catatan'] ?? null);
                                        $successCount++;
                                    } catch (\Exception $e) {
                                        $errorCount++;
                                    }
                                }
                            }

                            if ($successCount > 0) {
                                Notification::make()
                                    ->title("$successCount setoran berhasil disetujui")
                                    ->success()
                                    ->send();
                            }

                            if ($errorCount > 0) {
                                Notification::make()
                                    ->title("$errorCount setoran gagal disetujui")
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('tanggal', 'desc');
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
            'index' => Pages\ListTabunganSetorans::route('/'),
            'create' => Pages\CreateTabunganSetoran::route('/create'),
            'view' => Pages\ViewTabunganSetoran::route('/{record}'),
            'edit' => Pages\EditTabunganSetoran::route('/{record}/edit'),
        ];
    }
}
