<?php

namespace App\Filament\Resources\TabunganResource\RelationManagers;

use App\Models\TabunganSetoran;
use App\Services\SavingsLedgerService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SetoranRelationManager extends RelationManager
{
    protected static string $relationship = 'setoran';

    protected static ?string $title = 'Setoran';

    protected static ?string $modelLabel = 'Setoran';

    protected static ?string $pluralModelLabel = 'Setoran';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('tanggal')
                    ->label('Tanggal Setoran')
                    ->required()
                    ->default(now()),

                Forms\Components\TextInput::make('nominal')
                    ->label('Nominal')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->minValue(1),

                Forms\Components\Select::make('metode')
                    ->label('Metode Pembayaran')
                    ->options([
                        'tunai' => 'Tunai',
                        'transfer' => 'Transfer Bank',
                        'kartu_debit' => 'Kartu Debit',
                        'kartu_kredit' => 'Kartu Kredit',
                        'e_wallet' => 'E-Wallet',
                    ])
                    ->required(),

                Forms\Components\FileUpload::make('bukti_path')
                    ->label('Bukti Pembayaran')
                    ->image()
                    ->directory('setoran-bukti')
                    ->visibility('private')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg'])
                    ->maxSize(2048),

                Forms\Components\Textarea::make('catatan')
                    ->label('Catatan')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tanggal')
            ->columns([
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
                    ->placeholder('Belum diverifikasi'),

                Tables\Columns\TextColumn::make('verified_at')
                    ->label('Tgl. Verifikasi')
                    ->dateTime()
                    ->placeholder('Belum diverifikasi'),

                Tables\Columns\ImageColumn::make('bukti_path')
                    ->label('Bukti')
                    ->circular()
                    ->size(40),
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Setoran')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['status_verifikasi'] = 'pending';
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (TabunganSetoran $record): bool => $record->status_verifikasi === 'pending'),

                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (TabunganSetoran $record): bool => $record->status_verifikasi === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan Persetujuan')
                            ->rows(3),
                    ])
                    ->action(function (TabunganSetoran $record, array $data): void {
                        $service = app(SavingsLedgerService::class);
                        
                        try {
                            $service->approveDeposit($record, $data['catatan'] ?? null);
                            
                            Notification::make()
                                ->title('Setoran berhasil disetujui')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal menyetujui setoran')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (TabunganSetoran $record): bool => $record->status_verifikasi === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('catatan')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (TabunganSetoran $record, array $data): void {
                        $service = app(SavingsLedgerService::class);
                        
                        try {
                            $service->rejectDeposit($record, $data['catatan']);
                            
                            Notification::make()
                                ->title('Setoran berhasil ditolak')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal menolak setoran')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (TabunganSetoran $record): bool => $record->status_verifikasi === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tanggal', 'desc');
    }
}