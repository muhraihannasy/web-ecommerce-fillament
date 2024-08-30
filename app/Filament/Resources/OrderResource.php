<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;

use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ToggleButtons;
use App\Filament\Resources\OrderResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\OrderResource\RelationManagers;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor() : array|string|null
    {
        return static::getModel()::count() > 10 ? 'success' : 'danger';
    }


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
                            ->options([
                                'cod' => 'Cash on Delivery',
                                'paypal' => 'Paypal',
                                'stripe' => 'Stripe',
                            ])
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
                                    $set('unit_amount', Product::find($state)->price ?? 0);
                                })
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $set('total_amount', $state * $get('unit_amount'));
                                })
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $set('quantity', 1);
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
                                ->dehydrated()
                                ->columnSpan(3),

                            TextInput::make('total_amount')
                                ->label('Total')
                                ->minValue(1)
                                ->required()
                                ->disabled()
                                ->dehydrated()
                                ->columnSpan(3),
                        ])->columns(12),

                        Placeholder::make('grand_total_placeholder')
                        ->label('Grand Total')
                        ->content(function (Get $get, Set $set) {
                            $total = 0;

                            if(!$repeaters = $get('items')) return "Rp. " . number_format($total, 0, '.', '.');

                            foreach ($repeaters as $key => $item) {
                                $total += $get("items.{$key}.total_amount");
                            }

                            $set('grand_total', $total);

                            return "Rp. " . number_format($total, 0, '.', '.');
                        }),

                        Hidden::make('grand_total')
                            ->dehydrated()
                            ->default(0),
                    ])


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('grand_total')
                    ->money('USD')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('shipping_method')
                    ->sortable()

                    ->searchable(),

                Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'new' => 'New',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'canceled' => 'Cancelled',
                    ])
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
