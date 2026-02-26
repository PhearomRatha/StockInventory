/**
 * Laravel POS Backend API Contract
 * Generated for React Frontend Integration
 * 
 * Last Updated: 2026-02-26
 * 
 * IMPORTANT: This contract must match the backend exactly.
 * Do NOT modify field names or types - backend is source of truth.
 */

// ============================================================================
// PART 1: API ENDPOINT TABLE
// ============================================================================

/*
| # | Endpoint | Method | Auth | Roles | Controller |
|---|----------|--------|------|-------|------------|
| 1 | /api/auth/login | POST | No | - | AuthController |
| 2 | /api/auth/google | POST | No | - | AuthController |
| 3 | /api/auth/logout | POST | Yes | All | AuthController |
| 4 | /api/auth/me | GET | Yes | All | AuthController |
| 5 | /api/auth/change-password | POST | Yes | All | AuthController |
| 6 | /api/auth/refresh | POST | Yes | All | AuthController |
| 7 | /api/register | POST | No | - | AuthController |
| 8 | /api/roles | GET | No | - | AuthController |
| 9 | /api/dashboard/index | GET | Yes | Admin,Manager,Staff | DashboardController |
| 10 | /api/products | GET | Yes | Staff,Admin,Manager | ProductController |
| 11 | /api/products/{id} | GET | Yes | Staff,Admin,Manager | ProductController |
| 12 | /api/products | POST | Yes | Admin,Manager | ProductController |
| 13 | /api/products/{id} | PATCH | Yes | Admin,Manager | ProductController |
| 14 | /api/products/{id} | DELETE | Yes | Admin,Manager | ProductController |
| 15 | /api/products/total | GET | Yes | Staff,Admin,Manager | ProductController |
| 16 | /api/products/stock-status | GET | Yes | Staff,Admin,Manager | ProductController |
| 17 | /api/categories | GET | Yes | Admin,Manager | CategoryController |
| 18 | /api/categories | POST | Yes | Admin,Manager | CategoryController |
| 19 | /api/categories/{id} | PATCH | Yes | Admin,Manager | CategoryController |
| 20 | /api/categories/{id} | DELETE | Yes | Admin,Manager | CategoryController |
| 21 | /api/suppliers | GET | Yes | Admin,Manager | SuppliersController |
| 22 | /api/suppliers | POST | Yes | Admin,Manager | SuppliersController |
| 23 | /api/suppliers/{id} | POST | Yes | Admin,Manager | SuppliersController |
| 24 | /api/suppliers/{id} | DELETE | Yes | Admin,Manager | SuppliersController |
| 25 | /api/customers | GET | Yes | Admin,Manager,Staff | CustomerController |
| 26 | /api/customers | POST | Yes | Admin,Manager,Staff | CustomerController |
| 27 | /api/customers/{id} | PATCH | Yes | Admin,Manager,Staff | CustomerController |
| 28 | /api/customers/{id} | DELETE | Yes | Admin,Manager,Staff | CustomerController |
| 29 | /api/stock-ins | GET | Yes | Admin,Manager,Staff | StockInsController |
| 30 | /api/stock-ins/overview | GET | Yes | Admin,Manager,Staff | StockInsController |
| 31 | /api/stock-ins | POST | Yes | Admin,Manager,Staff | StockInsController |
| 32 | /api/stock-ins/{id} | PATCH | Yes | Admin,Manager,Staff | StockInsController |
| 33 | /api/stock-ins/{id} | DELETE | Yes | Admin,Manager,Staff | StockInsController |
| 34 | /api/stock-ins/totalStockIn | GET | Yes | Admin,Manager,Staff | StockInsController |
| 35 | /api/stock-outs | GET | Yes | Admin,Manager,Staff | StockOutsController |
| 36 | /api/stock-outs | POST | Yes | Admin,Manager,Staff | StockOutsController |
| 37 | /api/stock-outs/stock-out-dashboard | GET | Yes | Admin,Manager,Staff | StockOutsController |
| 38 | /api/stock-outs/{id} | GET | Yes | Admin,Manager,Staff | StockOutsController |
| 39 | /api/stock-outs/{id} | PATCH | Yes | Admin,Manager,Staff | StockOutsController |
| 40 | /api/stock-outs/{id} | DELETE | Yes | Admin,Manager,Staff | StockOutsController |
| 41 | /api/stock-outs/{id}/receipt | GET | Yes | Admin,Manager,Staff | StockOutsController |
| 42 | /api/sales | GET | Yes | Admin,Manager,Staff | SalesController |
| 43 | /api/sales | POST | Yes | Admin,Manager,Staff | SalesController |
| 44 | /api/sales/{id} | PATCH | Yes | Admin,Manager,Staff | SalesController |
| 45 | /api/sales/{id} | DELETE | Yes | Admin,Manager,Staff | SalesController |
| 46 | /api/sales/dashboard | GET | Yes | Admin,Manager,Staff | SalesController |
| 47 | /api/sales/checkout | POST | Yes | Admin,Manager,Staff | SalesController |
| 48 | /api/sales/verify-payment | POST | Yes | Admin,Manager,Staff | SalesController |
| 49 | /api/sales/data | GET | Yes | Admin,Manager,Staff | SalesController |
| 50 | /api/payments | GET | Yes | Admin,Manager,Staff | PaymentController |
| 51 | /api/payments | POST | Yes | Admin,Manager,Staff | PaymentController |
| 52 | /api/payments/{id} | PATCH | Yes | Admin,Manager,Staff | PaymentController |
| 53 | /api/payments/{id} | DELETE | Yes | Admin,Manager,Staff | PaymentController |
| 54 | /api/payments/dashboard | GET | Yes | Admin,Manager,Staff | PaymentController |
| 55 | /api/payments/checkout | POST | Yes | Admin,Manager,Staff | PaymentController |
| 56 | /api/payments/verify | POST | Yes | Admin,Manager,Staff | PaymentController |
| 57 | /api/reports/sales | GET | Yes | Admin,Manager | ReportController |
| 58 | /api/reports/financial | GET | Yes | Admin,Manager | ReportController |
| 59 | /api/reports/stock | GET | Yes | Admin,Manager | ReportController |
| 60 | /api/reports/activity-logs | GET | Yes | Admin,Manager | ReportController |
| 61 | /api/activity-logs | GET | Yes | Admin | ActivityLogsController |
| 62 | /api/activity-logs/filter | GET | Yes | Admin | ActivityLogsController |
| 63 | /api/admin/stats | GET | Yes | Admin | AdminController |
| 64 | /api/admin/reset-password | POST | Yes | Admin,Manager | AuthController |
| 65 | /api/users | GET | Yes | Admin,Manager | UserManagementController |
| 66 | /api/users | POST | Yes | Admin,Manager | UserManagementController |
| 67 | /api/users/{id} | GET | Yes | Admin,Manager | UserManagementController |
| 68 | /api/users/{id} | PATCH | Yes | Admin,Manager | UserManagementController |
| 69 | /api/users/{id} | DELETE | Yes | Admin,Manager | UserManagementController |
| 70 | /api/users/{id}/toggle-status | POST | Yes | Admin,Manager | UserManagementController |
*/

