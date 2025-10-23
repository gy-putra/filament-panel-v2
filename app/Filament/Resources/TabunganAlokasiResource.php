<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TabunganAlokasiResource\Pages;
use App\Filament\Resources\TabunganAlokasiResource\RelationManagers;
use App\Models\TabunganAlokasi;
use App\Services\SavingsLedgerService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TabunganAlokasiResource extends Resource
{
    protected static ?string $model = TabunganAlokasi::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Alokasi Tabungan';

    protected static ?string $modelLabel = 'Alokasi';

    protected static ?string $pluralModelLabel = 'Alokasi Tabungan';

    protected static ?string $navigationGroup = 'Tabungan Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Alokasi')
                    ->schema([
                        Forms\Components\Select::make('tabungan_id')
                            ->label('Rekening Tabungan')
                            ->relationship('tabungan', 'nomor_rekening', function (Builder $query) {
                                return $query->with('jamaah')->where('status', 'aktif');
                            })
                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                $record->nomor_rekening . ' - ' . $record->jamaah->nama_lengkap . ' (Saldo: Rp ' . number_format($record->saldo_tersedia, 0, ',', '.') . ')'
                            )
                            ->searchable(['nomor_rekening'])
                            ->preload()
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('pendaftaran_id')
                            ->label('Pendaftaran')
                            ->relationship('pendaftaran', 'kode_pendaftaran')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->jamaah->nama_lengkap)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('invoice_id')
                            ->label('Invoice')
                            ->relationship('invoice', 'nomor_invoice')
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),

                        Forms\Components\DateTimePicker::make('tanggal')
                            ->label('Tanggal Alokasi')
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

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'posted' => 'Posted',
                                'reversed' => 'Reversed',
                            ])
                            ->default('draft')
                            ->required()
                            ->columnSpan(2),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Catatan')
                    ->schema([
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

                Tables\Columns\TextColumn::make('pendaftaran.kode_pendaftaran')
                    ->label('Kode Pendaftaran')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoice.nomor_invoice')
                    ->label('No. Invoice')
                    ->searchable()
                    ->placeholder('Tidak ada'),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'posted',
                        'danger' => 'reversed',
                    ]),

                Tables\Columns\TextColumn::make('catatan')
                    ->label('Catatan')
                    ->limit(50)
                    ->placeholder('Tidak ada catatan'),

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
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'reversed' => 'Reversed',
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
                    ->visible(fn (TabunganAlokasi $record): bool => $record->status === 'draft'),

                Tables\Actions\Action::make('post')
                    ->label('Post')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (TabunganAlokasi $record): bool => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Post Alokasi')
                    ->modalDescription('Apakah Anda yakin ingin memposting alokasi ini? Setelah diposting, alokasi tidak dapat diubah.')
                    ->modalSubmitActionLabel('Ya, Post Alokasi')
                    ->modalCancelActionLabel('Batal')
                    ->action(function (TabunganAlokasi $record): void {
                        try {
                            $service = app(SavingsLedgerService::class);
                            $service->postAllocation($record);
                            
                            Notification::make()
                                ->title('Alokasi berhasil diposting')
                                ->body('Alokasi dengan nominal Rp ' . number_format($record->nominal, 0, ',', '.') . ' telah berhasil diposting.')
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            \Log::error('Failed to post allocation', [
                                'alokasi_id' => $record->id,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                            
                            Notification::make()
                                ->title('Gagal memposting alokasi')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('reverse')
                    ->label('Reverse')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (TabunganAlokasi $record): bool => $record->status === 'posted')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Reverse Alokasi')
                    ->modalDescription('Apakah Anda yakin ingin mereverse alokasi ini? Dana akan dikembalikan ke saldo tersedia.')
                    ->modalSubmitActionLabel('Ya, Reverse Alokasi')
                    ->modalCancelActionLabel('Batal')
                    ->form([
                        Forms\Components\Textarea::make('catatan')
                            ->label('Alasan Reverse')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (TabunganAlokasi $record, array $data): void {
                        $service = app(SavingsLedgerService::class);
                        
                        try {
                            // Update the record with catatan before reversing
                            $record->update(['catatan' => $data['catatan']]);
                            
                            $service->reverseAllocation($record);
                            
                            Notification::make()
                                ->title('Alokasi berhasil direverse')
                                ->body('Alokasi dengan nominal Rp ' . number_format($record->nominal, 0, ',', '.') . ' telah berhasil direverse.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Log::error('Failed to reverse allocation', [
                                'alokasi_id' => $record->id,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                            
                            Notification::make()
                                ->title('Gagal mereverse alokasi')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (TabunganAlokasi $record): bool => $record->status === 'draft'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('bulk_post')
                        ->label('Post Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Post Alokasi')
                        ->modalDescription('Apakah Anda yakin ingin memposting semua alokasi yang dipilih?')
                        ->action(function ($records): void {
                            $service = app(SavingsLedgerService::class);
                            $successCount = 0;
                            $errorCount = 0;

                            foreach ($records as $record) {
                                if ($record->status === 'draft') {
                                    try {
                                        $service->postAllocation($record);
                                        $successCount++;
                                    } catch (\Exception $e) {
                                        $errorCount++;
                                    }
                                }
                            }

                            if ($successCount > 0) {
                                Notification::make()
                                    ->title("$successCount alokasi berhasil diposting")
                                    ->success()
                                    ->send();
                            }

                            if ($errorCount > 0) {
                                Notification::make()
                                    ->title("$errorCount alokasi gagal diposting")
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
            'index' => Pages\ListTabunganAlokasis::route('/'),
            'create' => Pages\CreateTabunganAlokasi::route('/create'),
            'view' => Pages\ViewTabunganAlokasi::route('/{record}'),
            'edit' => Pages\EditTabunganAlokasi::route('/{record}/edit'),
        ];
    }

    // Sembunyikan dari sidebar
    public static function shouldRegisterNavigation(): bool
    {
        return false; // sembunyikan dari sidebar
    }
}
