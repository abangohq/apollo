# Crypto Swap Feature - Architecture Documentation

## Overview

The crypto swap feature enables users to exchange one cryptocurrency for another using the Changelly API as the exchange provider. The system provides both floating and fixed rate swaps with comprehensive transaction tracking and status monitoring.

## System Architecture

### High-Level Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Laravel API   │    │   Changelly     │
│   Application   │◄──►│   Backend       │◄──►│   Exchange API  │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                              │
                              ▼
                       ┌─────────────────┐
                       │   Database      │
                       │   (MySQL)       │
                       └─────────────────┘
```

### Core Components

#### 1. Service Layer
- **ChangellyService**: Primary integration with Changelly API
- **CryptoApisService**: Bitcoin wallet management
- **XprocessingService**: Multi-chain wallet management

#### 2. Controller Layer
- **CryptoSwapController**: User-facing swap endpoints
- **CryptoController (Admin)**: Administrative swap management
- **WebhookController**: External webhook handling

#### 3. Action Layer
- **CryptoSwapAction**: Orchestrates swap transaction creation

#### 4. Job Layer
- **ResolveSwapTransaction**: Asynchronous transaction status monitoring

#### 5. Model Layer
- **SwapTransaction**: Core swap transaction entity
- **CryptoAsset**: Supported cryptocurrency assets
- **CryptoWallet**: User wallet management

## Component Details

### ChangellyService

**Location**: `app/Services/Crypto/ChangellyService.php`

**Responsibilities**:
- API authentication using RSA signatures
- Rate estimation (floating and fixed)
- Transaction creation
- Status monitoring
- Currency and pair management

**Key Methods**:
- `floatEstimate()`: Get floating rate estimates
- `fixedEstimate()`: Get fixed rate estimates
- `swapRates()`: Combined rate comparison
- `createTransaction()`: Create floating rate swap
- `createFixedTransaction()`: Create fixed rate swap
- `currencies()`: Get available currencies
- `swapPairs()`: Get available trading pairs
- `swapStatus()`: Check transaction status

### CryptoSwapController

**Location**: `app/Http/Controllers/User/CryptoSwapController.php`

**API Endpoints**:
- `POST /crypto/swap/rates`: Get swap rates
- `POST /crypto/swap/create`: Create new swap
- `GET /crypto/swap/currencies`: List available currencies
- `GET /crypto/swap/pairs/{from}`: Get trading pairs
- `GET /crypto/swap/{swapId}/details`: Get swap details

### CryptoSwapAction

**Location**: `app/Actions/Payment/CryptoSwapAction.php`

**Process Flow**:
1. Validate swap request
2. Generate or use existing wallet address
3. Create swap transaction with Changelly
4. Store transaction record in database
5. Dispatch status monitoring job

### SwapTransaction Model

**Location**: `app/Models/SwapTransaction.php`

**Database Schema**:
```sql
CREATE TABLE swap_transactions (
    id BIGINT PRIMARY KEY,
    user_id BIGINT FOREIGN KEY,
    reference VARCHAR(255),
    swap_tranx_id VARCHAR(255) INDEX,
    swap_type ENUM('float', 'fixed') INDEX,
    status VARCHAR(255) INDEX,
    currency_from VARCHAR(255),
    currency_to VARCHAR(255),
    payin_address VARCHAR(255),
    payout_address VARCHAR(255),
    refund_address VARCHAR(255),
    is_app_address BOOLEAN DEFAULT FALSE,
    amount_expected_from DECIMAL(16,8),
    amount_expected_to DECIMAL(16,8),
    pay_till DATETIME INDEX,
    network_fee VARCHAR(255),
    track_url VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Data Flow

### Swap Creation Flow

```
1. User Request → CryptoSwapController::createSwap()
2. Controller → CryptoSwapAction::handle()
3. Action → ChangellyService::createTransaction()
4. Changelly API Response → SwapTransaction::create()
5. Database Storage → ResolveSwapTransaction::dispatch()
6. Background Job → Status Monitoring
```

### Rate Estimation Flow

```
1. User Request → CryptoSwapController::swapRate()
2. Controller → ChangellyService::swapRates()
3. Service → floatEstimate() + fixedEstimate()
4. Parallel API Calls → Changelly API
5. Response Processing → Rate Comparison
6. Formatted Response → User
```

### Status Monitoring Flow

```
1. ResolveSwapTransaction Job → ChangellyService::swapStatus()
2. Status Check → Changelly API
3. Status Update → SwapTransaction::update()
4. Notification → User (if status changed)
5. Retry Logic → Re-queue if incomplete
```

## Security Considerations

### Authentication
- RSA signature-based authentication with Changelly
- Private key stored in environment configuration
- Request signing using phpseclib3

### Data Protection
- Sensitive data encrypted in database
- Wallet addresses validated before use
- Refund addresses required for security

### Rate Limiting
- API calls cached to reduce external requests
- Currency data cached for 15 minutes
- Swap pairs cached for 5 minutes

## Error Handling

### Validation Errors
- Request validation using Form Requests
- Changelly API error propagation
- User-friendly error messages

### Transaction Failures
- Database transaction rollback on errors
- Job retry mechanism with exponential backoff
- Alert logging for failed transactions

### Timeout Handling
- 3-hour timeout for transaction resolution
- Automatic job termination after timeout
- Admin alert for unresolved transactions

## Performance Optimizations

### Caching Strategy
- Currency list cached for 15 minutes
- Trading pairs cached for 5 minutes
- Rate estimates not cached (real-time pricing)

### Asynchronous Processing
- Transaction status monitoring via queued jobs
- Non-blocking user experience
- Background status updates

### Database Optimization
- Indexed fields for common queries
- Foreign key constraints for data integrity
- Efficient query patterns in repositories

## Monitoring and Logging

### Transaction Tracking
- Unique reference generation for each swap
- Comprehensive status tracking
- External tracking URL from Changelly

### Logging Strategy
- Failed transaction alerts
- API error logging
- Performance metrics tracking

### Notifications
- Real-time status updates to users
- Admin alerts for critical failures
- Email notifications for completed swaps

## Integration Points

### External Services
- **Changelly API**: Primary exchange provider
- **CryptoApis**: Bitcoin wallet management
- **Xprocessing**: Multi-chain wallet support

### Internal Systems
- **User Management**: Authentication and authorization
- **Wallet System**: Address generation and management
- **Notification System**: User communication
- **Admin Dashboard**: Transaction monitoring

## Scalability Considerations

### Horizontal Scaling
- Stateless service design
- Queue-based job processing
- Database connection pooling

### Performance Monitoring
- API response time tracking
- Job processing metrics
- Database query optimization

### Future Enhancements
- Multiple exchange provider support
- Advanced rate comparison algorithms
- Enhanced user analytics and reporting