// ============================================================================
// PART 2: REQUEST/RESPONSE CONTRACTS
// ============================================================================

export interface ApiContracts {
  // Auth Endpoints
  login: AuthLoginContract;
  register: RegisterContract;
  googleLogin: GoogleLoginContract;
  logout: LogoutContract;
  me: MeContract;
  changePassword: ChangePasswordContract;
  refreshToken: RefreshTokenContract;
  
  // Products
  productsIndex: ProductsIndexContract;
  productsShow: ProductsShowContract;
  productsStore: ProductsStoreContract;
  productsUpdate: ProductsUpdateContract;
  productsStockStatus: ProductsStockStatusContract;
  
  // Categories
  categoriesStore: CategoriesStoreContract;
  categoriesUpdate: CategoriesUpdateContract;
  
  // Suppliers
  suppliersStore: SuppliersStoreContract;
  suppliersUpdate: SuppliersUpdateContract;
  
  // Customers
  customersStore: CustomersStoreContract;
  customersUpdate: CustomersUpdateContract;
  
  // Stock Ins
  stockInsStore: StockInsStoreContract;
  stockInsUpdate: StockInsUpdateContract;
  
  // Stock Outs
  stockOutsStore: StockOutsStoreContract;
  stockOutsUpdate: StockOutsUpdateContract;
  
