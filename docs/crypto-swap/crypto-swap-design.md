# Crypto Swap Feature - Design Documentation

## Table of Contents
1. [API Specifications](#api-specifications)
2. [Data Structures](#data-structures)
3. [Business Logic](#business-logic)
4. [User Interface Flow](#user-interface-flow)
5. [Error Handling](#error-handling)
6. [Security Design](#security-design)
7. [Performance Design](#performance-design)

## API Specifications

### Base URL
```
/api/crypto/swap
```

### Authentication
All endpoints require Bearer token authentication.

### 1. Get Swap Rates

**Endpoint**: `POST /rates`

**Description**: Get both floating and fixed rate estimates for a currency swap.

**Request Body**:
```json
{
  "from": "btc",
  "to": "eth", 
  "amountFrom": "0.1"
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "exchange": {
      "from": "btc",
      "to": "eth",
      "amountFrom": "0.1"
    },
    "floating": {
      "amountTo": "2.45",
      "min": "0.001",
      "max": "100",
      "expiredAt": null
    },
    "fixed": {
      "rate_id": "fixed_rate_123",
      "amountTo": "2.43",
      "min": "0.001", 
      "max": "100",
      "expiredAt": "2024-01-15 14:30:00"
    }
  }
}
```

**Validation Rules**:
- `from`: required|string|exists in supported currencies
- `to`: required|string|exists in supported currencies|different from 'from'
- `amountFrom`: required|numeric|min:0.00000001

### 2. Create Swap Transaction

**Endpoint**: `POST /create`

**Description**: Create a new swap transaction.

**Request Body**:
```json
{
  "from": "btc",
  "to": "eth",
  "amountFrom": "0.1",
  "address": "0x742d35Cc6634C0532925a3b8D4C9db96590c6C87",
  "refundAddress": "bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh",
  "swap_type": "fixed",
  "rateId": "fixed_rate_123",
  "app_address": false
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 123,
    "reference": "SWP_20240115_001",
    "swap_tranx_id": "changelly_tx_456",
    "status": "new",
    "payin_address": "bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh",
    "payout_address": "0x742d35Cc6634C0532925a3b8D4C9db96590c6C87",
    "amount_expected_from": "0.1",
    "amount_expected_to": "2.43",
    "pay_till": "2024-01-15 15:30:00",
    "network_fee": "0.0005",
    "track_url": "https://changelly.com/track/changelly_tx_456"
  }
}
```

**Validation Rules**:
- `from`: required|string
- `to`: required|string
- `amountFrom`: required|numeric|min:0.00000001
- `address`: required_unless:app_address,true|string
- `refundAddress`: required|string
- `swap_type`: required|in:float,fixed
- `rateId`: required_if:swap_type,fixed|string
- `app_address`: boolean

### 3. Get Available Currencies

**Endpoint**: `GET /currencies`

**Description**: Get list of all supported currencies for swapping.

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "name": "bitcoin",
      "fullName": "Bitcoin",
      "ticker": "btc",
      "image": "https://cdn.changelly.com/icons/btc.png"
    },
    {
      "name": "ethereum", 
      "fullName": "Ethereum",
      "ticker": "eth",
      "image": "https://cdn.changelly.com/icons/eth.png"
    }
  ]
}
```

### 4. Get Trading Pairs

**Endpoint**: `GET /pairs/{from}`

**Description**: Get available currencies that can be swapped from the specified currency.

**Parameters**:
- `from`: Source currency ticker (e.g., "btc")

**Response**:
```json
{
  "success": true,
  "data": ["eth", "ltc", "xrp", "ada"]
}
```

### 5. Get Swap Details

**Endpoint**: `GET /{swapId}/details`

**Description**: Get detailed information about a specific swap transaction.

**Parameters**:
- `swapId`: Swap transaction ID

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 123,
    "reference": "SWP_20240115_001",
    "swap_tranx_id": "changelly_tx_456",
    "swap_type": "fixed",
    "status": "confirming",
    "currency_from": "btc",
    "currency_to": "eth",
    "payin_address": "bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh",
    "payout_address": "0x742d35Cc6634C0532925a3b8D4C9db96590c6C87",
    "refund_address": "bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh",
    "is_app_address": false,
    "amount_expected_from": "0.1",
    "amount_expected_to": "2.43",
    "pay_till": "2024-01-15 15:30:00",
    "network_fee": "0.0005",
    "track_url": "https://changelly.com/track/changelly_tx_456",
    "created_at": "2024-01-15 14:30:00",
    "updated_at": "2024-01-15 14:35:00"
  }
}
```

## Data Structures

### SwapTransaction Model

```php
class SwapTransaction extends Model
{
    protected $fillable = [
        'user_id',           // Foreign key to users table
        'reference',         // Internal reference (SWP_YYYYMMDD_XXX)
        'swap_tranx_id',     // Changelly transaction ID
        'swap_type',         // 'float' or 'fixed'
        'status',           // Transaction status from Changelly
        'currency_from',     // Source currency ticker
        'currency_to',       // Target currency ticker
        'payin_address',     // Address to send source currency
        'payout_address',    // Address to receive target currency
        'refund_address',    // Address for refunds if needed
        'is_app_address',    // Boolean: using app-managed address
        'amount_expected_from', // Expected source amount
        'amount_expected_to',   // Expected target amount
        'pay_till',          // Payment deadline
        'network_fee',       // Network fee for transaction
        'track_url'          // Changelly tracking URL
    ];

    protected $casts = [
        'is_app_address' => 'boolean',
        'pay_till' => 'datetime'
    ];
}
```

### Status Values

```php
// Changelly transaction statuses
const STATUSES = [
    'new',          // Transaction created, waiting for payment
    'waiting',      // Waiting for payment confirmation
    'confirming',   // Payment received, confirming
    'exchanging',   // Exchange in progress
    'sending',      // Sending target currency
    'finished',     // Transaction completed successfully
    'failed',       // Transaction failed
    'refunded',     // Transaction refunded
    'overdue',      // Payment deadline exceeded
    'expired'       // Fixed rate expired
];
```

### Request/Response DTOs

```php
// Swap Rate Request
class SwapRateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'from' => 'required|string',
            'to' => 'required|string|different:from',
            'amountFrom' => 'required|numeric|min:0.00000001'
        ];
    }
}

// Create Swap Request  
class CreateSwapRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'from' => 'required|string',
            'to' => 'required|string',
            'amountFrom' => 'required|numeric|min:0.00000001',
            'address' => 'required_unless:app_address,true|string',
            'refundAddress' => 'required|string',
            'swap_type' => 'required|in:float,fixed',
            'rateId' => 'required_if:swap_type,fixed|string',
            'app_address' => 'boolean'
        ];
    }
}
```

## Business Logic

### Swap Creation Process

```php
class CryptoSwapAction
{
    public function handle(): SwapTransaction
    {
        DB::beginTransaction();
        
        try {
            // 1. Validate request and user permissions
            $this->validateSwapRequest();
            
            // 2. Generate or retrieve wallet address
            $address = $this->resolvePayoutAddress();
            
            // 3. Create swap with Changelly
            $swapResponse = $this->createChangellySwap($address);
            
            // 4. Store transaction record
            $transaction = $this->storeSwapTransaction($swapResponse);
            
            // 5. Schedule status monitoring
            ResolveSwapTransaction::dispatch($transaction)->afterCommit();
            
            DB::commit();
            return $transaction;
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

### Address Resolution Logic

```php
private function resolvePayoutAddress(): string
{
    if ($this->request->app_address) {
        // Use app-managed wallet
        return $this->getOrCreateAppWallet($this->request->to);
    }
    
    // Use user-provided address
    return $this->request->address;
}

private function getOrCreateAppWallet(string $currency): string
{
    $wallet = $this->cryptoRepo->chainWallet(
        $this->request->user()->id, 
        $currency
    );
    
    if ($wallet) {
        return $wallet->address;
    }
    
    // Create new wallet based on currency
    if (strtoupper($currency) === 'BTC') {
        return $this->cryptoApis->createWallet($currency, $this->request->user());
    }
    
    return $this->xprocess->createWallet($currency, $this->request->user());
}
```

### Status Monitoring Logic

```php
class ResolveSwapTransaction implements ShouldQueue
{
    public function handle(ChangellyService $changelly): void
    {
        // Get current status from Changelly
        $response = $changelly->swapStatus($this->swapTransaction->swap_tranx_id);
        
        if ($this->isErrorResponse($response)) {
            $this->handleRetry();
            return;
        }
        
        $newStatus = $response['result'];
        $this->swapTransaction->update(['status' => $newStatus]);
        
        // Notify user of status changes
        if ($this->isNotifiableStatus($newStatus)) {
            $this->swapTransaction->user->notify(
                new SwapStateNotification($this->swapTransaction)
            );
        }
        
        // Continue monitoring if not final status
        if ($this->shouldContinueMonitoring($newStatus)) {
            self::dispatch($this->swapTransaction)
                ->delay(now()->addMinutes(env('SWAP_RETRY', 5)));
        }
    }
}
```

## User Interface Flow

### 1. Rate Comparison Flow

```
User Input → Currency Selection → Amount Entry → Rate Fetch → Rate Display
    ↓
[BTC] → [ETH] → [0.1] → API Call → [Float: 2.45 ETH | Fixed: 2.43 ETH]
```

### 2. Swap Creation Flow

```
Rate Selection → Address Input → Confirmation → Transaction Creation → Status Tracking
      ↓              ↓              ↓                ↓                    ↓
   [Fixed Rate] → [0x742d...] → [Confirm] → [SWP_001] → [Status: New]
```

### 3. Transaction Monitoring

```
Status Updates → Real-time Notifications → Completion Notification
      ↓                    ↓                        ↓
  [Confirming] → [Push Notification] → [Transaction Complete]
```

## Error Handling

### API Error Responses

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "amountFrom": ["The amount is below minimum threshold"]
  }
}
```

### Changelly API Errors

```php
// Error mapping from Changelly responses
const CHANGELLY_ERRORS = [
    'INVALID_CURRENCY' => 'Currency not supported',
    'AMOUNT_TOO_SMALL' => 'Amount below minimum threshold',
    'AMOUNT_TOO_BIG' => 'Amount exceeds maximum limit',
    'PAIR_NOT_SUPPORTED' => 'Currency pair not available'
];
```

### Retry Logic

```php
class ResolveSwapTransaction
{
    public function shouldRetry(): bool
    {
        $transactionAge = now()->diffInHours($this->swapTransaction->created_at);
        return $transactionAge < 3; // 3-hour timeout
    }
    
    public function getRetryDelay(): int
    {
        return env('SWAP_RETRY', 5); // 5 minutes default
    }
}
```

## Security Design

### Authentication & Authorization

```php
// Route middleware
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::prefix('crypto/swap')->group(function () {
        // Swap endpoints
    });
});

// Controller authorization
public function createSwap(CryptoSwapAction $action)
{
    $this->authorize('create-swap', auth()->user());
    return $action->handle();
}
```

### Request Signing (Changelly)

```php
private function signature(array $payload): string
{
    $privateKey = RSA::load(hex2bin(config('services.changelly.privateKey')));
    $message = json_encode($payload);
    
    openssl_sign($message, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    return base64_encode($signature);
}
```

### Input Validation

```php
// Address validation
'address' => [
    'required_unless:app_address,true',
    'string',
    new ValidCryptoAddress($this->to)
],

// Amount validation  
'amountFrom' => [
    'required',
    'numeric',
    'min:0.00000001',
    new MinimumSwapAmount($this->from, $this->to)
]
```

## Performance Design

### Caching Strategy

```php
// Currency caching (15 minutes)
Cache::remember('swap-currencies', now()->addMinutes(15), function () {
    return $this->changelly->getCurrenciesFull();
});

// Pair caching (5 minutes)
Cache::remember("swap-pairs-{$from}", now()->addMinutes(5), function () use ($from) {
    return $this->changelly->getPairs($from);
});
```

### Database Optimization

```sql
-- Indexes for common queries
CREATE INDEX idx_swap_transactions_user_status ON swap_transactions(user_id, status);
CREATE INDEX idx_swap_transactions_created_at ON swap_transactions(created_at);
CREATE INDEX idx_swap_transactions_swap_tranx_id ON swap_transactions(swap_tranx_id);
```

### Queue Configuration

```php
// Job configuration
class ResolveSwapTransaction implements ShouldQueue
{
    public $tries = 3;
    public $timeout = 60;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min
    
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->swapTransaction->reference))
                ->releaseAfter(60)
        ];
    }
}
```

### Rate Limiting

```php
// API rate limiting
Route::middleware(['throttle:swap-rates'])->group(function () {
    Route::post('rates', [CryptoSwapController::class, 'swapRate']);
});

// Rate limiter configuration
RateLimiter::for('swap-rates', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()->id);
});
```

This design documentation provides comprehensive implementation details for the crypto swap feature, covering all aspects from API design to performance optimization.