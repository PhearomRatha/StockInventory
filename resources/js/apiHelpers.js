// apiHelpers.js - API Helper Functions for Stock Inventory System
// This file provides reusable functions for all API endpoints, optimized for React/Vue frontend.
// Includes individual endpoint functions and aggregated helpers for dashboard, alerts, and lists.

// Base API URL - Update this to match your environment
const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

// Helper function to get auth token from localStorage (adjust as needed)
const getAuthToken = () => localStorage.getItem('auth_token');

// Generic fetch wrapper with auth headers
const apiRequest = async (url, options = {}) => {
  const headers = {
    'Content-Type': 'application/json',
    ...options.headers,
  };

  if (getAuthToken()) {
    headers.Authorization = `Bearer ${getAuthToken()}`;
  }

  const response = await fetch(`${API_BASE_URL}${url}`, {
    ...options,
    headers,
  });

  if (!response.ok) {
    throw new Error(`API Error: ${response.status} ${response.statusText}`);
  }

  return response.json();
};

// =============================================================================
// AUTH ENDPOINTS
// =============================================================================

/**
 * Login user
 * @param {Object} credentials - {email, password}
 * @returns {Promise<Object>} - {user, token}
 * Example: await login({email: 'user@example.com', password: 'password'});
 */
export const login = async (credentials) => {
  return apiRequest('/login', {
    method: 'POST',
    body: JSON.stringify(credentials),
  });
};

/**
 * Get public roles
 * @returns {Promise<Array>} - List of roles
 * Example: const roles = await getPublicRoles();
 */
export const getPublicRoles = async () => {
  return apiRequest('/pubroles');
};

/**
 * Register new user
 * @param {Object} userData - {name, email, password, role_id}
 * @returns {Promise<Object>} - Created user
 * Example: await signup({name: 'John', email: 'john@example.com', password: 'pass'});
 */
export const signup = async (userData) => {
  return apiRequest('/signup', {
    method: 'POST',
    body: JSON.stringify(userData),
  });
};

/**
 * Logout user
 * @returns {Promise<Object>} - Logout confirmation
 * Example: await logout();
 */
export const logout = async () => {
  return apiRequest('/logout', {
    method: 'POST',
  });
};

// =============================================================================
// DASHBOARD ENDPOINTS
// =============================================================================

/**
 * Get dashboard summary data
 * @returns {Promise<Object>} - {totalProduct, totalCustomer, totalSales, totalSupplier, totalStockOut, totalStockIn}
 * Example: const dashboard = await getDashboardData();
 */
export const getDashboardData = async () => {
  return apiRequest('/dashboard/index');
};

// =============================================================================
// PRODUCTS ENDPOINTS
// =============================================================================

/**
 * Get all products
 * @returns {Promise<Array>} - List of products with relations
 * Example: const products = await getProducts();
 */
export const getProducts = async () => {
  return apiRequest('/products');
};

/**
 * Get single product by ID
 * @param {number} id - Product ID
 * @returns {Promise<Object>} - Product details
 * Example: const product = await getProduct(1);
 */
export const getProduct = async (id) => {
  return apiRequest(`/products/${id}`);
};

/**
 * Create new product
 * @param {Object} productData - Product data (FormData for image upload)
 * @returns {Promise<Object>} - Created product
 * Example: await createProduct(formData);
 */
export const createProduct = async (productData) => {
  const headers = {};
  if (getAuthToken()) headers.Authorization = `Bearer ${getAuthToken()}`;

  const response = await fetch(`${API_BASE_URL}/products`, {
    method: 'POST',
    headers,
    body: productData, // FormData
  });

  if (!response.ok) {
    throw new Error(`API Error: ${response.status} ${response.statusText}`);
  }

  return response.json();
};

/**
 * Update product
 * @param {number} id - Product ID
 * @param {Object} productData - Updated data (FormData for image)
 * @returns {Promise<Object>} - Updated product
 * Example: await updateProduct(1, formData);
 */