  // Sales
  salesStore: SalesStoreContract;
  salesCheckout: SalesCheckoutContract;
  salesVerifyPayment: SalesVerifyPaymentContract;
  
  // Payments
  paymentsStore: PaymentsStoreContract;
  paymentsCheckout: PaymentsCheckoutContract;
  paymentsVerify: PaymentsVerifyContract;
  
  // Users
  usersStore: UsersStoreContract;
  usersUpdate: UsersUpdateContract;
  usersToggleStatus: UsersToggleStatusContract;
  
  // Admin
  adminResetPassword: AdminResetPasswordContract;
  
  // Reports
  reportsSales: ReportsSalesContract;
  reportsFinancial: ReportsFinancialContract;
  reportsStock: ReportsStockContract;
}

// ============================================================================
// AUTH CONTRACTS
// ============================================================================

interface AuthLoginContract {
  endpoint: "/api/auth/login";
  method: "POST";
  authRequired: false;
  request: {
    email: string;
    password: string;
  };
  validation: {
    email: "required|email";
    password: "required|string";
  };
  successResponse: {
    success: true;
    data: {
      user: {
        id: number;
        name: string;
        email: string;
        role: string;
        role_id: number;
        status: string;
      };
      token: string;
      token_type: "Bearer";
    };
  };
  errorResponse: {
    success: false;
    message: string;
    errors?: Record<string, string[]>;
  };
}

interface RegisterContract {
  endpoint: "/api/register";
  method: "POST";
  authRequired: false;
  request: {
    name: string;
    email: string;
    password: string;
  };
  validation: {
    name: "required|string|max:255";
    email: "required|string|email|max:255|unique:users,email";
    password: "required"; // ⚠️ ISSUE: No min length - security concern
  };
  successResponse: {
    success: true;
    message: string;
    user: {
      id: number;
      name: string;
      email: string;
    };
  };
  errorResponse: {
    success: false;
    message?: string;
    errors?: Record<string, string[]>;
  };
}

interface GoogleLoginContract {
  endpoint: "/api/auth/google";
  method: "POST";
  authRequired: false;
  request: {
    token: string;
  };
  validation: {
    token: "required|string";
  };
}

interface LogoutContract {
  endpoint: "/api/auth/logout";
  method: "POST";
  authRequired: true;
  roles: ["Admin", "Manager", "Staff"];
}

interface MeContract {
  endpoint: "/api/auth/me";
  method: "GET";
  authRequired: true;
}

interface ChangePasswordContract {
  endpoint: "/api/auth/change-password";
  method: "POST";
  authRequired: true;
  request: {
    current_password: string;
    new_password: string;
  };
}

interface RefreshTokenContract {
  endpoint: "/api/auth/refresh";
  method: "POST";
  authRequired: true;
}

// ============================================================================
// PRODUCTS CONTRACTS
// ============================================================================

interface ProductsIndexContract {
  endpoint: "/api/products";
  method: "GET";
  authRequired: true;
  roles: ["Staff", "Admin", "Manager"];
  queryParams?: {
    per_page?: number;
  };
}

interface ProductsShowContract {
  endpoint: "/api/products/{id}";
  method: "GET";
  authRequired: true;
  roles: ["Staff", "Admin", "Manager"];
}

