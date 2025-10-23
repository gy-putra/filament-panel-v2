<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JamaahResource\Pages;
use App\Models\Jamaah;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\TrashedFilter;

class JamaahResource extends Resource
{
    protected static ?string $model = Jamaah::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nama_lengkap';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identity')
                    ->schema([
                        Forms\Components\TextInput::make('kode_jamaah')
                            ->label('Kode Jamaah')
                            ->disabled(fn ($context) => $context === 'edit')
                            ->hidden()
                            ->placeholder('Auto-generated')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('nama_lengkap')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(150)
                            ->autofocus()
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('nama_ayah')
                            ->label('Nama Ayah')
                            ->required()
                            ->maxLength(150)
                            ->columnSpan(2),
                        Forms\Components\Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->required()
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ])
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('tgl_lahir')
                            ->label('Tanggal Lahir')
                            ->required()
                            ->maxDate(today())
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('tempat_lahir')
                            ->label('Tempat Lahir')
                            ->maxLength(100)
                            ->columnSpan(1),
                        Forms\Components\Select::make('pendidikan_terakhir')
                            ->label('Pendidikan Terakhir')
                            ->required()
                            ->options([
                                'SD' => 'SD',
                                'SMP' => 'SMP',
                                'SMA' => 'SMA',
                                'D3' => 'D3',
                                'S1' => 'S1',
                                'S2' => 'S2',
                                'S3' => 'S3',
                                'Lainnya' => 'Lainnya',
                            ])
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Nationality & IDs')
                    ->schema([
                        Forms\Components\TextInput::make('kewarganegaraan')
                            ->label('Kewarganegaraan')
                            ->required()
                            ->default('Indonesia')
                            ->maxLength(64)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('negara')
                            ->label('Negara')
                            ->required()
                            ->default('Indonesia')
                            ->maxLength(100)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('no_ktp')
                            ->label('No. KTP')
                            ->maxLength(32)
                            ->unique(ignoreRecord: true)
                            ->helperText('WNI only; leave empty if WNA')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('no_bpjs')
                            ->label('No. BPJS')
                            ->maxLength(30)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Passport Information')
                    ->schema([
                        Forms\Components\TextInput::make('no_paspor')
                            ->label('Passport Number')
                            ->maxLength(50)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('kota_paspor')
                            ->label('Passport City')
                            ->maxLength(100)
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('tgl_terbit_paspor')
                            ->label('Issue Date')
                            ->maxDate(today())
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('tgl_expired_paspor')
                            ->label('Passport Expiry')
                            ->minDate(today())
                            ->columnSpan(1),
                        Forms\Components\FileUpload::make('foto_jamaah')
                            ->label('Pilgrim Photo')
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->directory('jamaah-photos')
                            ->disk('public')
                            ->visibility('public')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Contact & Address')
                    ->schema([
                        Forms\Components\Textarea::make('alamat')
                            ->label('Alamat')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('kabupaten')
                            ->label('Kabupaten')
                            ->maxLength(100)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('kecamatan')
                            ->label('Kecamatan')
                            ->maxLength(100)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('kelurahan')
                            ->label('Kelurahan')
                            ->maxLength(100)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('kota')
                            ->label('Kota')
                            ->maxLength(100)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('provinsi')
                            ->label('Provinsi')
                            ->maxLength(100)
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('no_hp')
                            ->label('No. HP')
                            ->required()
                            ->maxLength(32)
                            ->tel()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(150)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status & Work')
                    ->schema([
                        Forms\Components\Select::make('status_pernikahan')
                            ->label('Status Pernikahan')
                            ->required()
                            ->options([
                                'Single' => 'Single',
                                'Married' => 'Married',
                                'Widowed' => 'Widowed',
                                'Divorced' => 'Divorced',
                            ])
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('pekerjaan')
                            ->label('Pekerjaan')
                            ->maxLength(100)
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_jamaah')
                    ->label('Kode Jamaah')
                    ->badge()
                    ->hidden()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('foto_jamaah')
                    ->label('Photo')
                    ->circular()
                    ->size(40)
                    ->disk('public')
                    ->defaultImageUrl(asset('images/default-avatar.svg'))
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->label('Nama Sesuai Paspor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_ayah')
                    ->label('Nama Ayah')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenis_kelamin')
                    ->label('Gender')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'L' => 'info',
                        'P' => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    }),
                Tables\Columns\TextColumn::make('tgl_lahir')
                    ->label('Tanggal Lahir')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('no_hp')
                    ->label('No. HP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('no_ktp')
                    ->label('No. KTP')
                    ->searchable()
                    ->copyable()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('no_paspor')
                    ->label('No. Paspor')
                    ->searchable()
                    ->copyable()
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('kota_paspor')
                    ->label('Kota Paspor')
                    ->searchable()
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('tgl_terbit_paspor')
                    ->label('Terbit Paspor')
                    ->date()
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('tgl_expired_paspor')
                    ->label('Expired Paspor')
                    ->date()
                    ->sortable()
                    ->placeholder('-')
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'success')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('kabupaten')
                    ->label('Kabupaten')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('kecamatan')
                    ->label('Kecamatan')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('kelurahan')
                    ->label('Kelurahan')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('kota')
                    ->label('Kota')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('provinsi')
                    ->label('Provinsi')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status_pernikahan')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Single' => 'gray',
                        'Married' => 'success',
                        'Widowed' => 'warning',
                        'Divorced' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ]),
                Tables\Filters\SelectFilter::make('status_pernikahan')
                    ->label('Status Pernikahan')
                    ->options([
                        'Single' => 'Single',
                        'Married' => 'Married',
                        'Widowed' => 'Widowed',
                        'Divorced' => 'Divorced',
                    ]),
                Tables\Filters\SelectFilter::make('pendidikan_terakhir')
                    ->label('Pendidikan Terakhir')
                    ->options([
                        'SD' => 'SD',
                        'SMP' => 'SMP',
                        'SMA' => 'SMA',
                        'D3' => 'D3',
                        'S1' => 'S1',
                        'S2' => 'S2',
                        'S3' => 'S3',
                        'Lainnya' => 'Lainnya',
                    ]),
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
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
            'index' => Pages\ListJamaahs::route('/'),
            'create' => Pages\CreateJamaah::route('/create'),
            'view' => Pages\ViewJamaah::route('/{record}'),
            'edit' => Pages\EditJamaah::route('/{record}/edit'),
        ];
    }
}