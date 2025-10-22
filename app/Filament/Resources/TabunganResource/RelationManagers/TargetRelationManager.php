<?php

namespace App\Filament\Resources\TabunganResource\RelationManagers;

use App\Models\TabunganTarget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TargetRelationManager extends RelationManager
{
    protected static string $relationship = 'target';

    protected static ?string $title = 'Target Tabungan';

    protected static ?string $modelLabel = 'Target';

    protected static ?string $pluralModelLabel = 'Target';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('target_nominal')
                    ->label('Target Nominal')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->minValue(1),

                Forms\Components\DatePicker::make('deadline')
                    ->label('Target Deadline')
                    ->required()
                    ->minDate(now()->addDay()),

                Forms\Components\Select::make('paket_target_id')
                    ->label('Paket Target')
                    ->relationship('paketTarget', 'nama_paket')
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih paket target (opsional)'),

                Forms\Components\TextInput::make('rencana_bulanan')
                    ->label('Rencana Setoran Bulanan')
                    ->numeric()
                    ->prefix('Rp')
                    ->minValue(0)
                    ->helperText('Kosongkan jika tidak ada rencana bulanan tetap'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('target_nominal')
            ->columns([
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
                    ->color(fn ($state): string => match (true) {
                        $state >= 100 => 'success',
                        $state >= 75 => 'info',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Sisa Target')
                    ->money('IDR')
                    ->color(fn ($state): string => $state <= 0 ? 'success' : 'warning'),

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
                            ->label('Progress Range')
                            ->options([
                                'completed' => 'Tercapai (100%)',
                                'high' => 'Tinggi (75-99%)',
                                'medium' => 'Sedang (50-74%)',
                                'low' => 'Rendah (<50%)',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['progress_range']) {
                            return $query;
                        }

                        return $query->whereHas('tabungan', function (Builder $query) use ($data) {
                            $query->selectRaw('
                                CASE 
                                    WHEN target_nominal > 0 THEN (saldo_tersedia / target_nominal) * 100
                                    ELSE 0
                                END as progress_percentage
                            ');

                            match ($data['progress_range']) {
                                'completed' => $query->havingRaw('progress_percentage >= 100'),
                                'high' => $query->havingRaw('progress_percentage >= 75 AND progress_percentage < 100'),
                                'medium' => $query->havingRaw('progress_percentage >= 50 AND progress_percentage < 75'),
                                'low' => $query->havingRaw('progress_percentage < 50'),
                                default => $query,
                            };
                        });
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Target')
                    ->mutateFormDataUsing(function (array $data, RelationManager $livewire): array {
                        // Check if target already exists for this tabungan
                        $existingTarget = $livewire->getOwnerRecord()->target()->first();
                        
                        if ($existingTarget) {
                            throw new \Exception('Tabungan ini sudah memiliki target. Silakan edit target yang ada.');
                        }
                        
                        return $data;
                    }),
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
            ->emptyStateHeading('Belum ada target tabungan')
            ->emptyStateDescription('Tambahkan target tabungan untuk membantu jamaah mencapai tujuan finansial mereka.')
            ->emptyStateIcon('heroicon-o-target');
    }
}