interface ProductsStoreContract {
  endpoint: "/api/products";
  method: "POST";
  authRequired: true;
  roles: ["Admin", "Manager"];
  request: {
    name: string;
    category_id: number;
    supplier_id: number;
    sku?: string;
    barcode?: string;
    description?: string;
    cost: number;
    price?: number;
    stock_quantity: number;
    reorder_level?: number;
    image?: File;
  };
  validation: {
    name: "required|string|max:255";
    category_id: "required|exists:categories,id";
    supplier_id: "required|exists:suppliers,id";
    sku: "nullable|unique:products,sku";
    barcode: "nullable|string";
    description: "nullable|string";
    cost: "required|numeric";
    price: "nullable|numeric";
    stock_quantity: "required|integer";
    reorder_level: "nullable|integer";
    image: "nullable|image|mimes:jpg,jpeg,png|max:2048";
  };
}

interface ProductsUpdateContract {
  endpoint: "/api/products/{id}";
  method: "PATCH";
  authRequired: true;
  roles: ["Admin", "Manager"];
  request: {
    name?: string;
    category_id?: number;
    supplier_id?: number;
    sku?: string;
    barcode?: string;
    description?: string;
    cost?: number;
    price?: number;
    stock_quantity?: number;
    reorder_level?: number;
    image?: File;
  };
  validation: {
    name: "sometimes|required|string|max:255";
    category_id: "sometimes|required|exists:categories,id";
    supplier_id: "sometimes|required|exists:suppliers,id";
    sku: "sometimes|required|unique:products,sku,{id}";
    barcode: "nullable|string";
    description: "nullable|string";
    cost: "sometimes|required|numeric";
    price: "nullable|numeric";
    stock_quantity: "sometimes|required|integer";
    reorder_level: "nullable|integer";
    image: "nullable|image|mimes:jpg,jpeg,png|max:2048";
  };
}

interface ProductsStockStatusContract {
  endpoint: "/api/products/stock-status";
  method: "GET";
  authRequired: true;
  roles: ["Staff", "Admin", "Manager"];
}

// ============================================================================
// CATEGORIES CONTRACTS
// ============================================================================

interface CategoriesStoreContract {
  endpoint: "/api/categories";
  method: "POST";
  authRequired: true;
  roles: ["Admin", "Manager"];
  request: {
    name: string;
    description?: string;
  };
  validation: {
    name: "required|string|max:255";
    description: "nullable|string";
  };
}

interface CategoriesUpdateContract {
  endpoint: "/api/categories/{id}";
  method: "PATCH";
  authRequired: true;
  roles: ["Admin", "Manager"];
  request: {
    name?: string;
    description?: string;
  };
  validation: {
    name: "sometimes|required|string|max:255";
    description: "nullable|string";
  };
}

// ============================================================================
// SUPPLIERS CONTRACTS
// ============================================================================

interface SuppliersStoreContract {
  endpoint: "/api/suppliers";
  method: "POST";
  authRequired: true;
  roles: ["Admin", "Manager"];
  request: {
    name: string;
    contact_person?: string;
    email?: string;
    phone?: string;
    address?: string;
  };
  validation: {
    name: "required|string|max:255";
    contact_person: "nullable|string|max:255";
    email: "nullable|email|max:255";
    phone: "nullable|string|max:20";
    address: "nullable|string";
  };
}

interface SuppliersUpdateContract {
  endpoint: "/api/suppliers/{id}";
  method: "POST"; // ⚠️ ISSUE: Should be PATCH
  authRequired: true;
  roles: ["Admin", "Manager"];
  request: {
    name?: string;
    contact_person?: string;
    email?: string;
    phone?: string;
    address?: string;
  };
  validation: {
    name: "sometimes|required|string|max:255";
    contact_person: "nullable|string|max:255";
    email: "nullable|email|max:255";
    phone: "nullable|string|max:20";
    address: "nullable|string";
  };
}

// ============================================================================
// CUSTOMERS CONTRACTS
// ============================================================================

interface CustomersStoreContract {
  endpoint: "/api/customers";
  method: "POST";
  authRequired: true;
  roles: ["Admin", "Manager", "Staff"];
  request: {
    name: string;
    email?: string;
    phone?: string;
    address?: string;
  };
  validation: {
    name: "required|string|max:255";
    email: "nullable|email|max:255";
    phone: "nullable|string|max:20";
    address: "nullable|string";
  };
}