export const updateProduct = async (id, productData) => {
  const headers = {};
  if (getAuthToken()) headers.Authorization = `Bearer ${getAuthToken()}`;

  const response = await fetch(`${API_BASE_URL}/products/${id}`, {
    method: 'POST', // Laravel uses PATCH but often POST for files
    headers,
    body: productData,
  });

  if (!response.ok) {
    throw new Error(`API Error: ${response.status} ${response.statusText}`);
  }

  return response.json();
};

/**
 * Delete product
 * @param {number} id - Product ID
 * @returns {Promise<Object>} - Deletion confirmation
 * Example: await deleteProduct(1);
 */
export const deleteProduct = async (id) => {
  return apiRequest(`/products/${id}`, {
    method: 'DELETE',
  });
};

/**
 * Get total products count
 * @returns {Promise<Object>} - {total_products}
 * Example: const total = await getTotalProducts();
 */
export const getTotalProducts = async () => {
  return apiRequest('/products/total');
};

/**
 * Get stock status (low stock, out of stock)
 * @returns {Promise<Object>} - {low_stock: [], out_of_stock: []}
 * Example: const alerts = await getStockAlerts();
 */
export const getStockAlerts = async () => {
  return apiRequest('/products/stock-status');
};

// =============================================================================
// CATEGORIES ENDPOINTS
// =============================================================================

/**
 * Get all categories
 * @returns {Promise<Array>} - List of categories
 * Example: const categories = await getCategories();
 */
export const getCategories = async () => {
  return apiRequest('/categories');
};

/**
 * Get single category
 * @param {number} id - Category ID
 * @returns {Promise<Object>} - Category details
 * Example: const category = await getCategory(1);
 */
export const getCategory = async (id) => {
  return apiRequest(`/categories/${id}`);
};

/**
 * Create category
 * @param {Object} categoryData - {name, description}
 * @returns {Promise<Object>} - Created category
 * Example: await createCategory({name: 'Electronics'});
 */
export const createCategory = async (categoryData) => {
  return apiRequest('/categories', {
    method: 'POST',
    body: JSON.stringify(categoryData),
  });
};

/**
 * Update category
 * @param {number} id - Category ID
 * @param {Object} categoryData - {name, description}
 * @returns {Promise<Object>} - Updated category
 * Example: await updateCategory(1, {name: 'New Name'});
 */
export const updateCategory = async (id, categoryData) => {
  return apiRequest(`/categories/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(categoryData),
  });
};

/**
 * Delete category
 * @param {number} id - Category ID
 * @returns {Promise<Object>} - Deletion confirmation
 * Example: await deleteCategory(1);
 */
export const deleteCategory = async (id) => {
  return apiRequest(`/categories/${id}`, {
    method: 'DELETE',
  });
};

// =============================================================================
// SUPPLIERS ENDPOINTS
// =============================================================================

/**
 * Get all suppliers
 * @returns {Promise<Array>} - List of suppliers
 * Example: const suppliers = await getSuppliers();
 */
export const getSuppliers = async () => {
  return apiRequest('/suppliers');
};

/**
 * Create supplier
 * @param {Object} supplierData - {name, contact, address, company, phone, email}
 * @returns {Promise<Object>} - Created supplier
 * Example: await createSupplier({name: 'ABC Corp'});
 */
export const createSupplier = async (supplierData) => {
  return apiRequest('/suppliers', {
    method: 'POST',
    body: JSON.stringify(supplierData),
  });
};

/**
 * Update supplier
 * @param {number} id - Supplier ID
 * @param {Object} supplierData - Updated data
 * @returns {Promise<Object>} - Updated supplier
 * Example: await updateSupplier(1, {name: 'New Name'});
 */
export const updateSupplier = async (id, supplierData) => {
  return apiRequest(`/suppliers/${id}`, {
    method: 'POST', // Note: Route uses POST for update
    body: JSON.stringify(supplierData),
  });
};

/**
 * Delete supplier
 * @param {number} id - Supplier ID
 * @returns {Promise<Object>} - Deletion confirmation
 * Example: await deleteSupplier(1);
 */
export const deleteSupplier = async (id) => {
  return apiRequest(`/suppliers/${id}`, {
    method: 'DELETE',
  });
};

// =============================================================================
// CUSTOMERS ENDPOINTS
// =============================================================================

/**
 * Get all customers
 * @returns {Promise<Array>} - List of customers
 * Example: const customers = await getCustomers();
 */
export const getCustomers = async () => {
  return apiRequest('/customers');
};

/**
 * Get single customer
 * @param {number} id - Customer ID
 * @returns {Promise<Object>} - Customer details
 * Example: const customer = await getCustomer(1);
 */
export const getCustomer = async (id) => {
  return apiRequest(`/customers/${id}`);
};

/**
 * Create customer
 * @param {Object} customerData - {name, email, phone, address, preferences, notes, type}
 * @returns {Promise<Object>} - Created customer
 * Example: await createCustomer({name: 'John Doe'});
 */
export const createCustomer = async (customerData) => {
  return apiRequest('/customers', {
    method: 'POST',
    body: JSON.stringify(customerData),
  });
};

/**
 * Update customer
 * @param {number} id - Customer ID
 * @param {Object} customerData - Updated data
 * @returns {Promise<Object>} - Updated customer
 * Example: await updateCustomer(1, {name: 'Jane Doe'});
 */
export const updateCustomer = async (id, customerData) => {
  return apiRequest(`/customers/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(customerData),
  });
};

