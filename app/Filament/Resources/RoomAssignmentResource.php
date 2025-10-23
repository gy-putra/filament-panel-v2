<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomAssignmentResource\Pages;
use App\Filament\Resources\RoomAssignmentResource\RelationManagers;
use App\Models\RoomAssignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;

class RoomAssignmentResource extends Resource
{
    protected static ?string $model = RoomAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Departure Management';

    protected static ?string $navigationLabel = 'Penempatan Kamar';

    protected static ?string $modelLabel = 'Penempatan Kamar';

    protected static ?string $pluralModelLabel = 'Penempatan Kamar';

    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Penempatan Kamar')
                    ->schema([
                        Select::make('pendaftaran_id')
                            ->label('Pendaftaran')
                            ->required()
                            ->relationship('pendaftaran', 'kode_pendaftaran')
                            ->searchable(['kode_pendaftaran'])
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->jamaah->nama_lengkap} - {$record->jamaah->kota}"),
                        
                        Select::make('room_id')
                            ->label('Kamar')
                            ->required()
                            ->relationship('room', 'nomor_kamar')
                            ->searchable(['nomor_kamar', 'tipe_kamar'])
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->nomor_kamar} ({$record->tipe_kamar} - {$record->kapasitas} orang)"),
                        
                        DateTimePicker::make('assigned_at')
                            ->label('Waktu Penempatan')
                            ->default(now())
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pendaftaran.kode_pendaftaran')
                    ->label('Kode Pendaftaran')
                    ->searchable(['pendaftaran.kode_pendaftaran'])
                    ->sortable()
                    ->weight(FontWeight::Bold),
                
                TextColumn::make('pendaftaran.jamaah.nama_lengkap')
                    ->label('Nama Jamaah')
                    ->searchable(['pendaftaran.jamaah.nama_lengkap'])
                    ->sortable()
                    ->limit(30),
                
                TextColumn::make('pendaftaran.jamaah.jenis_kelamin')
                    ->label('Gender')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'L' => 'blue',
                        'P' => 'pink',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                        default => $state,
                    }),
                
                TextColumn::make('room.nomor_kamar')
                    ->label('Nomor Kamar')
                    ->searchable(['room.nomor_kamar'])
                    ->sortable()
                    ->weight(FontWeight::Bold),
                
                TextColumn::make('room.tipe_kamar')
                    ->label('Tipe Kamar')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'single' => 'info',
                        'double' => 'success',
                        'triple' => 'warning',
                        'quad' => 'primary',
                        default => 'gray',
                    }),
                
                TextColumn::make('room.kapasitas')
                    ->label('Kapasitas')
                    ->numeric()
                    ->sortable(),
                
                TextColumn::make('room_occupancy')
                    ->label('Terisi')
                    ->getStateUsing(function (RoomAssignment $record): string {
                        $occupancy = $record->room->roomAssignments()->count();
                        $capacity = $record->room->kapasitas;
                        return "{$occupancy}/{$capacity}";
                    })
                    ->badge()
                    ->color(function (RoomAssignment $record): string {
                        $occupancy = $record->room->roomAssignments()->count();
                        $capacity = $record->room->kapasitas;
                        
                        if ($occupancy >= $capacity) {
                            return 'danger';
                        } elseif ($occupancy > ($capacity * 0.8)) {
                            return 'warning';
                        }
                        
                        return 'success';
                    }),
                
                TextColumn::make('assigned_at')
                    ->label('Waktu Penempatan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups([
                Group::make('room.tipe_kamar')
                    ->label('Tipe Kamar')
                    ->collapsible()
                    ->getTitleFromRecordUsing(fn (RoomAssignment $record): string => match ($record->room->tipe_kamar) {
                        'single' => 'Single Room',
                        'double' => 'Double Room',
                        'triple' => 'Triple Room',
                        'quad' => 'Quad Room',
                        default => ucfirst($record->room->tipe_kamar) . ' Room',
                    }),
            ])
            ->filters([
                SelectFilter::make('pendaftaran.paket_keberangkatan_id')
                    ->label('Paket Keberangkatan')
                    ->relationship('pendaftaran.paketKeberangkatan', 'nama_paket')
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('room.tipe_kamar')
                    ->label('Tipe Kamar')
                    ->options([
                        'single' => 'Single',
                        'double' => 'Double',
                        'triple' => 'Triple',
                        'quad' => 'Quad',
                    ]),
                
                SelectFilter::make('pendaftaran.jenis_kelamin')
                    ->label('Gender')
                    ->options([
                        'laki-laki' => 'Laki-laki',
                        'perempuan' => 'Perempuan',
                    ]),
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
            ->defaultSort('assigned_at', 'desc');
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
            'index' => Pages\ListRoomAssignments::route('/'),
            'create' => Pages\CreateRoomAssignment::route('/create'),
            'edit' => Pages\EditRoomAssignment::route('/{record}/edit'),
        ];
    }
}