interface CustomersUpdateContract {
  endpoint: "/api/customers/{id}";
  method: "PATCH";
  authRequired: true;
  roles: ["Admin", "Manager", "Staff"];
  request: {
    name?: string;
    email?: string;
    phone?: string;
    address?: string;
  };
  validation: {
    name: "sometimes|required|string|max:255";
    email: "nullable|email|max:255";
    phone: "nullable|string|max:20";
    address: "nullable|string";
  };
}

// ============================================================================
// STOCK INS CONTRACTS
// ============================================================================

interface StockInsStoreContract {
  endpoint: "/api/stock-ins";
  method: "POST";
  authRequired: true;
  roles: ["Admin", "Manager", "Staff"];
  request: {
    product_id: number;
    supplier_id: number;
    quantity: number;
    date: string; // YYYY-MM-DD
    notes?: string;
  };
  validation: {
    product_id: "required|exists:products,id";
    supplier_id: "required|exists:suppliers,id";
    quantity: "required|integer|min:1";
    date: "required|date";
    notes: "nullable|string";
  };
}

interface StockInsUpdateContract {
  endpoint: "/api/stock-ins/{id}";
  method: "PATCH";
  authRequired: true;
  roles: ["Admin", "Manager", "Staff"];
  request: {
    product_id?: number;
    supplier_id?: number;
    quantity?: number;
    date?: string;
    notes?: string;
  };
  validation: {
    product_id: "sometimes|required|exists:products,id";
    supplier_id: "sometimes|required|exists:suppliers,id";
    quantity: "sometimes|required|integer|min:1";
    date: "sometimes|required|date";
    notes: "nullable|string";
  };
}

// ============================================================================
// STOCK OUTS CONTRACTS
// ============================================================================

interface StockOutsStoreContract {
  endpoint: "/api/stock-outs";
  method: "POST";
  authRequired: true;
  roles: ["Admin", "Manager", "Staff"];
  request: {
    product_id: number;
    customer_id?: number;
    quantity: number;
    date: string;
    notes?: string;
  };
  validation: {
    product_id: "required|exists:products,id";
    customer_id: "nullable|exists:customers,id";
    quantity: "required|integer|min:1";
    date: "required|date";
    notes: "nullable|string";
  };
}

interface StockOutsUpdateContract {
  endpoint: "/api/stock-outs/{id}";
  method: "PATCH";
  authRequired: true;
  roles: ["Admin", "Manager", "Staff"];
  request: {
    product_id?: number;
    customer_id?: number;
    quantity?: number;
    date?: string;
    notes?: string;
  };
  validation: {
    product_id: "sometimes|required|exists:products,id";
    customer_id: "nullable|exists:customers,id";
    quantity: "sometimes|required|integer|min:1";
    date: "sometimes|required|date";
    notes: "nullable|string";
  };
}

// ============================================================================
// SALES CONTRACTS
// ============================================================================

interface SalesStoreContract {
  endpoint: "/api/sales";
  method: "POST";
  authRequired: true;
  roles: ["Admin", "Manager", "Staff"];
  request: {
    customer_id?: number | null;
    items: Array<{
      product_id: number;
      quantity: number;
    }>;
    notes?: string;
  };
  validation: {
    customer_id: "nullable|exists:customers,id";
    items: "required|array|min:1";
    "items.*.product_id": "required|exists:products,id";
    "items.*.quantity": "required|integer|min:1";
    notes: "nullable|string";
  };
}

interface SalesCheckoutContract {
  endpoint: "/api/sales/checkout";
  method: "POST";
  authRequired: true;
  roles: ["Admin", "Manager", "Staff"];
  request: {
    customer_id?: number | null;
    items: Array<{
      product_id: number;
      quantity: number;
    }>;
    payment_method: string;
    notes?: string;
  };
  validation: {
    customer_id: "nullable|exists:customers,id";
    items: "required|array|min:1";
    "items.*.product_id": "required|exists:products,id";
    "items.*.quantity": "required|integer|min:1";
    payment_method: "required|string";
    notes: "nullable|string";
  };
}