/**
 * Delete customer
 * @param {number} id - Customer ID
 * @returns {Promise<Object>} - Deletion confirmation
 * Example: await deleteCustomer(1);
 */
export const deleteCustomer = async (id) => {
  return apiRequest(`/customers/${id}`, {
    method: 'DELETE',
  });
};

// =============================================================================
// STOCK INS ENDPOINTS
// =============================================================================

/**
 * Get all stock ins
 * @returns {Promise<Array>} - List of stock ins
 * Example: const stockIns = await getStockIns();
 */
export const getStockIns = async () => {
  return apiRequest('/stock-ins');
};

/**
 * Get stock ins overview
 * @returns {Promise<Object>} - Stock ins overview
 * Example: const overview = await getStockInsOverview();
 */
export const getStockInsOverview = async () => {
  return apiRequest('/stock-ins/overview');
};

/**
 * Create stock in
 * @param {Object} stockInData - Stock in data
 * @returns {Promise<Object>} - Created stock in
 * Example: await createStockIn({product_id: 1, quantity: 10});
 */
export const createStockIn = async (stockInData) => {
  return apiRequest('/stock-ins', {
    method: 'POST',
    body: JSON.stringify(stockInData),
  });
};

/**
 * Update stock in
 * @param {number} id - Stock in ID
 * @param {Object} stockInData - Updated data
 * @returns {Promise<Object>} - Updated stock in
 * Example: await updateStockIn(1, {quantity: 20});
 */
export const updateStockIn = async (id, stockInData) => {
  return apiRequest(`/stock-ins/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(stockInData),
  });
};

/**
 * Delete stock in
 * @param {number} id - Stock in ID
 * @returns {Promise<Object>} - Deletion confirmation
 * Example: await deleteStockIn(1);
 */
export const deleteStockIn = async (id) => {
  return apiRequest(`/stock-ins/${id}`, {
    method: 'DELETE',
  });
};

/**
 * Get total stock in
 * @returns {Promise<Object>} - Total stock in quantity
 * Example: const total = await getTotalStockIn();
 */
export const getTotalStockIn = async () => {
  return apiRequest('/stock-ins/totalStockIn');
};

// =============================================================================
// STOCK OUTS ENDPOINTS
// =============================================================================

/**
 * Get all stock outs
 * @returns {Promise<Array>} - List of stock outs
 * Example: const stockOuts = await getStockOuts();
 */
export const getStockOuts = async () => {
  return apiRequest('/stock-outs');
};

/**
 * Create stock out
 * @param {Object} stockOutData - Stock out data
 * @returns {Promise<Object>} - Created stock out
 * Example: await createStockOut({product_id: 1, quantity: 5});
 */
export const createStockOut = async (stockOutData) => {
  return apiRequest('/stock-outs', {
    method: 'POST',
    body: JSON.stringify(stockOutData),
  });
};

/**
 * Update stock out
 * @param {number} id - Stock out ID
 * @param {Object} stockOutData - Updated data
 * @returns {Promise<Object>} - Updated stock out
 * Example: await updateStockOut(1, {quantity: 10});
 */
export const updateStockOut = async (id, stockOutData) => {
  return apiRequest(`/stock-outs/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(stockOutData),
  });
};

