<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UmrahProgramResource\Pages;
use App\Filament\Resources\UmrahProgramResource\RelationManagers;
use App\Models\UmrahProgram;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;

class UmrahProgramResource extends Resource
{
    protected static ?string $model = UmrahProgram::class;

    protected static ?string $navigationIcon = 'heroicon-o-bookmark';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Umrah Program';

    protected static ?string $modelLabel = 'Umrah Program';

    protected static ?string $pluralModelLabel = 'Umrah Programs';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Program Information')
                    ->schema([
                        TextInput::make('program_code')
                            ->label('Program Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('e.g., UMR001')
                            ->helperText('Unique identifier for the program'),
                        
                        TextInput::make('program_name')
                            ->label('Program Name')
                            ->required()
                            ->maxLength(150)
                            ->placeholder('e.g., Umrah Plus Madinah 12 Days')
                            ->helperText('Descriptive name of the program'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('program_code')
                    ->label('Program Code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                
                TextColumn::make('program_name')
                    ->label('Program Name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                
                TextColumn::make('paket_keberangkatans_count')
                    ->label('Packages')
                    ->counts('paketKeberangkatans')
                    ->badge()
                    ->color('success'),
                
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('has_packages')
                    ->label('Has Packages')
                    ->query(fn (Builder $query): Builder => $query->has('paketKeberangkatans')),
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
            ->defaultSort('program_code');
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
            'index' => Pages\ListUmrahPrograms::route('/'),
            'create' => Pages\CreateUmrahProgram::route('/create'),
            'edit' => Pages\EditUmrahProgram::route('/{record}/edit'),
        ];
    }
}