interface SalesVerifyPaymentContract {
  endpoint: "/api/sales/verify-payment";
  method: "POST";
  authRequired: true;
  roles: ["Admin", "Manager", "Staff"];
  request: {
    sale_id: number;
    payment_reference: string;
  };
  validation: {
    sale_id: "required|exists:sales,id";
    payment_reference: "required|string";
  };
}

// ============================================================================
// PAYMENTS CONTRACTS
// ============================================================================

interface PaymentsStoreContract {
  endpoint: "/api/payments";
  method: "POST";
  authRequired: true;
  roles: ["Admin", "Manager", "Staff"];
  request: {
    sale_id: number;
    amount: number;
    payment_method: string;
    reference?: string;
    notes?: string;
  };
  validation: {
    sale_id: "required|exists:sales,id";
    amount: "required|numeric|min:0";
    payment_method: "required|string";
    reference: "nullable|string";
    notes: "nullable|string";
  };
}

interface PaymentsCheckoutContract {
  endpoint: "/api/payments/checkout";
  method: "POST";
  authRequired: true;
  roles: ["Admin", "Manager", "Staff"];
  request: {
    sale_id: number;
    amount: number;
    payment_method: string;
    reference?: string;
    notes?: string;
  };
}

interface PaymentsVerifyContract {
  endpoint: "/api/payments/verify";
  method: "POST";
  authRequired: true;
  roles: ["Admin", "Manager", "Staff"];
  request: {
    payment_id: number;
    reference: string;
  };
  validation: {
    payment_id: "required|exists:payments,id";
    reference: "required|string";
  };
}

// ============================================================================
// USERS CONTRACTS
// ============================================================================

interface UsersStoreContract {
  endpoint: "/api/users";
  method: "POST";
  authRequired: true;
  roles: ["Admin", "Manager"];
  request: {
    name: string;
    email: string;
    password?: string;
    role_id?: number;
  };
  validation: {
    name: "required|string|max:255|min:2";
    email: "required|email|max:255|unique:users,email";
    password: "nullable|string|min:6|max:50";
    role_id: "sometimes|required|exists:roles,id";
  };
}

interface UsersUpdateContract {
  endpoint: "/api/users/{id}";
  method: "PATCH";
  authRequired: true;
  roles: ["Admin", "Manager"];
}

interface UsersToggleStatusContract {
  endpoint: "/api/users/{id}/toggle-status";
  method: "POST";
  authRequired: true;
  roles: ["Admin", "Manager"];
  request: {
    user_id: number;
  };
  validation: {
    user_id: "required|exists:users,id";
  };
}

// ============================================================================
// ADMIN CONTRACTS
// ============================================================================

interface AdminResetPasswordContract {
  endpoint: "/api/admin/reset-password";
  method: "POST";
  authRequired: true;
  roles: ["Admin", "Manager"];
  request: {
    user_id: number;
    new_password: string;
  };
}

// ============================================================================
// REPORTS CONTRACTS
// ============================================================================

interface ReportsSalesContract {
  endpoint: "/api/reports/sales";
  method: "GET";
  authRequired: true;
  roles: ["Admin", "Manager"];
  queryParams?: {
    start_date?: string; // YYYY-MM-DD
    end_date?: string; // YYYY-MM-DD
  };
}

interface ReportsFinancialContract {
  endpoint: "/api/reports/financial";
  method: "GET";
  authRequired: true;
  roles: ["Admin", "Manager"];
  queryParams?: {
    start_date?: string;
    end_date?: string;
  };
}

interface ReportsStockContract {
  endpoint: "/api/reports/stock";
  method: "GET";
  authRequired: true;
  roles: ["Admin", "Manager"];
}

// ============================================================================
// PART 3: RESPONSE HELPER TYPES
// ============================================================================

export interface SuccessResponse<T = unknown> {
  status: number;
  message: string;
  data: T;
}