/**
 * Delete stock out
 * @param {number} id - Stock out ID
 * @returns {Promise<Object>} - Deletion confirmation
 * Example: await deleteStockOut(1);
 */
export const deleteStockOut = async (id) => {
  return apiRequest(`/stock-outs/${id}`, {
    method: 'DELETE',
  });
};

/**
 * Get stock out dashboard data
 * @returns {Promise<Object>} - Dashboard data for stock outs
 * Example: const dashboard = await getStockOutDashboard();
 */
export const getStockOutDashboard = async () => {
  return apiRequest('/stock-out-dashboard');
};

/**
 * Get stock out receipt
 * @param {number} id - Stock out ID
 * @returns {Promise<Object>} - Receipt data
 * Example: const receipt = await getStockOutReceipt(1);
 */
export const getStockOutReceipt = async (id) => {
  return apiRequest(`/stock-outs/${id}/receipt`);
};

// =============================================================================
// SALES ENDPOINTS
// =============================================================================

/**
 * Get all sales
 * @returns {Promise<Array>} - List of sales
 * Example: const sales = await getSales();
 */
export const getSales = async () => {
  return apiRequest('/sales');
};

/**
 * Create sale
 * @param {Object} saleData - Sale data
 * @returns {Promise<Object>} - Created sale
 * Example: await createSale({customer_id: 1, items: [...]});
 */
export const createSale = async (saleData) => {
  return apiRequest('/sales', {
    method: 'POST',
    body: JSON.stringify(saleData),
  });
};

/**
 * Update sale
 * @param {number} id - Sale ID
 * @param {Object} saleData - Updated data
 * @returns {Promise<Object>} - Updated sale
 * Example: await updateSale(1, {status: 'completed'});
 */
export const updateSale = async (id, saleData) => {
  return apiRequest(`/sales/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(saleData),
  });
};

/**
 * Delete sale
 * @param {number} id - Sale ID
 * @returns {Promise<Object>} - Deletion confirmation
 * Example: await deleteSale(1);
 */
export const deleteSale = async (id) => {
  return apiRequest(`/sales/${id}`, {
    method: 'DELETE',
  });
};

/**
 * Get sales dashboard data
 * @returns {Promise<Object>} - Sales dashboard data
 * Example: const dashboard = await getSalesDashboard();
 */
export const getSalesDashboard = async () => {
  return apiRequest('/sales/dashboard');
};

/**
 * Checkout sale
 * @param {Object} checkoutData - Checkout data
 * @returns {Promise<Object>} - Checkout result
 * Example: await checkoutSale({sale_id: 1, payment_method: 'cash'});
 */
export const checkoutSale = async (checkoutData) => {
  return apiRequest('/sales/checkout', {
    method: 'POST',
    body: JSON.stringify(checkoutData),
  });
};

/**
 * Verify sale payment
 * @param {Object} verifyData - Verification data
 * @returns {Promise<Object>} - Verification result
 * Example: await verifySalePayment({sale_id: 1, transaction_id: '123'});
 */
export const verifySalePayment = async (verifyData) => {
  return apiRequest('/sales/verify-payment', {
    method: 'POST',
    body: JSON.stringify(verifyData),
  });
};

/**
 * Get sales data
 * @returns {Promise<Object>} - Sales data
 * Example: const data = await getSalesData();
 */
export const getSalesData = async () => {
  return apiRequest('/sales/data');
};

// =============================================================================
// PAYMENTS ENDPOINTS
// =============================================================================

/**
 * Get all payments
 * @returns {Promise<Array>} - List of payments
 * Example: const payments = await getPayments();
 */
export const getPayments = async () => {
  return apiRequest('/payments');
};

/**
 * Create payment
 * @param {Object} paymentData - Payment data
 * @returns {Promise<Object>} - Created payment
 * Example: await createPayment({sale_id: 1, amount: 100});
 */
export const createPayment = async (paymentData) => {
  return apiRequest('/payments', {
    method: 'POST',
    body: JSON.stringify(paymentData),
  });
};

/**
 * Update payment
 * @param {number} id - Payment ID
 * @param {Object} paymentData - Updated data
 * @returns {Promise<Object>} - Updated payment
 * Example: await updatePayment(1, {status: 'paid'});
 */
export const updatePayment = async (id, paymentData) => {
  return apiRequest(`/payments/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(paymentData),
  });
};

