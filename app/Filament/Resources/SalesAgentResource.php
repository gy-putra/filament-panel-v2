<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesAgentResource\Pages;
use App\Filament\Resources\SalesAgentResource\RelationManagers;
use App\Models\SalesAgent;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class SalesAgentResource extends Resource
{
    protected static ?string $model = SalesAgent::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Sales Agent';

    protected static ?string $pluralModelLabel = 'Sales Agents';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('agent_code')
                            ->label('Id Agent')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('e.g., AG-2025-001')
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(150)
                            ->columnSpan(2),
                        
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->required()
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('place_of_birth')
                            ->label('Tempat Lahir') 
                            ->required()
                            ->maxLength(150)
                            ->placeholder('e.g., Jakarta, Indonesia')
                            ->columnSpan(2),
                        
                        Forms\Components\Textarea::make('address')
                            ->label('Alamat')
                            ->required()
                            ->rows(3)
                            ->columnSpan(3),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Contact Details')
                    ->schema([
                        Forms\Components\TextInput::make('phone_number')
                            ->label('Nomor Telepon')
                            ->required()
                            ->tel()
                            ->maxLength(32)
                            ->columnSpan(1),
                        
                        Forms\Components\Select::make('type')
                            ->label('Tipe Agent')
                            ->required()
                            ->options([
                                'internal' => 'Internal (Employee)',
                                'external' => 'External',
                            ])
                            ->columnSpan(1),
                        
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'active' => 'Aktif',
                                'inactive' => 'Tidak Aktif',
                            ])
                            ->default('active')
                            ->columnSpan(1),
                        
                        Forms\Components\DatePicker::make('join_on')
                            ->label('Tanggal Bergabung')
                            ->required()
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Account Information')
                    ->description('Commission and payment details')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Nama Bank')
                            ->required()
                            ->maxLength(150)
                            ->columnSpan(2),
                        
                        Forms\Components\TextInput::make('account_number')
                            ->label('Nomor Rekening')
                            ->maxLength(50)
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('account_name')
                            ->label('Nama Rekening')
                            ->required()
                            ->maxLength(150)
                            ->placeholder('Nama pemilik rekening')
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('agency_name')
                            ->label('Nama Agency')
                            ->maxLength(150)
                            ->visible(fn (Forms\Get $get) => $get('type') === 'external')
                            ->columnSpan(2),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('System Information')
                    ->schema([
                        Forms\Components\Select::make('created_by')
                            ->label('Created By')
                            ->relationship('createdBy', 'name')
                            ->searchable()
                            ->preload()
                            ->default(Auth::id())
                            ->disabled()
                            ->columnSpan(1),
                    ])
                    ->columns(1)
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('agent_code')
                    ->label('Kode Agent')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('place_of_birth')
                    ->label('Tempat Lahir')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('birth_date')
                    ->label('Tanggal Lahir')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                
                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Nomor Telepon')
                    ->searchable(),
                
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipe Agent')
                    ->colors([
                        'primary' => 'internal',
                        'secondary' => 'external',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'internal' => 'Internal',
                        'external' => 'External',
                    }),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    }),
                
                Tables\Columns\TextColumn::make('join_on')
                    ->label('Tanggal Bergabung')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('bank_name')
                    ->label('Nama Bank')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('account_name')
                    ->label('Nama Rekening')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Agent')
                    ->options([
                        'internal' => 'Internal',
                        'external' => 'External',
                    ]),
                
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
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
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListSalesAgents::route('/'),
            'create' => Pages\CreateSalesAgent::route('/create'),
            'edit' => Pages\EditSalesAgent::route('/{record}/edit'),
        ];
    }
}
