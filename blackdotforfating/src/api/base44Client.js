import { createClient } from '@base44/sdk';
// import { getAccessToken } from '@base44/sdk/utils/auth-utils';

// Create a client with authentication required
export const base44 = createClient({
  appId: "69059bc3236ba5ee473c0bca", 
  requiresAuth: true // Ensure authentication is required for all operations
});
