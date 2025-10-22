<?php

namespace App\Filament\Resources\TabunganResource\RelationManagers;

use App\Models\TabunganAlokasi;
use App\Services\SavingsLedgerService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AlokasiRelationManager extends RelationManager
{
    protected static string $relationship = 'alokasi';

    protected static ?string $title = 'Alokasi';

    protected static ?string $modelLabel = 'Alokasi';

    protected static ?string $pluralModelLabel = 'Alokasi';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('pendaftaran_id')
                    ->label('Pendaftaran')
                    ->relationship('pendaftaran', 'kode_pendaftaran')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('invoice_id')
                    ->label('Invoice')
                    ->relationship('invoice', 'nomor_invoice')
                    ->searchable()
                    ->preload(),

                Forms\Components\DateTimePicker::make('tanggal')
                    ->label('Tanggal Alokasi')
                    ->required()
                    ->default(now()),

                Forms\Components\TextInput::make('nominal')
                    ->label('Nominal')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->minValue(1),

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

                Tables\Columns\TextColumn::make('pendaftaran.kode_pendaftaran')
                    ->label('Kode Pendaftaran')
                    ->searchable(),

                Tables\Columns\TextColumn::make('invoice.nomor_invoice')
                    ->label('No. Invoice')
                    ->searchable()
                    ->placeholder('Tidak ada'),

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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Alokasi')
                    ->using(function (array $data, string $model): TabunganAlokasi {
                        $service = app(SavingsLedgerService::class);
                        $tabungan = $this->getOwnerRecord();
                        
                        try {
                            return $service->createAllocation(
                                $tabungan,
                                $data['pendaftaran_id'],
                                $data['nominal'],
                                $data['tanggal'],
                                $data['catatan'] ?? null,
                                $data['invoice_id'] ?? null
                            );
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal membuat alokasi')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                            
                            throw $e;
                        }
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
                    ->action(function (TabunganAlokasi $record): void {
                        $service = app(SavingsLedgerService::class);
                        
                        try {
                            $service->postAllocation($record);
                            
                            Notification::make()
                                ->title('Alokasi berhasil diposting')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
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
                    ->form([
                        Forms\Components\Textarea::make('catatan')
                            ->label('Alasan Reverse')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (TabunganAlokasi $record, array $data): void {
                        $service = app(SavingsLedgerService::class);
                        
                        try {
                            $service->reverseAllocation($record, $data['catatan']);
                            
                            Notification::make()
                                ->title('Alokasi berhasil direverse')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
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
                ]),
            ])
            ->defaultSort('tanggal', 'desc');
    }
}