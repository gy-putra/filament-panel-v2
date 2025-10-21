<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffResource\Pages;
use App\Filament\Resources\StaffResource\RelationManagers;
use App\Models\Staff;
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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\SelectFilter;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Departure Management';

    protected static ?string $navigationLabel = 'Staff';

    protected static ?string $modelLabel = 'Staff';

    protected static ?string $pluralModelLabel = 'Staff';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Staff')
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Staff')
                            ->required()
                            ->maxLength(150),
                        
                        Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->required()
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ]),
                        
                        TextInput::make('no_hp')
                            ->label('No. HP')
                            ->required()
                            ->tel()
                            ->maxLength(20),
                        
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(100),
                        
                        Select::make('tipe_staff')
                            ->label('Tipe Staff')
                            ->required()
                            ->options([
                                'muthowif' => 'Muthowif',
                                'muthowifah' => 'Muthowifah',
                                'lapangan' => 'Lapangan',
                                'dokumen' => 'Dokumen',
                                'medis' => 'Medis',
                                'lainnya' => 'Lainnya',
                            ]),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama Staff')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                
                TextColumn::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'L' => 'blue',
                        'P' => 'pink',
                        default => 'gray',
                    }),
                
                TextColumn::make('tipe_staff')
                    ->label('Tipe Staff')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'muthowif' => 'success',
                        'muthowifah' => 'warning',
                        'lapangan' => 'info',
                        'dokumen' => 'primary',
                        'medis' => 'danger',
                        'lainnya' => 'gray',
                        default => 'gray',
                    }),
                
                TextColumn::make('no_hp')
                    ->label('No. HP')
                    ->searchable(),
                
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ]),
                
                SelectFilter::make('tipe_staff')
                    ->label('Tipe Staff')
                    ->options([
                        'muthowif' => 'Muthowif',
                        'muthowifah' => 'Muthowifah',
                        'lapangan' => 'Lapangan',
                        'dokumen' => 'Dokumen',
                        'medis' => 'Medis',
                        'lainnya' => 'Lainnya',
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
            ->defaultSort('nama', 'asc');
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
            'index' => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'edit' => Pages\EditStaff::route('/{record}/edit'),
        ];
    }
}