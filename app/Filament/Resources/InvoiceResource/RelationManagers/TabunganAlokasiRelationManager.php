<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use App\Models\TabunganAlokasi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TabunganAlokasiRelationManager extends RelationManager
{
    protected static string $relationship = 'tabunganAlokasi';

    protected static ?string $title = 'Alokasi Tabungan';

    protected static ?string $modelLabel = 'Alokasi';

    protected static ?string $pluralModelLabel = 'Alokasi';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tabungan_id')
                    ->label('Tabungan')
                    ->relationship('tabungan', 'nomor_rekening')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('pendaftaran_id')
                    ->label('Pendaftaran')
                    ->relationship('pendaftaran', 'kode_pendaftaran')
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

                Tables\Columns\TextColumn::make('tabungan.nomor_rekening')
                    ->label('No. Rekening')
                    ->searchable(),

                Tables\Columns\TextColumn::make('pendaftaran.kode_pendaftaran')
                    ->label('Kode Pendaftaran')
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
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'reversed' => 'Reversed',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}