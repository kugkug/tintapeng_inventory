# Tintapeng Inventory System - API Endpoints Documentation

**Base URL:** `http://localhost:8000/api/v1`  
**API Version:** V1  
**Authentication:** JWT Bearer Token  
**Date Generated:** 2026-08-25

---

## Table of Contents

1. [Authentication](#authentication)
2. [Products](#products)
3. [Barcodes](#barcodes)
4. [Sales/POS](#salespos)
5. [Inventory](#inventory)
6. [Reports](#reports)
7. [Locations](#locations)
8. [Categories](#categories)
9. [Suppliers](#suppliers)
10. [Purchase Orders](#purchase-orders)
11. [Discounts](#discounts)
12. [Users](#users)
13. [Health Check](#health-check)

---

## Authentication

### Login (Public)

```
POST /v1/auth/login
```

**Description:** Authenticate user and receive JWT token  
**Authentication:** None (Public)  
**Request Body:**

```json
{
    "email": "admin@demo.com",
    "password": "password123"
}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
        "refresh_token": "abc123def456...",
        "token_type": "Bearer",
        "expires_in": 900,
        "user": {
            "id": 1,
            "name": "Admin User",
            "email": "admin@demo.com",
            "role": "admin",
            "tenant_id": 1
        }
    }
}
```

### Refresh Token

```
POST /v1/auth/refresh
```

**Description:** Refresh expired JWT token  
**Authentication:** Bearer Token (Required)  
**Headers:**

```
Authorization: Bearer {access_token}
```

**Response (200):** Same as Login

### Get Current User (Protected)

```
GET /v1/auth/me
```

**Description:** Get authenticated user profile  
**Authentication:** Bearer Token (Required)  
**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "tenant_id": 1,
        "name": "Admin User",
        "email": "admin@demo.com",
        "role": "admin",
        "is_active": true,
        "created_at": "2026-08-25T01:30:00Z",
        "updated_at": "2026-08-25T01:30:00Z"
    }
}
```

### Logout (Protected)

```
POST /v1/auth/logout
```

**Description:** Invalidate current JWT token  
**Authentication:** Bearer Token (Required)  
**Response (200):**

```json
{
    "success": true,
    "message": "Successfully logged out"
}
```

---

## Products

### List All Products

```
GET /v1/products
```

**Description:** Get all products (paginated, with optional filters)  
**Authentication:** Bearer Token (Required)  
**Query Parameters:**

```
?page=1
&per_page=15
&search=keyword
&category_id=1
&min_stock=10
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "tenant_id": 1,
            "name": "Product Name",
            "category_id": 1,
            "barcode": "123456789",
            "barcode_format": "CODE128",
            "barcode_image_path": "/barcodes/product-1.png",
            "unit": "pc",
            "cost_per_unit": 50.0,
            "selling_price": 100.0,
            "min_stock": 10,
            "total_stock": 45,
            "created_at": "2026-08-25T01:30:00Z",
            "updated_at": "2026-08-25T01:30:00Z"
        }
    ],
    "meta": {
        "total": 20,
        "per_page": 15,
        "current_page": 1
    }
}
```

### Get Single Product

```
GET /v1/products/{product_id}
```

**Description:** Get product details with stock information  
**Authentication:** Bearer Token (Required)  
**Response (200):** Same as List, single object

### Create Product

```
POST /v1/products
```

**Description:** Create new product (auto-generates barcode)  
**Authentication:** Bearer Token (Required)  
**Request Body:**

```json
{
    "name": "New Product",
    "category_id": 1,
    "unit": "pc",
    "cost_per_unit": 50.0,
    "selling_price": 100.0,
    "min_stock": 10,
    "barcode_format": "CODE128"
}
```

**Response (201):** Created product object

### Update Product

```
PUT /v1/products/{product_id}
```

**Description:** Update product information  
**Authentication:** Bearer Token (Required)  
**Request Body:**

```json
{
    "name": "Updated Name",
    "category_id": 1,
    "cost_per_unit": 55.0,
    "selling_price": 110.0,
    "min_stock": 15
}
```

**Response (200):** Updated product object

### Delete Product

```
DELETE /v1/products/{product_id}
```

**Description:** Delete product (cascades to inventory)  
**Authentication:** Bearer Token (Required)  
**Response (200):**

```json
{
    "success": true,
    "message": "Product deleted successfully"
}
```

### Get Product by Barcode

```
GET /v1/products/barcode/{barcode}
```

**Description:** Quick lookup by barcode (POS feature)  
**Authentication:** Bearer Token (Required)  
**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Product Name",
        "barcode": "123456789",
        "selling_price": 100.0,
        "total_stock": 45
    }
}
```

---

## Barcodes

### Get Barcode Image

```
GET /v1/barcodes/{product_id}/image
```

**Description:** Get barcode as base64-encoded PNG image  
**Authentication:** Bearer Token (Required)  
**Query Parameters:**

```
?format=base64  // default
?format=png     // returns PNG file
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "barcode": "123456789",
        "format": "CODE128",
        "image": "data:image/png;base64,iVBORw0KGgoAAAANS..."
    }
}
```

### Generate Barcode

```
POST /v1/barcodes/generate
```

**Description:** Generate barcode for multiple products  
**Authentication:** Bearer Token (Required)  
**Request Body:**

```json
{
    "product_ids": [1, 2, 3],
    "format": "CODE128"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Barcodes generated successfully"
}
```

### Regenerate Single Barcode

```
POST /v1/barcodes/{product_id}/regenerate
```

**Description:** Regenerate barcode for product  
**Authentication:** Bearer Token (Required)  
**Response (200):** Barcode image object

### Print Barcode Labels

```
POST /v1/barcodes/print-labels
```

**Description:** Generate printable PDF of barcode labels (Avery 5160 format)  
**Authentication:** Bearer Token (Required)  
**Request Body:**

```json
{
    "product_ids": [1, 2, 3]
}
```

**Response (200):** PDF file download

### Validate Barcode

```
GET /v1/barcodes/validate/{barcode}
```

**Description:** Check if barcode exists and is valid  
**Authentication:** Bearer Token (Required)  
**Response (200):**

```json
{
    "success": true,
    "data": {
        "valid": true,
        "product_id": 1,
        "product_name": "Product Name"
    }
}
```

---

## Sales/POS

### List All Sales

```
GET /v1/sales
```

**Description:** Get all sales transactions (paginated, filterable by date)  
**Authentication:** Bearer Token (Required)  
**Query Parameters:**

```
?page=1
&per_page=15
&start_date=2026-08-01
&end_date=2026-08-31
&payment_method=cash
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "tenant_id": 1,
            "user_id": 1,
            "location_id": 1,
            "sale_date": "2026-08-25T15:30:00Z",
            "total_amount": 500.0,
            "discount_amount": 50.0,
            "final_amount": 450.0,
            "payment_method": "cash",
            "items_count": 3,
            "items": [
                {
                    "id": 1,
                    "product_id": 1,
                    "quantity": 2,
                    "unit_price": 100.0,
                    "subtotal": 200.0,
                    "product": {
                        "id": 1,
                        "name": "Product Name"
                    }
                }
            ],
            "created_at": "2026-08-25T15:30:00Z"
        }
    ]
}
```

### Get Single Sale

```
GET /v1/sales/{sale_id}
```

**Description:** Get complete sale transaction with items  
**Authentication:** Bearer Token (Required)  
**Response (200):** Single sale object with items

### Create Sale (POS Transaction)

```
POST /v1/sales
```

**Description:** Create new sale transaction (auto-decrements stock)  
**Authentication:** Bearer Token (Required)  
**Request Body:**

```json
{
    "location_id": 1,
    "items": [
        {
            "product_id": 1,
            "quantity": 2,
            "unit_price": 100.0
        },
        {
            "product_id": 2,
            "quantity": 1,
            "unit_price": 150.0
        }
    ],
    "discount_id": 1,
    "payment_method": "cash"
}
```

**Response (201):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "total_amount": 350.0,
        "discount_amount": 35.0,
        "final_amount": 315.0,
        "payment_method": "cash"
    }
}
```

### Update Sale (Partial)

```
PUT /v1/sales/{sale_id}
```

**Description:** Update sale details (items not editable after creation)  
**Authentication:** Bearer Token (Required)  
**Request Body:**

```json
{
    "payment_method": "card"
}
```

**Response (200):** Updated sale object

### Delete Sale (Refund)

```
DELETE /v1/sales/{sale_id}
```

**Description:** Cancel sale and restore stock  
**Authentication:** Bearer Token (Required)  
**Response (200):**

```json
{
    "success": true,
    "message": "Sale cancelled and stock restored"
}
```

---

## Inventory

### List Inventory Summary

```
GET /v1/inventory
```

**Description:** Get stock summary across all locations  
**Authentication:** Bearer Token (Required)  
**Query Parameters:**

```
?location_id=1
&product_id=1
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "product_id": 1,
            "location_id": 1,
            "quantity": 45,
            "product": {
                "id": 1,
                "name": "Product Name",
                "min_stock": 10
            },
            "location": {
                "id": 1,
                "name": "Main Store"
            }
        }
    ]
}
```

### Adjust Inventory

```
POST /v1/inventory/adjust
```

**Description:** Adjust stock (purchase, sale, damage, return)  
**Authentication:** Bearer Token (Required)  
**Request Body:**

```json
{
    "product_id": 1,
    "location_id": 1,
    "quantity": 10,
    "type": "purchase",
    "reason": "Received from supplier",
    "reference": "PO-001"
}
```

**Response (201):**

```json
{
    "success": true,
    "data": {
        "adjustment_id": 1,
        "new_quantity": 55,
        "adjusted_by": "admin@demo.com"
    }
}
```

### Get Low Stock Products

```
GET /v1/inventory/low-stock
```

**Description:** Get products below minimum stock threshold  
**Authentication:** Bearer Token (Required)  
**Query Parameters:**

```
?location_id=1
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "product_id": 1,
            "product_name": "Product Name",
            "current_stock": 5,
            "min_stock": 10,
            "shortage": 5,
            "location_id": 1
        }
    ]
}
```

### Get Inventory Logs

```
GET /v1/inventory/logs/{product_id}
```

**Description:** Get audit trail of all inventory adjustments for product  
**Authentication:** Bearer Token (Required)  
**Query Parameters:**

```
?location_id=1
&type=purchase
&start_date=2026-08-01
&end_date=2026-08-31
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "product_id": 1,
            "location_id": 1,
            "type": "purchase",
            "quantity_change": 10,
            "previous_quantity": 45,
            "new_quantity": 55,
            "reason": "Received from supplier",
            "reference": "PO-001",
            "adjusted_by_user": "admin@demo.com",
            "created_at": "2026-08-25T10:00:00Z"
        }
    ]
}
```

---

## Reports

### Daily Sales Report

```
GET /v1/reports/daily
```

**Description:** Get sales summary for today/specific date by payment method  
**Authentication:** Bearer Token (Required)  
**Query Parameters:**

```
?date=2026-08-25
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "date": "2026-08-25",
        "total_sales": 5000.0,
        "total_discounts": 500.0,
        "net_revenue": 4500.0,
        "transaction_count": 25,
        "by_payment_method": {
            "cash": {
                "total": 3000.0,
                "count": 15
            },
            "card": {
                "total": 2000.0,
                "count": 10
            }
        }
    }
}
```

### Weekly Sales Report

```
GET /v1/reports/weekly
```

**Description:** Get 7-day sales trend  
**Authentication:** Bearer Token (Required)  
**Query Parameters:**

```
?week=1
&year=2026
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "week": "August 18-24, 2026",
        "total_sales": 35000.0,
        "total_discounts": 3500.0,
        "net_revenue": 31500.0,
        "daily_breakdown": [
            {
                "date": "2026-08-18",
                "sales": 5000.0,
                "count": 20
            }
        ],
        "growth_vs_previous_week": "+5.2%"
    }
}
```

### Monthly Sales Report

```
GET /v1/reports/monthly
```

**Description:** Get monthly report with top products  
**Authentication:** Bearer Token (Required)  
**Query Parameters:**

```
?month=8
&year=2026
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "month": "August 2026",
        "total_sales": 150000.0,
        "total_discounts": 15000.0,
        "net_revenue": 135000.0,
        "total_transactions": 500,
        "average_transaction": 270.0,
        "top_products": [
            {
                "product_id": 1,
                "product_name": "Product Name",
                "quantity_sold": 250,
                "revenue": 25000.0
            }
        ],
        "growth_vs_previous_month": "+8.5%"
    }
}
```

### Dashboard Summary

```
GET /v1/reports/summary
```

**Description:** Get quick dashboard metrics  
**Authentication:** Bearer Token (Required)  
**Response (200):**

```json
{
    "success": true,
    "data": {
        "today_sales": 5000.0,
        "today_transactions": 25,
        "low_stock_count": 3,
        "total_products": 20,
        "total_revenue_month": 150000.0,
        "month_growth": "+8.5%"
    }
}
```

---

## Locations

### List All Locations

```
GET /v1/locations
```

**Description:** Get all warehouse/store locations  
**Authentication:** Bearer Token (Required)  
**Query Parameters:**

```
?page=1
&per_page=10
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "tenant_id": 1,
            "name": "Main Store",
            "address": "123 Main Street",
            "is_main_warehouse": true,
            "is_active": true,
            "inventory_count": 500,
            "created_at": "2026-08-25T01:30:00Z"
        }
    ]
}
```

### Get Single Location

```
GET /v1/locations/{location_id}
```

**Description:** Get location details with inventory summary  
**Authentication:** Bearer Token (Required)  
**Response (200):** Single location object

### Create Location

```
POST /v1/locations
```

**Description:** Create new warehouse/store location  
**Authentication:** Bearer Token (Required)  
**Request Body:**

```json
{
    "name": "Branch Store",
    "address": "456 Branch Road",
    "is_main_warehouse": false
}
```

**Response (201):** Created location object

### Update Location

```
PUT /v1/locations/{location_id}
```

**Description:** Update location details  
**Authentication:** Bearer Token (Required)  
**Request Body:**

```json
{
    "name": "Updated Location Name",
    "address": "789 New Address",
    "is_active": true
}
```

**Response (200):** Updated location object

### Delete Location

```
DELETE /v1/locations/{location_id}
```

**Description:** Delete location (must have no inventory)  
**Authentication:** Bearer Token (Required)  
**Response (200):**

```json
{
    "success": true,
    "message": "Location deleted successfully"
}
```

---

## Categories

### List All Categories

```
GET /v1/categories
```

**Description:** Get all product categories  
**Authentication:** Bearer Token (Required)  
**Query Parameters:**

```
?page=1
&per_page=10
&search=keyword
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "tenant_id": 1,
            "name": "Electronics",
            "description": "Electronic products",
            "product_count": 10,
            "created_at": "2026-08-25T01:30:00Z"
        }
    ]
}
```

### Get Single Category

```
GET /v1/categories/{category_id}
```

**Description:** Get category details with products  
**Authentication:** Bearer Token (Required)  
**Response (200):** Single category object

### Create Category

```
POST /v1/categories
```

**Description:** Create new product category  
**Authentication:** Bearer Token (Required)  
**Request Body:**

```json
{
    "name": "Home & Garden",
    "description": "Home and garden products"
}
```

**Response (201):** Created category object

### Update Category

```
PUT /v1/categories/{category_id}
```

**Description:** Update category information  
**Authentication:** Bearer Token (Required)  
**Request Body:**

```json
{
    "name": "Updated Category",
    "description": "Updated description"
}
```

**Response (200):** Updated category object

### Delete Category

```
DELETE /v1/categories/{category_id}
```

**Description:** Delete category (reassign products first)  
**Authentication:** Bearer Token (Required)  
**Response (200):**

```json
{
    "success": true,
    "message": "Category deleted successfully"
}
```

---

## Suppliers

### List All Suppliers

```
GET /v1/suppliers
```

**Description:** Get all suppliers  
**Authentication:** Bearer Token (Required)  
**Query Parameters:**

```
?page=1
&per_page=10
&search=name
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "tenant_id": 1,
            "name": "Supplier Name",
            "contact_person": "John Doe",
            "email": "john@supplier.com",
            "phone": "+1-800-123-4567",
            "address": "123 Supplier St",
            "city": "City",
            "country": "Country",
            "po_count": 5,
            "created_at": "2026-08-25T01:30:00Z"
        }
    ]
}
```

### Get Single Supplier

```
GET /v1/suppliers/{supplier_id}
```

**Description:** Get supplier details with purchase orders  
**Authentication:** Bearer Token (Required)  
**Response (200):** Single supplier object

### Create Supplier

```
POST /v1/suppliers
```

**Description:** Create new supplier  
**Authentication:** Bearer Token (Required)  
**Request Body:**

```json
{
    "name": "New Supplier",
    "contact_person": "Jane Smith",
    "email": "jane@newsupplier.com",
    "phone": "+1-800-987-6543",
    "address": "456 New Supplier Ave",
    "city": "New City",
    "country": "New Country"
}
```

**Response (201):** Created supplier object

### Update Supplier

```
PUT /v1/suppliers/{supplier_id}
```

**Description:** Update supplier information  
**Authentication:** Bearer Token (Required)  
**Request Body:**

```json
{
    "name": "Updated Supplier",
    "email": "updated@supplier.com"
}
```

**Response (200):** Updated supplier object

### Delete Supplier

```
DELETE /v1/suppliers/{supplier_id}
```

**Description:** Delete supplier (no active POs)  
**Authentication:** Bearer Token (Required)  
**Response (200):**

```json
{
    "success": true,
    "message": "Supplier deleted successfully"
}
```

---

## Purchase Orders

### List All Purchase Orders

```
GET /v1/purchase-orders
```

**Description:** Get all purchase orders with status filtering  
**Authentication:** Bearer Token (Required)  
**Query Parameters:**

```
?page=1
&per_page=10
&status=pending
&supplier_id=1
&start_date=2026-08-01
&end_date=2026-08-31
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "tenant_id": 1,
            "supplier_id": 1,
            "po_number": "PO-001",
            "status": "pending",
            "total_amount": 5000.0,
            "expected_delivery": "2026-08-30",
            "items_count": 3,
            "supplier": {
                "id": 1,
                "name": "Supplier Name"
            },
            "created_at": "2026-08-25T01:30:00Z"
        }
    ]
}
```

### Get Single Purchase Order

```
GET /v1/purchase-orders/{po_id}
```

**Description:** Get PO details with all items  
**Authentication:** Bearer Token (Required)  
**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "po_number": "PO-001",
        "supplier_id": 1,
        "status": "pending",
        "total_amount": 5000.0,
        "items": [
            {
                "id": 1,
                "product_id": 1,
                "quantity": 50,
                "unit_price": 50.0,
                "subtotal": 2500.0
            }
        ],
        "expected_delivery": "2026-08-30",
        "notes": "Urgent delivery needed"
    }
}
```