export interface ErrorResponse {
  status: number;
  message: string;
  errors?: Record<string, string[]>;
}

// ============================================================================
// PART 4: FRONTEND VALIDATION SCHEMAS (Yup)
// ============================================================================

import * as Yup from "yup";

// Auth Schemas
export const loginSchema = Yup.object({
  email: Yup.string().required("Email is required").email("Invalid email format"),
  password: Yup.string().required("Password is required"),
});

export const registerSchema = Yup.object({
  name: Yup.string().required("Name is required").max(255, "Name too long"),
  email: Yup.string()
    .required("Email is required")
    .email("Invalid email format")
    .max(255, "Email too long"),
  password: Yup.string().required("Password is required"), // ⚠️ Consider adding min:8
});

// Product Schemas
export const productStoreSchema = Yup.object({
  name: Yup.string().required("Product name is required").max(255),
  category_id: Yup.number().required("Category is required").positive(),
  supplier_id: Yup.number().required("Supplier is required").positive(),
  sku: Yup.string().nullable(),
  barcode: Yup.string().nullable(),
  description: Yup.string().nullable(),
  cost: Yup.number().required("Cost is required").positive(),
  price: Yup.number().nullable().positive(),
  stock_quantity: Yup.number().required("Stock quantity is required").integer().min(0),
  reorder_level: Yup.number().nullable().integer().min(0),
  image: Yup.mixed().nullable(),
});

export const productUpdateSchema = productStoreSchema.shape({
  name: Yup.string().max(255),
  category_id: Yup.number().positive(),
  supplier_id: Yup.number().positive(),
  cost: Yup.number().positive(),
  price: Yup.number().nullable().positive(),
  stock_quantity: Yup.number().integer().min(0),
  reorder_level: Yup.number().integer().min(0),
});

// Category Schemas
export const categoryStoreSchema = Yup.object({
  name: Yup.string().required("Category name is required").max(255),
  description: Yup.string().nullable(),
});

export const categoryUpdateSchema = categoryStoreSchema.shape({
  name: Yup.string().max(255),
});

// Supplier Schemas
export const supplierStoreSchema = Yup.object({
  name: Yup.string().required("Supplier name is required").max(255),
  contact_person: Yup.string().nullable().max(255),
  email: Yup.string().nullable().email("Invalid email").max(255),
  phone: Yup.string().nullable().max(20),
  address: Yup.string().nullable(),
});

export const supplierUpdateSchema = supplierStoreSchema.shape({
  name: Yup.string().max(255),
  email: Yup.string().nullable().email("Invalid email").max(255),
});

// Customer Schemas
export const customerStoreSchema = Yup.object({
  name: Yup.string().required("Customer name is required").max(255),
  email: Yup.string().nullable().email("Invalid email").max(255),
  phone: Yup.string().nullable().max(20),
  address: Yup.string().nullable(),
});

export const customerUpdateSchema = customerStoreSchema.shape({
  name: Yup.string().max(255),
  email: Yup.string().nullable().email("Invalid email").max(255),
});

// Stock In Schemas
export const stockInStoreSchema = Yup.object({
  product_id: Yup.number().required("Product is required").positive(),
  supplier_id: Yup.number().required("Supplier is required").positive(),
  quantity: Yup.number().required("Quantity is required").integer().min(1),
  date: Yup.string().required("Date is required"), // Format: YYYY-MM-DD
  notes: Yup.string().nullable(),
});

export const stockInUpdateSchema = stockInStoreSchema.shape({
  product_id: Yup.number().positive(),
  supplier_id: Yup.number().positive(),
  quantity: Yup.number().integer().min(1),
  date: Yup.string(),
});

// Stock Out Schemas
export const stockOutStoreSchema = Yup.object({
  product_id: Yup.number().required("Product is required").positive(),
  customer_id: Yup.number().nullable().positive(),
  quantity: Yup.number().required("Quantity is required").integer().min(1),
  date: Yup.string().required("Date is required"),
  notes: Yup.string().nullable(),
});

