# 📱 MR Shife Mobile API Documentation

## Overview

This documentation covers the comprehensive Mobile API endpoints for the MR Shife food delivery application. The API is designed specifically for mobile applications with optimized response formats, proper pagination, and multilingual support.

## Route Organization

The Mobile API routes are organized by user type and functionality across different route files:

### 📁 **routes/api/customer.php**
- Customer authentication and profile management
- Favorites, cart, and order management
- Product browsing and merchant discovery
- Reviews and notifications

### 📁 **routes/api/merchant.php**
- Merchant authentication and profile management
- Product and category management
- Order processing and analytics
- Business settings and delivery configuration

### 📁 **routes/api/common.php**
- Home screen data aggregation
- App configuration and feature flags
- Location services and delivery zones
- Promotional content and coupons

This organization ensures better maintainability and follows the existing application structure.

## Base URL

```
http://localhost:8000/api/mobile
```

## Authentication

The API uses Laravel Sanctum for authentication. Include the Bearer token in the Authorization header for protected endpoints:

```
Authorization: Bearer {your_token_here}
```

## Language Support

All endpoints support Arabic and English languages. Include the language preference in the request header:

```
X-Language: en  // for English
X-Language: ar  // for Arabic
```

## Response Format

All API responses follow a consistent format:

```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": {
        // Response data here
    },
    "pagination": {  // Only for paginated responses
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75,
        "has_more_pages": true
    }
}
```

## Error Handling

Error responses include detailed information:

```json
{
    "success": false,
    "message": "Error message",
    "error": "Detailed error information",
    "errors": {  // Validation errors
        "field_name": ["Error message"]
    }
}
```

## Endpoints Overview

### 🏠 Home Screen

#### GET `/mobile/home`
Get comprehensive home screen data including featured merchants, popular products, nearby restaurants, and personalized recommendations.

**Response includes:**
- Hero banner and search suggestions
- Quick categories
- Featured merchants
- Popular dishes
- Nearby restaurants
- Special offers
- Cuisines
- Trending products
- Promotional banners

### 🏪 Merchants

#### GET `/mobile/merchants`
Get paginated list of merchants with advanced filtering options.

**Query Parameters:**
- `search` - Search by merchant name
- `business_type` - Filter by business type
- `is_featured` - Filter featured merchants
- `delivery_fee_max` - Maximum delivery fee
- `user_lat`, `user_lng`, `radius` - Location-based filtering
- `sort_by` - Sort by: created_at, rating, delivery_fee, distance
- `sort_order` - asc or desc
- `per_page` - Items per page (max 50)

#### GET `/mobile/merchants/{id}`
Get detailed merchant information including business hours, ratings, categories, and featured products.

#### GET `/mobile/merchants/{id}/products`
Get paginated products for a specific merchant with filtering options.

**Query Parameters:**
- `search` - Search products
- `category_id` - Filter by category
- `food_nationality_id` - Filter by cuisine
- `is_vegetarian` - Filter vegetarian products
- `is_spicy` - Filter spicy products
- `has_discount` - Filter discounted products
- `price_min`, `price_max` - Price range
- `preparation_time_max` - Maximum prep time
- `sort_by` - Sort options
- `per_page` - Pagination

#### GET `/mobile/merchants/{id}/categories`
Get all categories for a specific merchant with product counts.

### 🍕 Products

#### GET `/mobile/products/search`
Search products across all merchants with relevance-based sorting.

**Required Parameters:**
- `query` - Search query (minimum 2 characters)

**Optional Parameters:**
- `category_id` - Filter by category
- `food_nationality_id` - Filter by cuisine
- `merchant_id` - Filter by merchant
- `price_min`, `price_max` - Price range

#### GET `/mobile/products/featured`
Get featured products across all merchants.

#### GET `/mobile/products/popular`
Get popular products sorted by order count and ratings.

#### GET `/mobile/products/{id}`
Get comprehensive product details including:
- Product information and images
- Pricing and discounts
- Options and variations
- Nutritional information
- Reviews and ratings
- Related products

### 📂 Categories & Classifications

#### GET `/mobile/categories/food-nationalities`
Get all available food nationalities with product counts.

## Authentication Required Endpoints

### ❤️ Favorites

#### GET `/mobile/user/favorites/merchants`
Get user's favorite merchants.

#### POST `/mobile/user/favorites/merchants/{id}`
Add merchant to favorites.

#### DELETE `/mobile/user/favorites/merchants/{id}`
Remove merchant from favorites.

### 🛒 Cart Management

#### GET `/mobile/cart`
Get current cart contents with totals and applied coupons.

#### POST `/mobile/cart/add`
Add product to cart with options and quantity.

**Request Body:**
```json
{
    "product_id": 1,
    "quantity": 2,
    "options": [
        {
            "option_id": "size",
            "choice_id": "large"
        }
    ],
    "special_instructions": "No onions please"
}
```

#### PUT `/mobile/cart/update/{itemId}`
Update cart item quantity or options.

#### DELETE `/mobile/cart/remove/{itemId}`
Remove specific item from cart.

#### POST `/mobile/cart/apply-coupon`
Apply coupon code to cart.

### 📦 Orders

#### POST `/mobile/orders/create`
Create new order from cart contents.

**Request Body:**
```json
{
    "delivery_address_id": 1,
    "payment_method": "cash",
    "delivery_instructions": "Ring the doorbell",
    "scheduled_delivery_time": null
}
```

#### GET `/mobile/orders/{id}`
Get detailed order information.

#### GET `/mobile/orders/{id}/track`
Get real-time order tracking information.

## Location Services

### 📍 Location-Based Features

#### POST `/mobile/location/nearby-merchants`
Get merchants near a specific location.

**Request Body:**
```json
{
    "latitude": 24.7136,
    "longitude": 46.6753,
    "radius": 10
}
```

#### POST `/mobile/location/check-delivery`
Check delivery availability to a location.

#### POST `/mobile/location/delivery-fee`
Calculate delivery fee based on distance and order total.

## Promotions

### 🎁 Promotional Features

#### GET `/mobile/promotions/active`
Get all active promotions and special offers.

#### POST `/mobile/promotions/validate-coupon`
Validate coupon code and get discount information.

## App Configuration

### ⚙️ App Settings

#### GET `/mobile/app/config`
Get app configuration settings and feature flags.

#### POST `/mobile/app/version-check`
Check if app update is required or available.

**Request Body:**
```json
{
    "app_version": "1.0.0",
    "platform": "ios",
    "build_number": "100"
}
```

## Rate Limiting

Rate limiting is currently disabled for development. In production, the following limits apply:
- API endpoints: 60 requests per minute
- Authentication: 5 requests per minute
- Registration: 3 requests per minute

## Error Codes

- `200` - Success
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

## Testing

Use the provided Postman collection (`postman/Mobile_API_Collection.json`) for testing all endpoints. The collection includes:
- Environment variables for easy configuration
- Sample requests with proper headers
- Example request bodies for POST/PUT endpoints
- Organized folders by functionality

## Best Practices

1. **Always include language header** for proper localization
2. **Use pagination** for list endpoints to improve performance
3. **Handle errors gracefully** with proper error messages
4. **Cache responses** where appropriate to reduce API calls
5. **Include user location** for better merchant recommendations
6. **Validate input** before sending requests to reduce errors

## Support

For API support and questions, please contact the development team or refer to the main project documentation.
