<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;

use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\MarkdownEditor;

use App\Filament\Resources\ProductResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProductResource\RelationManagers;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('Product Information')
                        ->schema([
                            TextInput::make('name')
                                ->label('Name')
                                ->minLength(3)
                                ->maxLength(255)
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn (string $operation, ?string  $state, callable $set) => $operation == "create" ? $set('slug', Str::slug($state)) : null),

                            TextInput::make('slug')
                                ->label('Slug')
                                ->maxLength(255)
                                ->disabled()
                                ->required()
                                ->minLength(3)
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->dehydrated(),

                            MarkdownEditor::make('description')
                                ->label('Description')
                                ->required()
                                ->columnSpan('full')
                                ->fileAttachmentsDirectory('products'),
                            ])->columnSpan(2),


                        Section::make('Product Image')
                        ->schema([
                            FileUpload::make('image')
                                ->label('Image')
                                ->directory('products')
                                ->image()
                                ->multiple()
                                ->maxFiles(5)
                                ->reorderable()
                        ])->columnSpan(2),
                ])->columnSpan(2),

                Group::make()->schema([
                    Section::make('Price')
                        ->schema([
                            TextInput::make('price')
                                ->label('Price')
                                ->minLength(3)
                                ->numeric()
                                ->prefix('Rp. ')
                                ->maxLength(255)
                                ->required()
                        ]),

                    Section::make('Associations')
                        ->schema([
                            Select::make('category_id')
                               ->required()
                               ->searchable()
                               ->preload()
                               ->relationship('category', 'name'),

                            Select::make('brand_id')
                               ->required()
                               ->searchable()
                               ->preload()
                               ->relationship('brand', 'name')
                        ]),

                    Section::make('Status')
                        ->schema([
                            Toggle::make('in_stock')
                               ->required()
                               ->default(true),

                            Toggle::make('is_active')
                               ->required()
                               ->default(true),

                            Toggle::make('is_featured')
                               ->required()
                               ->default(true),
                        ])
                ])->columnSpan(1)
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('brand.name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->searchable()
                    ->sortable()
                    ->boolean(),

                Tables\Columns\IconColumn::make('in_stock')
                    ->searchable()
                    ->sortable()
                    ->boolean(),

                Tables\Columns\IconColumn::make('on_sale')
                    ->searchable()
                    ->sortable()
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->searchable()
                    ->sortable()
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->datetime()
                    ->sortable()
                    ->toggleable(true),
            ])
            ->filters([
                SelectFilter::make('Categori')
                    ->relationship('category', 'name'),

                SelectFilter::make('Brand')
                    ->relationship('brand', 'name')
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