/**
 * Delete payment
 * @param {number} id - Payment ID
 * @returns {Promise<Object>} - Deletion confirmation
 * Example: await deletePayment(1);
 */
export const deletePayment = async (id) => {
  return apiRequest(`/payments/${id}`, {
    method: 'DELETE',
  });
};

/**
 * Get payments dashboard data
 * @returns {Promise<Object>} - Payments dashboard data
 * Example: const dashboard = await getPaymentsDashboard();
 */
export const getPaymentsDashboard = async () => {
  return apiRequest('/payments/dashboard');
};

/**
 * Checkout payment
 * @param {Object} checkoutData - Checkout data
 * @returns {Promise<Object>} - Checkout result
 * Example: await checkoutPayment({payment_id: 1});
 */
export const checkoutPayment = async (checkoutData) => {
  return apiRequest('/payments/checkout', {
    method: 'POST',
    body: JSON.stringify(checkoutData),
  });
};

/**
 * Verify payment
 * @param {Object} verifyData - Verification data
 * @returns {Promise<Object>} - Verification result
 * Example: await verifyPayment({payment_id: 1, transaction_id: '123'});
 */
export const verifyPayment = async (verifyData) => {
  return apiRequest('/payments/verify', {
    method: 'POST',
    body: JSON.stringify(verifyData),
  });
};

// =============================================================================
// REPORTS ENDPOINTS
// =============================================================================

/**
 * Get sales report
 * @returns {Promise<Object>} - Sales report data
 * Example: const report = await getSalesReport();
 */
export const getSalesReport = async () => {
  return apiRequest('/reports/sales');
};

/**
 * Get financial report
 * @returns {Promise<Object>} - Financial report data
 * Example: const report = await getFinancialReport();
 */
export const getFinancialReport = async () => {
  return apiRequest('/reports/financial');
};

/**
 * Get stock report
 * @returns {Promise<Object>} - Stock report data
 * Example: const report = await getStockReport();
 */
export const getStockReport = async () => {
  return apiRequest('/reports/stock');
};

/**
 * Get activity logs report
 * @returns {Promise<Object>} - Activity logs report data
 * Example: const report = await getActivityLogsReport();
 */
export const getActivityLogsReport = async () => {
  return apiRequest('/reports/activity-logs');
};

// =============================================================================
// ACTIVITY LOGS ENDPOINTS
// =============================================================================

/**
 * Get all activity logs
 * @returns {Promise<Array>} - List of activity logs
 * Example: const logs = await getActivityLogs();
 */
export const getActivityLogs = async () => {
  return apiRequest('/activity-logs');
};

/**
 * Filter activity logs
 * @param {Object} filters - Filter parameters
 * @returns {Promise<Array>} - Filtered activity logs
 * Example: const logs = await filterActivityLogs({user_id: 1});
 */
export const filterActivityLogs = async (filters) => {
  const query = new URLSearchParams(filters).toString();
  return apiRequest(`/activity-logs/filter?${query}`);
};

/**
 * Create activity log
 * @param {Object} logData - Log data
 * @returns {Promise<Object>} - Created log
 * Example: await createActivityLog({action: 'created', module: 'products'});
 */
export const createActivityLog = async (logData) => {
  return apiRequest('/activity-logs', {
    method: 'POST',
    body: JSON.stringify(logData),
  });
};

