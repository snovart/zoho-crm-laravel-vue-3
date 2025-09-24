export const DEAL_STRINGS = {
  // Titles
  pageTitle: 'Create Deal (Vue 3 + TS)',
  dealSection: 'Deal',
  customerSection: 'Customer',

  // Deal fields
  dealName: 'Deal Name',
  source: 'Source',

  // Customer fields
  firstName: 'First Name',
  lastName: 'Last Name',
  email: 'Email',

  // Buttons
  submit: 'Submit',

  // Helpers / options
  selectSource: 'Select source',
  placeholderDealName: 'Enter deal name',
  placeholderEmail: 'name@example.com',

  // Messages
  success: 'Deal has been created and pushed to Zoho successfully.',
  errRequiredDealName: 'Deal name is required.',
  errRequiredSource: 'Source is required.',
  errInvalidEmail: 'Email is invalid.',
  errGeneral: 'Unexpected error. Please try again.',

  existingCustomer: 'Existing Customer (optional)',
  existingCustomerPlaceholder: 'Select existing customer',
} as const;
