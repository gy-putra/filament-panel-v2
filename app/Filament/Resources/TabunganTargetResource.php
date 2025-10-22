<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TabunganTargetResource\Pages;
use App\Filament\Resources\TabunganTargetResource\RelationManagers;
use App\Models\TabunganTarget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TabunganTargetResource extends Resource
{
    protected static ?string $model = TabunganTarget::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationLabel = 'Target Tabungan';

    protected static ?string $modelLabel = 'Target';

    protected static ?string $pluralModelLabel = 'Target Tabungan';

    protected static ?string $navigationGroup = 'Tabungan Management';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Target')
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

                        Forms\Components\TextInput::make('target_nominal')
                            ->label('Target Nominal')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(1)
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('deadline')
                            ->label('Target Deadline')
                            ->required()
                            ->minDate(now()->addDay())
                            ->columnSpan(1),

                        Forms\Components\Select::make('paket_target_id')
                            ->label('Paket Target')
                            ->relationship('paketTarget', 'nama_paket')
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('rencana_bulanan')
                            ->label('Rencana Bulanan')
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0)
                            ->helperText('Jumlah yang direncanakan untuk ditabung setiap bulan')
                            ->columnSpan(1),
                    ])
                    ->columns(2),
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

                Tables\Columns\TextColumn::make('target_nominal')
                    ->label('Target Nominal')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('deadline')
                    ->label('Deadline')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('paketTarget.nama_paket')
                    ->label('Paket Target')
                    ->placeholder('Tidak ada paket'),

                Tables\Columns\TextColumn::make('rencana_bulanan')
                    ->label('Rencana Bulanan')
                    ->money('IDR')
                    ->placeholder('Tidak ada rencana'),

                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 100 => 'success',
                        $state >= 75 => 'info',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Sisa Target')
                    ->money('IDR')
                    ->color(fn ($state) => $state <= 0 ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('deadline')
                    ->form([
                        Forms\Components\DatePicker::make('dari_deadline')
                            ->label('Dari Deadline'),
                        Forms\Components\DatePicker::make('sampai_deadline')
                            ->label('Sampai Deadline'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_deadline'],
                                fn (Builder $query, $date): Builder => $query->whereDate('deadline', '>=', $date),
                            )
                            ->when(
                                $data['sampai_deadline'],
                                fn (Builder $query, $date): Builder => $query->whereDate('deadline', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('progress')
                    ->form([
                        Forms\Components\Select::make('progress_range')
                            ->label('Range Progress')
                            ->options([
                                '0-25' => '0% - 25%',
                                '26-50' => '26% - 50%',
                                '51-75' => '51% - 75%',
                                '76-99' => '76% - 99%',
                                '100+' => '100% atau lebih',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['progress_range']) {
                            return $query;
                        }

                        return $query->whereHas('tabungan', function (Builder $query) use ($data) {
                            $range = $data['progress_range'];
                            
                            switch ($range) {
                                case '0-25':
                                    $query->whereRaw('(saldo_tersedia / target_nominal * 100) BETWEEN 0 AND 25');
                                    break;
                                case '26-50':
                                    $query->whereRaw('(saldo_tersedia / target_nominal * 100) BETWEEN 26 AND 50');
                                    break;
                                case '51-75':
                                    $query->whereRaw('(saldo_tersedia / target_nominal * 100) BETWEEN 51 AND 75');
                                    break;
                                case '76-99':
                                    $query->whereRaw('(saldo_tersedia / target_nominal * 100) BETWEEN 76 AND 99');
                                    break;
                                case '100+':
                                    $query->whereRaw('(saldo_tersedia / target_nominal * 100) >= 100');
                                    break;
                            }
                        });
                    }),

                Tables\Filters\SelectFilter::make('paket_target_id')
                    ->label('Paket Target')
                    ->relationship('paketTarget', 'nama_paket'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('deadline', 'asc');
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
            'index' => Pages\ListTabunganTargets::route('/'),
            'create' => Pages\CreateTabunganTarget::route('/create'),
            'view' => Pages\ViewTabunganTarget::route('/{record}'),
            'edit' => Pages\EditTabunganTarget::route('/{record}/edit'),
        ];
    }
}