/**
 * Update activity log
 * @param {number} id - Log ID
 * @param {Object} logData - Updated data
 * @returns {Promise<Object>} - Updated log
 * Example: await updateActivityLog(1, {action: 'updated'});
 */
export const updateActivityLog = async (id, logData) => {
  return apiRequest(`/activity-logs/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(logData),
  });
};

/**
 * Delete activity log
 * @param {number} id - Log ID
 * @returns {Promise<Object>} - Deletion confirmation
 * Example: await deleteActivityLog(1);
 */
export const deleteActivityLog = async (id) => {
  return apiRequest(`/activity-logs/${id}`, {
    method: 'DELETE',
  });
};

// =============================================================================
// USERS ENDPOINTS (Admin only)
// =============================================================================

/**
 * Get all users
 * @returns {Promise<Array>} - List of users
 * Example: const users = await getUsers();
 */
export const getUsers = async () => {
  return apiRequest('/users');
};

/**
 * Create user
 * @param {Object} userData - User data
 * @returns {Promise<Object>} - Created user
 * Example: await createUser({name: 'John', email: 'john@example.com'});
 */
export const createUser = async (userData) => {
  return apiRequest('/users', {
    method: 'POST',
    body: JSON.stringify(userData),
  });
};

/**
 * Update user
 * @param {number} id - User ID
 * @param {Object} userData - Updated data
 * @returns {Promise<Object>} - Updated user
 * Example: await updateUser(1, {name: 'Jane'});
 */
export const updateUser = async (id, userData) => {
  return apiRequest(`/users/${id}`, {
    method: 'PUT',
    body: JSON.stringify(userData),
  });
};

/**
 * Delete user
 * @param {number} id - User ID
 * @returns {Promise<Object>} - Deletion confirmation
 * Example: await deleteUser(1);
 */
export const deleteUser = async (id) => {
  return apiRequest(`/users/${id}`, {
    method: 'DELETE',
  });
};

// =============================================================================
// ROLES ENDPOINTS (Admin only)
// =============================================================================

/**
 * Get all roles
 * @returns {Promise<Array>} - List of roles
 * Example: const roles = await getRoles();
 */
export const getRoles = async () => {
  return apiRequest('/roles');
};

/**
 * Create role
 * @param {Object} roleData - Role data
 * @returns {Promise<Object>} - Created role
 * Example: await createRole({name: 'Manager'});
 */
export const createRole = async (roleData) => {
  return apiRequest('/roles', {
    method: 'POST',
    body: JSON.stringify(roleData),
  });
};

/**
 * Update role
 * @param {number} id - Role ID
 * @param {Object} roleData - Updated data
 * @returns {Promise<Object>} - Updated role
 * Example: await updateRole(1, {name: 'Senior Manager'});
 */
export const updateRole = async (id, roleData) => {
  return apiRequest(`/roles/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(roleData),
  });
};

/**
 * Delete role
 * @param {number} id - Role ID
 * @returns {Promise<Object>} - Deletion confirmation
 * Example: await deleteRole(1);
 */
export const deleteRole = async (id) => {
  return apiRequest(`/roles/${id}`, {
    method: 'DELETE',
  });
};

// =============================================================================
// AGGREGATED HELPER FUNCTIONS
// =============================================================================

/**
 * Fetch dashboard statistics (total products, total sales, total stock)
 * Critical for dashboard - combines multiple endpoints into one call
 * @returns {Promise<Object>} - Aggregated dashboard data
 * Example: const stats = await fetchDashboardStats();
 */
export const fetchDashboardStats = async () => {
  try {
    const dashboardData = await getDashboardData();
    return {
      totalProducts: dashboardData.totalProduct,
      totalSales: dashboardData.totalSales,
      totalStockIn: dashboardData.totalStockIn,
      totalStockOut: dashboardData.totalStockOut,
      totalCustomers: dashboardData.totalCustomer,
      totalSuppliers: dashboardData.totalSupplier,
    };
  } catch (error) {
    console.error('Error fetching dashboard stats:', error);
    throw error;
  }
};

