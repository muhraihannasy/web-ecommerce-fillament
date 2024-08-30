<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Order;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ToggleButtons;
use App\Filament\Resources\OrderResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\Product;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Order Information')
                    ->schema([
                        Select::make('user_id')
                            ->label('Customer')
                            ->relationship('user', 'name')
                            ->preload()
                            ->searchable()
                            ->required(),

                        Select::make('payment_method')
                            ->label('Payment Method')
                            ->required(),

                        Select::make('currency')
                            ->label('Currency')
                            ->options([
                                'usd' => 'USD',
                                'eur' => 'EUR',
                                'gbp' => 'GBP',
                                'idr' => 'IDR',
                            ])
                            ->required(),

                        Select::make('shipping_method')
                            ->label('Shipping Method')
                            ->options([
                                'cod' => 'Cash on Delivery',
                                'pickup' => 'Pickup',
                                'shipping' => 'Shipping',
                            ])
                            ->required(),

                       Textarea::make('notes')
                            ->label('Notes')
                            ->rows(5),

                       ToggleButtons::make('status')
                            ->inline()
                            ->label('Order Status')
                            ->options([
                                'new' => 'New',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'canceled' => 'Cancelled',
                            ])
                            ->icons([
                                'new' => 'heroicon-o-shopping-cart',
                                'processing' => 'heroicon-o-truck',
                                'shipped' => 'heroicon-o-truck',
                                'delivered' => 'heroicon-o-truck',
                                'canceled' => 'heroicon-o-x-circle',
                            ])
                            ->colors([
                                'new' => 'info',
                                'processing' => 'warning',
                                'shipped' => 'success',
                                'delivered' => 'success',
                                'canceled' => 'danger',
                            ])
                            ->default('Pending')
                            ->required(),
                    ])->columns(2),

                Section::make('Order Items')->schema([
                    Repeater::make('items')
                        ->relationship('orderItems')
                        ->schema([
                            Select::make('product_id')
                                ->label('Product')
                                ->relationship('product', 'name')
                                ->preload()
                                ->searchable()
                                ->distinct()
                                ->required()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->columnSpan(4)
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set)  {
                                    $set('unit_amount', Product::find($state)->price);
                                }),

                            TextInput::make('quantity')
                                ->label('Quantity')
                                ->minValue(1)
                                ->required()
                                ->numeric()
                                ->columnSpan(2)
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $set('total_amount', $state * $get('unit_amount'));
                                }),

                            TextInput::make('unit_amount')
                                ->required()
                                ->numeric()
                                ->disabled()
                                ->columnSpan(3),

                            TextInput::make('total_amount')
                                ->label('Total')
                                ->minValue(1)
                                ->required()
                                ->disabled()
                                ->columnSpan(3),
                        ])->columns(12),

                        // Select::make('category_id')
                        //     ->label('Category')
                        //     ->relationship('category', 'name')
                        //     ->preload()
                        //     ->searchable()
                        //     ->required(),

                        // Select::make('brand_id')
                        //     ->label('Brand')
                        //     ->relationship('brand', 'name')
                        //     ->preload()
                        //     ->searchable()
                        //     ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
