# Enhanced Financial Management Error Logging

## Overview

The Enhanced Financial Management module now includes comprehensive error logging and monitoring capabilities for better troubleshooting, audit trails, and system monitoring.

## Features Implemented

### 1. Structured Error Logging
- **Unique Error IDs**: Each error gets a unique identifier for tracking
- **Error Codes**: Categorized error codes for different types of failures
- **Contextual Information**: Rich context including user info, request data, and system state
- **Stack Traces**: Complete error traces for debugging

### 2. Dedicated Log Channels

#### Financial Channel (`storage/logs/financial/`)
- **Purpose**: Financial operations and errors
- **Retention**: 30 days (configurable via `LOG_FINANCIAL_DAYS`)
- **Format**: Daily rotation with structured JSON context

#### Audit Channel (`storage/logs/audit/`)
- **Purpose**: Audit trail for financial operations
- **Retention**: 90 days (configurable via `LOG_AUDIT_DAYS`)
- **Format**: Daily rotation for compliance

### 3. Error Context Information

Each error log includes:
```json
{
  "error_id": "ERR_unique_identifier",
  "error_code": "FINANCIAL_STATS_ERROR",
  "user_id": 123,
  "ip_address": "192.168.1.1",
  "user_agent": "Mozilla/5.0...",
  "url": "https://marine.ng/api/v1/admin/financial/stats",
  "method": "GET",
  "parameters": {...},
  "exception_class": "Exception",
  "file": "/path/to/file.php",
  "line": 123,
  "trace": "..."
}
```

## Error Codes

### Financial Module Error Codes

| Code | Description | Context |
|------|-------------|---------|
| `FINANCIAL_STATS_ERROR` | Financial statistics retrieval failure | Stats type, date range |
| `FINANCIAL_TRANSACTIONS_ERROR` | Transaction listing failure | Filters, pagination |
| `FINANCIAL_TRENDS_ERROR` | Trend analysis failure | Period, type |
| `REVENUE_BREAKDOWN_ERROR` | Revenue breakdown failure | Period, category |
| `REVENUE_SUMMARY_ERROR` | Revenue summary failure | Date range |
| `EXPENSE_SUMMARY_ERROR` | Expense summary failure | Filters |
| `EXPENSE_CREATE_ERROR` | Expense creation failure | Expense data |
| `EXPENSE_UPDATE_ERROR` | Expense update failure | Expense ID, update data |
| `EXPENSE_DELETE_ERROR` | Expense deletion failure | Expense ID |
| `SERVICE_TEMPLATES_ERROR` | Service templates failure | Template type |
| `REPORT_EXPORT_ERROR` | Report export failure | Format, type |
| `FINANCIAL_EXPORT_ERROR` | Financial data export failure | Export parameters |

## Operation Logging

### Tracked Operations
- `get_financial_stats`: Financial statistics requests
- `get_transactions`: Transaction listing requests
- `get_revenue_breakdown`: Revenue analysis requests
- `get_trends`: Trend analysis requests
- `create_expense`: Expense creation
- `update_expense`: Expense updates
- `delete_expense`: Expense deletions
- `export_report`: Report exports

### Operation Context
```json
{
  "operation": "get_financial_stats",
  "user_id": 123,
  "ip_address": "192.168.1.1",
  "timestamp": "2025-09-22T07:32:53.243991Z",
  "data": {
    "user_role": "admin",
    "filters": {...}
  }
}
```

## Frontend Integration

### Error Handling
The frontend now captures and displays:
- Error ID for support tracking
- User-friendly error messages
- Technical details (in development)
- Copy-to-clipboard functionality for error IDs
- Direct support contact with pre-filled error information

### Usage Example
```jsx
import ErrorDisplay from '../components/ui/ErrorDisplay';

// In your component
<ErrorDisplay
  error={error}
  title="Failed to load financial data"
  showDetails={process.env.NODE_ENV === 'development'}
  onRetry={retryFunction}
/>
```

## Testing

### Test Command
```bash
php artisan test:financial-logging
```

This command:
- Tests financial channel logging
- Tests audit channel logging
- Generates sample error logs
- Verifies log file creation

### Log Verification
Check logs at:
- `storage/logs/financial/financial-YYYY-MM-DD.log`
- `storage/logs/audit/audit-YYYY-MM-DD.log`
- `storage/logs/laravel.log`

## Configuration

### Environment Variables
```env
LOG_FINANCIAL_DAYS=30  # Financial log retention days
LOG_AUDIT_DAYS=90      # Audit log retention days
LOG_LEVEL=debug        # Minimum log level
```

### Log Channels Configuration
The logging configuration is in `config/logging.php`:

```php
'financial' => [
    'driver' => 'daily',
    'path' => storage_path('logs/financial/financial.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => env('LOG_FINANCIAL_DAYS', 30),
    'replace_placeholders' => true,
],

'audit' => [
    'driver' => 'daily',
    'path' => storage_path('logs/audit/audit.log'),
    'level' => env('LOG_LEVEL', 'info'),
    'days' => env('LOG_AUDIT_DAYS', 90),
    'replace_placeholders' => true,
],
```

## API Response Structure

### Error Response Format
```json
{
  "success": false,
  "message": "Failed to fetch financial stats",
  "error_id": "ERR_68d0fba53e0cd",
  "error_code": "FINANCIAL_STATS_ERROR",
  "error": {
    "message": "Database connection failed",
    "file": "/path/to/controller.php",
    "line": 123,
    "trace": "..." // Only in debug mode
  }
}
```

## Best Practices

### For Developers
1. Always use the `handleError()` method for consistent error handling
2. Provide meaningful error codes and context
3. Use `logFinancialOperation()` for audit trails
4. Include relevant request parameters in error context

### For System Administrators
1. Monitor financial logs daily
2. Set up log rotation to prevent disk space issues
3. Configure log retention based on compliance requirements
4. Monitor error patterns for system health

### For Support Teams
1. Always request the Error ID when users report issues
2. Use Error ID to quickly locate relevant logs
3. Check both financial and general logs for complete context
4. Correlate errors with system metrics and user reports

## Security Considerations

1. **Sensitive Data**: The logging system filters out sensitive data like passwords and tokens
2. **User Privacy**: Personal information is logged only when necessary for debugging
3. **Access Control**: Log files should have restricted access permissions
4. **Retention**: Logs are automatically cleaned up based on retention policies

## Troubleshooting

### Common Issues

1. **Log Files Not Created**
   - Check directory permissions: `storage/logs/financial/` and `storage/logs/audit/`
   - Verify logging configuration in `config/logging.php`

2. **Missing Error Context**
   - Ensure the controller uses `handleError()` method
   - Check if the error is being caught properly

3. **Performance Impact**
   - Logging is asynchronous where possible
   - Consider log level adjustment in production
   - Monitor disk space usage

### Performance Monitoring
- Log file sizes
- Error frequency patterns
- Response time impact
- Disk space utilization