export const stockOutUpdateSchema = stockOutStoreSchema.shape({
  product_id: Yup.number().positive(),
  customer_id: Yup.number().nullable().positive(),
  quantity: Yup.number().integer().min(1),
  date: Yup.string(),
});

// Sales Schemas
export const saleItemSchema = Yup.object({
  product_id: Yup.number().required("Product is required").positive(),
  quantity: Yup.number().required("Quantity is required").integer().min(1),
});

export const salesStoreSchema = Yup.object({
  customer_id: Yup.number().nullable().positive(),
  items: Yup.array()
    .required("Items are required")
    .min(1, "At least one item is required")
    .of(saleItemSchema),
  notes: Yup.string().nullable(),
});

export const salesCheckoutSchema = Yup.object({
  customer_id: Yup.number().nullable().positive(),
  items: Yup.array()
    .required("Items are required")
    .min(1, "At least one item is required")
    .of(saleItemSchema),
  payment_method: Yup.string().required("Payment method is required"),
  notes: Yup.string().nullable(),
});

// Payment Schemas
export const paymentStoreSchema = Yup.object({
  sale_id: Yup.number().required("Sale is required").positive(),
  amount: Yup.number().required("Amount is required").min(0),
  payment_method: Yup.string().required("Payment method is required"),
  reference: Yup.string().nullable(),
  notes: Yup.string().nullable(),
});

export const paymentVerifySchema = Yup.object({
  payment_id: Yup.number().required("Payment ID is required").positive(),
  reference: Yup.string().required("Reference is required"),
});

// User Schemas
export const userStoreSchema = Yup.object({
  name: Yup.string().required("Name is required").min(2).max(255),
  email: Yup.string()
    .required("Email is required")
    .email("Invalid email")
    .max(255),
  password: Yup.string().nullable().min(6, "Password must be at least 6 characters").max(50),
  role_id: Yup.number().nullable().positive(),
});

export const userUpdateSchema = userStoreSchema.shape({
  name: Yup.string().min(2).max(255),
  email: Yup.string().email("Invalid email").max(255),
});

// Change Password Schema
export const changePasswordSchema = Yup.object({
  current_password: Yup.string().required("Current password is required"),
  new_password: Yup.string()
    .required("New password is required")
    .min(6, "Password must be at least 6 characters"),
});

// Admin Reset Password Schema
export const adminResetPasswordSchema = Yup.object({
  user_id: Yup.number().required("User ID is required").positive(),
  new_password: Yup.string()
    .required("New password is required")
    .min(6, "Password must be at least 6 characters"),
});

// ============================================================================
// PART 5: ISSUES AND MISMATCHES
// ============================================================================

/*
ISSUES FOUND:

1. ⚠️ /api/suppliers/{id} uses POST instead of PATCH
   - Backend route: Route::post('/{id}', 'update')
   - Should be: Route::patch('/{id}', 'update')
   - Frontend fix: Use POST method for updates

2. ⚠️ Register endpoint has weak password validation
   - Backend: 'password' => 'required' (no min length)
   - Security concern: Should add min:8

3. ⚠️ Missing Admin routes in api.php
   - AdminController has methods not registered:
     - getPendingRequests()
     - approveUser()
     - rejectUser()
     - getAllUsers()
     - toggleUserStatus()
     - approveUserRequest()
     - rejectUserRequest()
   - These are inaccessible via API

4. ⚠️ RoleController publicRoles() not registered
   - Method exists but no route defined

5. ⚠️ Sales verify-payment uses sale_id but verifyPayment uses payment_id
   - Inconsistent naming between endpoints
*/

// ============================================================================
// PART 6: SECURITY NOTES
// ============================================================================

/*
SECURITY CONCERNS:

1. Public endpoints without rate limiting
2. No CSRF protection (API-only)
3. Register endpoint creates users without role assignment
4. Weak password validation on registration
5. Admin stats endpoint cached for 5 minutes (stale data)
*/

// ============================================================================
// EXPORT DEFAULT
// ============================================================================

export default ApiContracts;