/**
 * Fetch stock alerts for sidebar (low stock, out of stock)
 * Critical for sidebar alerts - optimized to minimize API calls
 * @returns {Promise<Object>} - {lowStock: [], outOfStock: []}
 * Example: const alerts = await fetchStockAlerts();
 */
export const fetchStockAlerts = async () => {
  try {
    const stockStatus = await getStockAlerts();
    return {
      lowStock: stockStatus.data.low_stock,
      outOfStock: stockStatus.data.out_of_stock,
    };
  } catch (error) {
    console.error('Error fetching stock alerts:', error);
    throw error;
  }
};

/**
 * Fetch reusable lists (products, categories, suppliers, customers)
 * Critical for reports and dropdowns - batch fetch to reduce calls
 * @returns {Promise<Object>} - {products: [], categories: [], suppliers: [], customers: []}
 * Example: const lists = await fetchReusableLists();
 */
export const fetchReusableLists = async () => {
  try {
    const [products, categories, suppliers, customers] = await Promise.all([
      getProducts(),
      getCategories(),
      getSuppliers(),
      getCustomers(),
    ]);

    return {
      products: products.data || products,
      categories: categories.data || categories,
      suppliers: suppliers.data || suppliers,
      customers: customers.data || customers,
    };
  } catch (error) {
    console.error('Error fetching reusable lists:', error);
    throw error;
  }
};

/**
 * Fetch all data needed for reports (sales, financial, stock, activity logs)
 * Critical for reports - combines all report endpoints
 * @returns {Promise<Object>} - All report data
 * Example: const reports = await fetchAllReports();
 */
export const fetchAllReports = async () => {
  try {
    const [salesReport, financialReport, stockReport, activityLogsReport] = await Promise.all([
      getSalesReport(),
      getFinancialReport(),
      getStockReport(),
      getActivityLogsReport(),
    ]);

    return {
      sales: salesReport.data || salesReport,
      financial: financialReport.data || financialReport,
      stock: stockReport.data || stockReport,
      activityLogs: activityLogsReport.data || activityLogsReport,
    };
  } catch (error) {
    console.error('Error fetching reports:', error);
    throw error;
  }
};

// Export all functions
export default {
  // Auth
  login,
  getPublicRoles,
  signup,
  logout,

  // Dashboard
  getDashboardData,

  // Products
  getProducts,
  getProduct,
  createProduct,
  updateProduct,
  deleteProduct,
  getTotalProducts,
  getStockAlerts,

  // Categories
  getCategories,
  getCategory,
  createCategory,
  updateCategory,
  deleteCategory,

  // Suppliers
  getSuppliers,
  createSupplier,
  updateSupplier,
  deleteSupplier,

  // Customers
  getCustomers,
  getCustomer,
  createCustomer,
  updateCustomer,
  deleteCustomer,

  // Stock Ins
  getStockIns,
  getStockInsOverview,
  createStockIn,
  updateStockIn,
  deleteStockIn,
  getTotalStockIn,

  // Stock Outs
  getStockOuts,
  createStockOut,
  updateStockOut,
  deleteStockOut,
  getStockOutDashboard,
  getStockOutReceipt,

  // Sales
  getSales,
  createSale,
  updateSale,
  deleteSale,
  getSalesDashboard,
  checkoutSale,
  verifySalePayment,
  getSalesData,

  // Payments
  getPayments,
  createPayment,
  updatePayment,
  deletePayment,
  getPaymentsDashboard,
  checkoutPayment,
  verifyPayment,

  // Reports
  getSalesReport,
  getFinancialReport,
  getStockReport,
  getActivityLogsReport,

  // Activity Logs
  getActivityLogs,
  filterActivityLogs,
  createActivityLog,
  updateActivityLog,
  deleteActivityLog,

  // Users
  getUsers,
  createUser,
  updateUser,
  deleteUser,

  // Roles
  getRoles,
  createRole,
  updateRole,
  deleteRole,

  // Aggregated Helpers
  fetchDashboardStats,
  fetchStockAlerts,
  fetchReusableLists,
  fetchAllReports,
};