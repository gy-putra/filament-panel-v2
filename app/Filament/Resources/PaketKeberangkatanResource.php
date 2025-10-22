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
                    ->weight(FontWeight::Bold),
                
                TextColumn::make('nama_paket')
                    ->label('Nama Paket')
                    ->searchable(['nama_paket'])
                    ->sortable()
                    ->weight(FontWeight::Bold),
                
                TextColumn::make('tgl_keberangkatan')
                    ->label('Tanggal Keberangkatan')
                    ->date('d M Y')
                    ->sortable(),
                
                TextColumn::make('tgl_kepulangan')
                    ->label('Tanggal Kepulangan')
                    ->date('d M Y')
                    ->sortable(),
                
                TextColumn::make('kuota_total')
                    ->label('Kuota')
                    ->sortable(),
                
                TextColumn::make('harga_paket')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable(),
                
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
                    ->sortable(),
                
                TextColumn::make('pendaftarans_count')
                    ->label('Jumlah Pendaftar')
                    ->default(0)
                    ->sortable(),
                
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
            'edit' => Pages\EditPaketKeberangkatan::route('/{record}/edit'),
        ];
    }
}