### Create Purchase Order

```
POST /v1/purchase-orders
```

**Description:** Create new purchase order with items  
**Authentication:** Bearer Token (Required)  
**Request Body:**

```json
{
    "supplier_id": 1,
    "items": [
        {
            "product_id": 1,
            "quantity": 50,
            "unit_price": 50.0
        },
        {
            "product_id": 2,
            "quantity": 30,
            "unit_price": 75.0
        }
    ],
    "expected_delivery": "2026-08-30",
    "notes": "Urgent delivery needed"
}
```

**Response (201):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "po_number": "PO-001",
        "total_amount": 4250.0,
        "status": "pending"
    }
}
```

### Update Purchase Order

```
PUT /v1/purchase-orders/{po_id}
```

**Description:** Update PO (status changes trigger actions)  
**Authentication:** Bearer Token (Required)  
**Request Body:**

```json
{
    "status": "received",
    "expected_delivery": "2026-08-28"
}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "status": "received",
        "message": "Stock automatically incremented"
    }
}
```

### Delete Purchase Order

```
DELETE /v1/purchase-orders/{po_id}
```

**Description:** Cancel PO (pending status only)  
**Authentication:** Bearer Token (Required)  
**Response (200):**

```json
{
    "success": true,
    "message": "Purchase order cancelled"
}
```

---

## Discounts

### List All Discounts

```
GET /v1/discounts
```

**Description:** Get all discounts (active/inactive)  
**Authentication:** Bearer Token (Required)  
**Query Parameters:**

```
?page=1
&per_page=10
&status=active
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "tenant_id": 1,
            "name": "Summer Sale",
            "type": "percentage",
            "value": 10,
            "unit": "%",
            "valid_from": "2026-08-01",
            "valid_until": "2026-08-31",
            "max_usage": 100,
            "usage_count": 25,
            "is_active": true,
            "created_at": "2026-08-25T01:30:00Z"
        }
    ]
}
```

### Get Single Discount

```
GET /v1/discounts/{discount_id}
```

**Description:** Get discount details  
**Authentication:** Bearer Token (Required)  
**Response (200):** Single discount object

### Create Discount

```
POST /v1/discounts
```

**Description:** Create new discount  
**Authentication:** Bearer Token (Required)  
**Request Body:**

```json
{
    "name": "Flash Sale",
    "type": "percentage",
    "value": 15,
    "valid_from": "2026-09-01",
    "valid_until": "2026-09-30",
    "max_usage": 50,
    "description": "15% off all items"
}
```

**Response (201):** Created discount object

### Update Discount

```
PUT /v1/discounts/{discount_id}
```

**Description:** Update discount (usage count read-only)  
**Authentication:** Bearer Token (Required)  
**Request Body:**

```json
{
    "value": 20,
    "max_usage": 75,
    "is_active": false
}
```

**Response (200):** Updated discount object

### Delete Discount

```
DELETE /v1/discounts/{discount_id}
```

**Description:** Delete discount  
**Authentication:** Bearer Token (Required)  
**Response (200):**

```json
{
    "success": true,
    "message": "Discount deleted successfully"
}
```

---

## Users

### List All Users (Admin Only)

```
GET /v1/users
```

**Description:** Get all system users  
**Authentication:** Bearer Token (Required, Admin role)  
**Query Parameters:**

```
?page=1
&per_page=10
&role=staff
&status=active
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "tenant_id": 1,
            "name": "Admin User",
            "email": "admin@demo.com",
            "role": "admin",
            "is_active": true,
            "created_at": "2026-08-25T01:30:00Z"
        }
    ]
}
```

### Get Single User (Admin Only)

```
GET /v1/users/{user_id}
```

**Description:** Get user details  
**Authentication:** Bearer Token (Required, Admin role)  
**Response (200):** Single user object

### Create User (Admin Only)

```
POST /v1/users
```

**Description:** Create new system user  
**Authentication:** Bearer Token (Required, Admin role)  
**Request Body:**

```json
{
    "name": "New Staff",
    "email": "staff@demo.com",
    "password": "securePassword123!",
    "role": "staff"
}
```

**Response (201):**

```json
{
    "success": true,
    "data": {
        "id": 2,
        "name": "New Staff",
        "email": "staff@demo.com",
        "role": "staff",
        "is_active": true
    }
}
```

### Update User (Admin Only)

```
PUT /v1/users/{user_id}
```

**Description:** Update user information and role  
**Authentication:** Bearer Token (Required, Admin role)  
**Request Body:**

```json
{
    "name": "Updated Name",
    "role": "manager",
    "is_active": true
}
```

**Response (200):** Updated user object

### Delete User (Admin Only)

```
DELETE /v1/users/{user_id}
```

**Description:** Delete user (cannot delete own account)  
**Authentication:** Bearer Token (Required, Admin role)  
**Response (200):**

```json
{
    "success": true,
    "message": "User deleted successfully"
}
```

---

## Health Check

### Health Status (Public)

```
GET /v1/health
```

**Description:** Check API server health  
**Authentication:** None (Public)  
**Response (200):**

```json
{
    "status": "ok",
    "timestamp": "2026-08-25T15:30:00Z"
}
```

---

## Authentication Headers

All protected endpoints require:

```
Authorization: Bearer {jwt_token}
```

Example:

```bash
curl -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  http://localhost:8000/api/v1/products
```

---

## Response Format

### Success Response

```json
{
    "success": true,
    "data": {
        /* resource data */
    },
    "meta": {
        /* pagination info for list endpoints */
    }
}
```

### Error Response

```json
{
    "success": false,
    "message": "Error message",
    "error": "Detailed error description",
    "errors": {
        /* validation errors */
    }
}
```

---

## HTTP Status Codes

- **200** - OK (Success)
- **201** - Created (Resource created successfully)
- **400** - Bad Request (Invalid input)
- **401** - Unauthorized (Missing/invalid JWT)
- **403** - Forbidden (Insufficient permissions)
- **404** - Not Found (Resource not found)
- **422** - Unprocessable Entity (Validation error)
- **500** - Server Error (Internal server error)

---

## Rate Limiting

No rate limiting currently implemented. Subject to change in production.

---

## CORS

CORS is enabled for development. Update `.env` `APP_URL` to restrict for production.

---

## Demo Credentials

```
Email: admin@demo.com
Password: password123
```

---

**Last Updated:** 2026-08-25  
**API Version:** 1.0  
**Framework:** Laravel 13.17
